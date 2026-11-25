<?php

use App\Enums\ConnectionStatus;
use App\Models\BankConnection;
use Illuminate\Support\Facades\Http;

const ITEM_ID = 'd0f8a8c0-e8e3-41e9-b210-d663bd873d93';

beforeEach(function () {
    config()->set('services.pluggy', [
        'base_url' => 'https://api.pluggy.ai',
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
    ]);
});

function fakeItem(array $item, int $status = 200): void
{
    Http::fake([
        '*/auth' => Http::response(['apiKey' => 'key-1']),
        '*/items/*' => Http::response($item, $status),
    ]);
}

test('connects a bank with its name, logo and consent expiry', function () {
    fakeItem([
        'id' => ITEM_ID,
        'connector' => ['id' => 1, 'name' => 'Banco 1', 'imageUrl' => 'https://cdn.example.com/1.svg'],
        'consentExpiresAt' => '2027-11-25T02:30:00.000Z',
    ]);

    $this->artisan('bank:connect', ['itemId' => ' '.strtoupper(ITEM_ID).' '])
        ->expectsOutput('Banco conectado: Banco 1.')
        ->assertSuccessful();

    $connection = BankConnection::query()->sole();
    expect($connection->pluggy_item_id)->toBe(ITEM_ID)
        ->and($connection->connector_logo_url)->toBe('https://cdn.example.com/1.svg')
        ->and($connection->status)->toBe(ConnectionStatus::Pending)
        ->and($connection->sync_enabled)->toBeTrue()
        // 02:30 UTC is 23:30 of the day before in São Paulo.
        ->and($connection->consent_expires_at?->toDateTimeString())->toBe('2027-11-24 23:30:00');
});

test('accepts an item without logo or consent expiry', function () {
    fakeItem(['id' => ITEM_ID, 'connector' => ['id' => 1, 'name' => 'Banco 1']]);

    $this->artisan('bank:connect', ['itemId' => ITEM_ID])->assertSuccessful();

    $connection = BankConnection::query()->sole();
    expect($connection->connector_logo_url)->toBeNull()
        ->and($connection->consent_expires_at)->toBeNull();
});

test('refuses an unknown item', function () {
    fakeItem(['code' => 404, 'codeDescription' => 'ITEM_NOT_FOUND', 'message' => 'item not found'], 404);

    $this->artisan('bank:connect', ['itemId' => ITEM_ID])
        ->expectsOutput('O Pluggy não encontrou este item ID.')
        ->assertFailed();

    $this->assertDatabaseEmpty('bank_connections');
});

test('refuses an item already connected, without calling Pluggy', function () {
    BankConnection::factory()->create(['pluggy_item_id' => ITEM_ID]);
    Http::fake();

    $this->artisan('bank:connect', ['itemId' => strtoupper(ITEM_ID)])
        ->expectsOutput('Este banco já está conectado.')
        ->assertFailed();

    $this->assertDatabaseCount('bank_connections', 1);
    Http::assertNothingSent();
});

test('refuses a malformed item id, without calling Pluggy', function () {
    Http::fake();

    $this->artisan('bank:connect', ['itemId' => '../accounts'])
        ->expectsOutput('O item ID não tem um formato válido.')
        ->assertFailed();

    $this->assertDatabaseEmpty('bank_connections');
    Http::assertNothingSent();
});

test('creates nothing when Pluggy fails', function () {
    fakeItem(['code' => 500, 'message' => 'boom'], 500);

    $this->artisan('bank:connect', ['itemId' => ITEM_ID])
        ->expectsOutputToContain('Não foi possível consultar o Pluggy')
        ->assertFailed();

    $this->assertDatabaseEmpty('bank_connections');
});

test('creates nothing when the item has no connector name', function () {
    fakeItem(['id' => ITEM_ID]);

    $this->artisan('bank:connect', ['itemId' => ITEM_ID])->assertFailed();

    $this->assertDatabaseEmpty('bank_connections');
});
