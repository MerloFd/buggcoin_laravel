<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\BankConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_connection_id' => BankConnection::factory(),
            'pluggy_account_id' => fake()->unique()->uuid(),
            'type' => AccountType::Checking,
            'name' => 'Conta corrente',
            'balance_cents' => fake()->numberBetween(0, 500_000),
            'credit_limit_cents' => null,
        ];
    }

    public function creditCard(): static
    {
        return $this->state([
            'type' => AccountType::CreditCard,
            'name' => 'Cartão de crédito',
            'credit_limit_cents' => 500_000,
        ]);
    }
}
