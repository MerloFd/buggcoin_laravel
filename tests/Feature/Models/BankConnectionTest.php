<?php

use App\Enums\ConnectionStatus;
use App\Models\BankConnection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new connection is pending and enabled', function () {
    $connection = BankConnection::query()->create([
        'pluggy_item_id' => 'item-1',
        'connector_name' => 'Banco 1',
    ])->refresh();

    expect($connection->status)->toBe(ConnectionStatus::Pending);
    expect($connection->sync_enabled)->toBeTrue();
    expect($connection->last_synced_at)->toBeNull();
});

test('item id is unique', function () {
    BankConnection::factory()->create(['pluggy_item_id' => 'item-1']);

    $this->expectException(UniqueConstraintViolationException::class);

    BankConnection::factory()->create(['pluggy_item_id' => 'item-1']);
});

test('sync enabled scope skips disabled connections', function () {
    $enabled = BankConnection::factory()->create();
    BankConnection::factory()->syncDisabled()->create();

    expect(BankConnection::query()->syncEnabled()->pluck('id')->all())->toBe([$enabled->id]);
});
