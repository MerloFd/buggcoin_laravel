<?php

namespace Tests\Feature\Models;

use App\Enums\ConnectionStatus;
use App\Models\BankConnection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_connection_is_pending_and_enabled()
    {
        $connection = BankConnection::query()->create([
            'pluggy_item_id' => 'item-1',
            'connector_name' => 'Banco 1',
        ])->refresh();

        $this->assertSame(ConnectionStatus::Pending, $connection->status);
        $this->assertTrue($connection->sync_enabled);
        $this->assertNull($connection->last_synced_at);
    }

    public function test_item_id_is_unique()
    {
        BankConnection::factory()->create(['pluggy_item_id' => 'item-1']);

        $this->expectException(UniqueConstraintViolationException::class);

        BankConnection::factory()->create(['pluggy_item_id' => 'item-1']);
    }

    public function test_sync_enabled_scope_skips_disabled_connections()
    {
        $enabled = BankConnection::factory()->create();
        BankConnection::factory()->syncDisabled()->create();

        $this->assertSame([$enabled->id], BankConnection::query()->syncEnabled()->pluck('id')->all());
    }
}
