<?php

namespace Database\Factories;

use App\Enums\ConnectionStatus;
use App\Models\BankConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankConnection>
 */
class BankConnectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pluggy_item_id' => fake()->unique()->uuid(),
            'connector_name' => fake()->randomElement(['Banco 1', 'Banco 2']),
            'connector_logo_url' => null,
            'status' => ConnectionStatus::Pending,
            'sync_enabled' => true,
        ];
    }

    public function syncDisabled(): static
    {
        return $this->state(['sync_enabled' => false]);
    }

    public function synced(): static
    {
        return $this->state([
            'status' => ConnectionStatus::Synced,
            'last_synced_at' => now(),
        ]);
    }
}
