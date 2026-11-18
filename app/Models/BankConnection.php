<?php

namespace App\Models;

use App\Enums\ConnectionStatus;
use Database\Factories\BankConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property-read Collection<int, Account> $accounts
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
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
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
