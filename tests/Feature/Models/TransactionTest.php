<?php

namespace Tests\Feature\Models;

use App\Enums\Direction;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_amount_in_cents_with_separate_direction()
    {
        // iFood, R$ 52,90 out.
        $transaction = Transaction::factory()->create([
            'amount_cents' => 5290,
            'direction' => Direction::Out,
            'description' => 'iFood',
        ])->refresh();

        $this->assertSame(5290, $transaction->amount_cents);
        $this->assertSame(Direction::Out, $transaction->direction);
        $this->assertSame(TransactionSource::Pluggy, $transaction->source);
        $this->assertSame(TransactionStatus::Posted, $transaction->status);
    }

    public function test_pluggy_transaction_id_is_unique()
    {
        Transaction::factory()->create(['pluggy_transaction_id' => 'tx-1']);

        $this->expectException(UniqueConstraintViolationException::class);

        Transaction::factory()->create(['pluggy_transaction_id' => 'tx-1']);
    }

    public function test_many_manual_transactions_may_have_no_pluggy_id()
    {
        Transaction::factory()->manual()->count(2)->create();

        $this->assertSame(2, Transaction::query()->whereNull('pluggy_transaction_id')->count());
    }

    public function test_in_month_scope_keeps_only_that_month()
    {
        $account = Account::factory()->create();
        $inside = Transaction::factory()->for($account)->create(['occurred_on' => '2026-11-01']);
        Transaction::factory()->for($account)->create(['occurred_on' => '2026-10-31']);
        Transaction::factory()->for($account)->create(['occurred_on' => '2026-12-01']);

        $ids = Transaction::query()->inMonth(Carbon::parse('2026-11-15'))->pluck('id')->all();

        $this->assertSame([$inside->id], $ids);
        $this->assertCount(3, $account->transactions);
    }
}
