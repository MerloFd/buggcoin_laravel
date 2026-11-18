<?php

namespace Tests\Feature\Models;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\BankConnection;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_may_be_negative()
    {
        // Overdraft of R$ 152,30.
        $account = Account::factory()->create(['balance_cents' => -15230])->refresh();

        $this->assertSame(-15230, $account->balance_cents);
    }

    public function test_credit_card_keeps_its_limit()
    {
        // Limit of R$ 5.000,00.
        $card = Account::factory()->creditCard()->create()->refresh();

        $this->assertSame(AccountType::CreditCard, $card->type);
        $this->assertSame(500000, $card->credit_limit_cents);
        $this->assertSame('cartão', $card->type->label());
    }

    public function test_belongs_to_its_connection()
    {
        $connection = BankConnection::factory()->create();
        $account = Account::factory()->for($connection)->create();

        $this->assertTrue($account->bankConnection?->is($connection));
        $this->assertCount(1, $connection->accounts);
    }

    public function test_pluggy_account_id_is_unique()
    {
        Account::factory()->create(['pluggy_account_id' => 'acc-1']);

        $this->expectException(UniqueConstraintViolationException::class);

        Account::factory()->create(['pluggy_account_id' => 'acc-1']);
    }

    public function test_deleting_a_connection_never_deletes_its_accounts()
    {
        $account = Account::factory()->create();

        try {
            $account->bankConnection?->delete();
        } catch (QueryException) {
            // Expected: restrictOnDelete.
        }

        $this->assertModelExists($account);
    }
}
