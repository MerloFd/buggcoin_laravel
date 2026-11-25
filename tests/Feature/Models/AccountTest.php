<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\BankConnection;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('balance may be negative', function () {
    // Overdraft of R$ 152,30.
    $account = Account::factory()->create(['balance_cents' => -15230])->refresh();

    expect($account->balance_cents)->toBe(-15230);
});

test('credit card keeps its limit', function () {
    // Limit of R$ 5.000,00.
    $card = Account::factory()->creditCard()->create()->refresh();

    expect($card->type)->toBe(AccountType::CreditCard);
    expect($card->credit_limit_cents)->toBe(500000);
    expect($card->type->label())->toBe('cartão');
});

test('belongs to its connection', function () {
    $connection = BankConnection::factory()->create();
    $account = Account::factory()->for($connection)->create();

    expect($account->bankConnection?->is($connection))->toBeTrue();
    expect($connection->accounts)->toHaveCount(1);
});

test('pluggy account id is unique', function () {
    Account::factory()->create(['pluggy_account_id' => 'acc-1']);

    $this->expectException(UniqueConstraintViolationException::class);

    Account::factory()->create(['pluggy_account_id' => 'acc-1']);
});

test('deleting a connection never deletes its accounts', function () {
    $account = Account::factory()->create();

    try {
        $account->bankConnection?->delete();
    } catch (QueryException) {
        // Expected: restrictOnDelete.
    }

    $this->assertModelExists($account);
});
