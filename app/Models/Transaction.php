<?php

namespace App\Models;

use App\Enums\Direction;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Every money movement, from Pluggy or typed by hand.
 *
 * @property int $id
 * @property int $account_id
 * @property TransactionSource $source
 * @property string|null $pluggy_transaction_id
 * @property Direction $direction
 * @property int $amount_cents Always positive; the sign is `direction`.
 * @property Carbon $occurred_on
 * @property string $description
 * @property string|null $pluggy_category
 * @property TransactionStatus $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Account $account
 */
#[Fillable([
    'account_id',
    'source',
    'pluggy_transaction_id',
    'direction',
    'amount_cents',
    'occurred_on',
    'description',
    'pluggy_category',
    'status',
])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'posted',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => TransactionSource::class,
            'direction' => Direction::class,
            'status' => TransactionStatus::class,
            'amount_cents' => 'integer',
            'occurred_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Transactions that happened in the given month (default: the current one).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInMonth(Builder $query, ?Carbon $month = null): Builder
    {
        $month ??= Carbon::now();

        return $query->whereBetween('occurred_on', [
            $month->copy()->startOfMonth()->toDateString(),
            $month->copy()->endOfMonth()->toDateString(),
        ]);
    }
}
