<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Transaction;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => \App\Models\Account::factory(),
            'amount' => $this->faker->numberBetween(100, 10000),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'GBP']),
            'type' => $this->faker->randomElement(['deposit', 'withdrawal']),
        ];
    }
}
