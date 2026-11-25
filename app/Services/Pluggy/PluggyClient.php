<?php

namespace App\Services\Pluggy;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Server-only, read-only client for the Pluggy API.
 *
 * Endpoints (https://api.pluggy.ai/oas3.json, read on 2026-11-25):
 * - POST /auth             {clientId, clientSecret} → {apiKey}, valid for 2 hours.
 * - GET  /items/{id}       → Item; 404 ITEM_NOT_FOUND.
 * - GET  /accounts         ?itemId&page&pageSize → {page, total, totalPages, results}.
 * - GET  /v2/transactions  ?accountId&dateFrom&dateTo → {results, next}. `next` is the
 *   full query string of the next page, or null. GET /transactions (v1) is deprecated
 *   and removed on 2026-12-31.
 *
 * Every read sends the API key in the X-API-KEY header.
 */
class PluggyClient
{
    private const CACHE_KEY = 'pluggy.api_key';

    /** Below the 2 hours Pluggy keeps an API key valid. */
    private const API_KEY_TTL_MINUTES = 110;

    private const ACCOUNTS_PAGE_SIZE = 500;

    /**
     * @return array<string, mixed>
     */
    public function item(string $itemId): array
    {
        return $this->get('/items/'.rawurlencode($itemId));
    }

    /**
     * All accounts of an item, every page.
     *
     * @return list<array<string, mixed>>
     */
    public function accounts(string $itemId): array
    {
        $accounts = [];
        $page = 1;

        do {
            $body = $this->get('/accounts', [
                'itemId' => $itemId,
                'page' => $page,
                'pageSize' => self::ACCOUNTS_PAGE_SIZE,
            ]);

            array_push($accounts, ...$this->results($body));
            $totalPages = is_numeric($body['totalPages'] ?? null) ? (int) $body['totalPages'] : 1;
        } while ($page++ < $totalPages);

        return $accounts;
    }

    /**
     * All transactions of an account, every page. Dates are Y-m-d, inclusive.
     *
     * @return list<array<string, mixed>>
     */
    public function transactions(string $accountId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $transactions = [];
        $seen = [];
        $next = '?'.http_build_query(array_filter([
            'accountId' => $accountId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ], fn (?string $value): bool => $value !== null));

        while ($next !== null) {
            // A malformed or repeated cursor would loop forever or leave the endpoint.
            if (! is_string($next) || ! str_starts_with($next, '?') || isset($seen[$next])) {
                throw new PluggyException('Pluggy returned an invalid pagination cursor.');
            }

            $seen[$next] = true;
            $body = $this->get('/v2/transactions'.$next);
            array_push($transactions, ...$this->results($body));
            $next = $body['next'] ?? null;
        }

        return $transactions;
    }

    /**
     * @param  array<string, int|string>  $query
     * @return array<string, mixed>
     */
    private function get(string $path, array $query = []): array
    {
        $call = fn (): Response => $this->send(
            fn (): Response => $this->request()
                ->withHeader('X-API-KEY', $this->apiKey())
                ->get($path, $query === [] ? null : $query),
        );

        $response = $call();

        // A cached key may have been revoked before its TTL: authenticate again, once.
        if (in_array($response->status(), [401, 403], true)) {
            Cache::forget(self::CACHE_KEY);
            $response = $call();
        }

        return $this->body($response);
    }

    private function apiKey(): string
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_string($cached)) {
            try {
                return Crypt::decryptString($cached);
            } catch (DecryptException) {
                // APP_KEY changed: fall through and authenticate again.
            }
        }

        $clientId = config('services.pluggy.client_id');
        $clientSecret = config('services.pluggy.client_secret');

        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
            throw new PluggyException('Set PLUGGY_CLIENT_ID and PLUGGY_CLIENT_SECRET in .env.');
        }

        $body = $this->body($this->send(fn (): Response => $this->request()->post('/auth', [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
        ])));

        $apiKey = $body['apiKey'] ?? null;

        if (! is_string($apiKey) || $apiKey === '') {
            throw new PluggyException('Pluggy did not return an API key.');
        }

        // The cache lives in the database: never store the key in plain text.
        Cache::put(self::CACHE_KEY, Crypt::encryptString($apiKey), now()->addMinutes(self::API_KEY_TTL_MINUTES));

        return $apiKey;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(config()->string('services.pluggy.base_url'))
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30);
    }

    /**
     * @param  Closure(): Response  $call
     */
    private function send(Closure $call): Response
    {
        try {
            return $call();
        } catch (ConnectionException) {
            // Not chained: the original exception may describe the request.
            throw new PluggyException('Could not reach Pluggy.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function body(Response $response): array
    {
        if ($response->failed()) {
            $code = $response->json('codeDescription');
            $requestId = $response->header('x-request-id');

            throw new PluggyException(
                "Pluggy request failed with status {$response->status()}.",
                $response->status(),
                is_string($code) ? $code : null,
                $requestId === '' ? null : $requestId,
            );
        }

        $body = $response->json();

        if (! is_array($body)) {
            throw new PluggyException('Pluggy returned an unexpected response.', $response->status());
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<array<string, mixed>>
     */
    private function results(array $body): array
    {
        $results = $body['results'] ?? null;

        // Never read a malformed response as "no data".
        if (! is_array($results)) {
            throw new PluggyException('Pluggy returned a list without results.');
        }

        return array_values($results);
    }
}
