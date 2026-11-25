<?php

use App\Enums\Direction;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('stores amount in cents with separate direction', function () {
    // iFood, R$ 52,90 out.
    $transaction = Transaction::factory()->create([
        'amount_cents' => 5290,
        'direction' => Direction::Out,
        'description' => 'iFood',
    ])->refresh();

    expect($transaction->amount_cents)->toBe(5290);
    expect($transaction->direction)->toBe(Direction::Out);
    expect($transaction->source)->toBe(TransactionSource::Pluggy);
    expect($transaction->status)->toBe(TransactionStatus::Posted);
});

test('pluggy transaction id is unique', function () {
    Transaction::factory()->create(['pluggy_transaction_id' => 'tx-1']);

    $this->expectException(UniqueConstraintViolationException::class);

    Transaction::factory()->create(['pluggy_transaction_id' => 'tx-1']);
});

test('many manual transactions may have no pluggy id', function () {
    Transaction::factory()->manual()->count(2)->create();

    expect(Transaction::query()->whereNull('pluggy_transaction_id')->count())->toBe(2);
});

test('in month scope keeps only that month', function () {
    $account = Account::factory()->create();
    $inside = Transaction::factory()->for($account)->create(['occurred_on' => '2026-11-01']);
    Transaction::factory()->for($account)->create(['occurred_on' => '2026-10-31']);
    Transaction::factory()->for($account)->create(['occurred_on' => '2026-12-01']);

    $ids = Transaction::query()->inMonth(Carbon::parse('2026-11-15'))->pluck('id')->all();

    expect($ids)->toBe([$inside->id]);
    expect($account->transactions)->toHaveCount(3);
});
