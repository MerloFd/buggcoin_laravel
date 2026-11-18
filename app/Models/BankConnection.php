<?php

namespace App\Models;

use App\Enums\ConnectionStatus;
use Database\Factories\BankConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A bank connected in Meu Pluggy (one Pluggy item).
 *
 * @property int $id
 * @property string $pluggy_item_id
 * @property string $connector_name
 * @property string|null $connector_logo_url
 * @property ConnectionStatus $status
 * @property string|null $last_error
 * @property bool $sync_enabled
 * @property Carbon|null $consent_expires_at
 * @property Carbon|null $last_synced_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'pluggy_item_id',
    'connector_name',
    'connector_logo_url',
    'status',
    'last_error',
    'sync_enabled',
    'consent_expires_at',
    'last_synced_at',
])]
class BankConnection extends Model
{
    /** @use HasFactory<BankConnectionFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
        'sync_enabled' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ConnectionStatus::class,
            'sync_enabled' => 'boolean',
            'consent_expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Connections the scheduler and "Sincronizar agora" should sync.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSyncEnabled(Builder $query): Builder
    {
        return $query->where('sync_enabled', true);
    }
}
