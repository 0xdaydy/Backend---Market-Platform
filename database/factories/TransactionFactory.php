<?php

namespace Database\Factories;

use App\Models\Farmer;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
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
            'reference' => 'TXN-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('####'),
            'farmer_id' => Farmer::factory(),
            'operator_id' => User::factory(),
            'payment_method' => fake()->randomElement(['cash', 'credit', 'commodity']),
            'total_amount' => fake()->randomFloat(2, 100, 10000),
            'status' => fake()->randomElement(['pending', 'completed', 'cancelled']),
        ];
    }
}
