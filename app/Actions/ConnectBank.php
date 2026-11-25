<?php

namespace App\Actions;

use App\Models\BankConnection;
use App\Services\Pluggy\PluggyClient;
use App\Services\Pluggy\PluggyException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Registers a Meu Pluggy item as a bank connection. Shared by bank:connect and
 * the "Adicionar banco" button.
 */
class ConnectBank
{
    public function __construct(private readonly PluggyClient $pluggy) {}

    /**
     * @throws ValidationException when the item is malformed, unknown or already connected
     * @throws PluggyException when Pluggy cannot answer
     */
    public function handle(string $itemId): BankConnection
    {
        // Pluggy ids are lowercase UUIDs; normalize so a retyped id is still a duplicate.
        $itemId = Str::lower(trim($itemId));

        if (! Str::isUuid($itemId)) {
            throw $this->invalid('O item ID não tem um formato válido.');
        }

        if (BankConnection::query()->where('pluggy_item_id', $itemId)->exists()) {
            throw $this->invalid('Este banco já está conectado.');
        }

        try {
            $item = $this->pluggy->item($itemId);
        } catch (PluggyException $exception) {
            if ($exception->status === 404) {
                throw $this->invalid('O Pluggy não encontrou este item ID.');
            }

            throw $exception;
        }

        $name = data_get($item, 'connector.name');
        $logo = data_get($item, 'connector.imageUrl');
        $consentExpiresAt = $item['consentExpiresAt'] ?? null;

        if (! is_string($name) || $name === '') {
            throw new PluggyException('Pluggy returned an item without a connector name.');
        }

        try {
            return BankConnection::query()->create([
                'pluggy_item_id' => $itemId,
                'connector_name' => $name,
                'connector_logo_url' => is_string($logo) ? $logo : null,
                // Pluggy sends UTC; Eloquent stores the app timezone without converting.
                'consent_expires_at' => is_string($consentExpiresAt)
                    ? Carbon::parse($consentExpiresAt)->setTimezone(config()->string('app.timezone'))
                    : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw $this->invalid('Este banco já está conectado.');
        }
    }

    private function invalid(string $message): ValidationException
    {
        return ValidationException::withMessages(['item_id' => $message]);
    }
}
