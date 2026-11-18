<?php

namespace Database\Factories;

use App\Enums\Direction;
use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'source' => TransactionSource::Pluggy,
            'pluggy_transaction_id' => fake()->unique()->uuid(),
            'direction' => Direction::Out,
            'amount_cents' => fake()->numberBetween(100, 50_000),
            'occurred_on' => now()->toDateString(),
            'description' => fake()->randomElement(['iFood', 'Uber', 'Mercado', 'Farmácia']),
            'pluggy_category' => fake()->randomElement(['Alimentação', 'Transporte', 'Saúde', null]),
            'status' => TransactionStatus::Posted,
        ];
    }

    public function income(): static
    {
        return $this->state(['direction' => Direction::In, 'description' => 'Salário']);
    }

    public function pending(): static
    {
        return $this->state(['status' => TransactionStatus::Pending]);
    }

    public function manual(): static
    {
        return $this->state([
            'source' => TransactionSource::Manual,
            'pluggy_transaction_id' => null,
            'pluggy_category' => null,
        ]);
    }
}
