<?php

use App\Services\Pluggy\PluggyClient;
use App\Services\Pluggy\PluggyException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.pluggy', [
        'base_url' => 'https://api.pluggy.ai',
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);
});

test('authenticates once and reuses the key', function () {
    Http::fake([
        '*/auth' => Http::response(['apiKey' => 'key-1']),
        '*/items/*' => Http::response(['id' => 'item-1']),
    ]);

    $client = app(PluggyClient::class);
    expect($client->item('item-1'))->toBe(['id' => 'item-1']);
    $client->item('item-1');

    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.pluggy.ai/auth'
        && $request->method() === 'POST'
        && $request->data() === ['clientId' => 'test-client-id', 'clientSecret' => 'test-client-secret']);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.pluggy.ai/items/item-1'
        && $request->hasHeader('X-API-KEY', 'key-1'));
});

test('the cached key is encrypted and expires before two hours', function () {
    Http::fake([
        '*/auth' => Http::response(['apiKey' => 'key-1']),
        '*/items/*' => Http::response(['id' => 'item-1']),
    ]);

    app(PluggyClient::class)->item('item-1');
    expect(Cache::get('pluggy.api_key'))->toBeString()->not->toContain('key-1');

    $this->travel(111)->minutes();
    expect(Cache::get('pluggy.api_key'))->toBeNull();
});

test('renews a rejected key once', function () {
    Http::fake([
        '*/auth' => Http::sequence()->push(['apiKey' => 'old'])->push(['apiKey' => 'new']),
        '*/items/*' => fn (Request $request) => $request->hasHeader('X-API-KEY', 'new')
            ? Http::response(['id' => 'item-1'])
            : Http::response(['code' => 401], 401),
    ]);

    expect(app(PluggyClient::class)->item('item-1'))->toBe(['id' => 'item-1']);
    Http::assertSentCount(4);
});

test('gives up when the new key is rejected too', function () {
    Http::fake([
        '*/auth' => Http::response(['apiKey' => 'key-1']),
        '*/items/*' => Http::response(['code' => 403], 403),
    ]);

    expect(fn () => app(PluggyClient::class)->item('item-1'))->toThrow(PluggyException::class);
    Http::assertSentCount(4);
});

test('reads every page of accounts', function () {
    Http::fake([
        '*/auth' => Http::response(['apiKey' => 'key-1']),
        '*/accounts*' => fn (Request $request) => Http::response([
            'page' => (int) $request['page'],
            'totalPages' => 2,
            'results' => [['id' => 'acc-'.$request['page']]],
        ]),
    ]);

    expect(app(PluggyClient::class)->accounts('item-1'))->toBe([['id' => 'acc-1'], ['id' => 'acc-2']]);
});

test('follows the transactions cursor exactly as returned', function () {
    Http::fake([
        '*/auth' => Http::response(['apiKey' => 'key-1']),
        '*/v2/transactions?accountId=acc-1&dateFrom=2026-11-08&dateTo=2026-11-16' => Http::response([
            'results' => [['id' => 'tx-1']],
            'next' => '?accountId=acc-1&after=abc%3D%3D',
        ]),
        '*/v2/transactions?accountId=acc-1&after=abc%3D%3D' => Http::response([
            'results' => [['id' => 'tx-2']],
            'next' => null,
        ]),
    ]);

    expect(app(PluggyClient::class)->transactions('acc-1', '2026-11-08', '2026-11-16'))
        ->toBe([['id' => 'tx-1'], ['id' => 'tx-2']]);
});

test('refuses a cursor that repeats or leaves the endpoint', function (string $next) {
    Http::fake([
        '*/auth' => Http::response(['apiKey' => 'key-1']),
        '*/v2/transactions*' => Http::response(['results' => [], 'next' => $next]),
    ]);

    expect(fn () => app(PluggyClient::class)->transactions('acc-1'))
        ->toThrow(PluggyException::class, 'invalid pagination cursor');
})->with(['?accountId=acc-1', '/../items', '.evil.test/']);

test('a list without results is an error, not an empty list', function () {
    Http::fake([
        '*/auth' => Http::response(['apiKey' => 'key-1']),
        '*/accounts*' => Http::response(['message' => 'odd']),
    ]);

    expect(fn () => app(PluggyClient::class)->accounts('item-1'))->toThrow(PluggyException::class);
});

test('failures keep the diagnosis and leak no credentials', function () {
    Http::fake(['*/auth' => Http::response(
        ['code' => 401, 'codeDescription' => 'CLIENT_KEYS_UNAUTHORIZED', 'message' => 'test-client-secret'],
        401,
        ['x-request-id' => 'req-1'],
    )]);

    try {
        app(PluggyClient::class)->item('item-1');
        $this->fail('Expected a PluggyException.');
    } catch (PluggyException $exception) {
        expect($exception->status)->toBe(401)
            ->and($exception->codeDescription)->toBe('CLIENT_KEYS_UNAUTHORIZED')
            ->and($exception->requestId)->toBe('req-1')
            ->and($exception->getMessage())->not->toContain('test-client')
            ->and($exception->getPrevious())->toBeNull();
    }
});

test('connection failures become a Pluggy exception', function () {
    Http::fake(['*/auth' => Http::failedConnection('test-client-secret')]);

    expect(fn () => app(PluggyClient::class)->item('item-1'))
        ->toThrow(PluggyException::class, 'Could not reach Pluggy.');
});

test('missing credentials never reach the network', function () {
    config()->set('services.pluggy.client_secret', null);
    Http::fake();

    expect(fn () => app(PluggyClient::class)->item('item-1'))->toThrow(PluggyException::class);
    Http::assertNothingSent();
});
