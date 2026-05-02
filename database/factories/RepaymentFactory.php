<?php

namespace Database\Factories;

use App\Models\Farmer;
use App\Models\Repayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repayment>
 */
class RepaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'farmer_id' => Farmer::factory(),
            'operator_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'payment_method' => fake()->randomElement(['cash', 'commodity']),
            'reference' => 'RPY-' . now()->format('Ymd') . '-' . fake()->unique()->numerify('####'),
        ];
    }
}
