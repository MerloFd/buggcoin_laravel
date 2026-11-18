<?php

namespace App\Models;

use App\Enums\AccountType;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A checking account, credit card or manual wallet.
 *
 * @property int $id
 * @property int|null $bank_connection_id
 * @property string|null $pluggy_account_id
 * @property AccountType $type
 * @property string $name
 * @property int $balance_cents Signed: may be negative.
 * @property int|null $credit_limit_cents
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read BankConnection|null $bankConnection
 */
#[Fillable([
    'bank_connection_id',
    'pluggy_account_id',
    'type',
    'name',
    'balance_cents',
    'credit_limit_cents',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'balance_cents' => 'integer',
            'credit_limit_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BankConnection, $this>
     */
    public function bankConnection(): BelongsTo
    {
        return $this->belongsTo(BankConnection::class);
    }
}
