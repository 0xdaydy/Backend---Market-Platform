<?php

namespace Database\Factories;

use App\Models\Farmer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Farmer>
 */
class FarmerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_id' => fake()->unique()->numerify('F-########'),
            'name' => fake()->name(),
            'phone' => fake()->optional()->phoneNumber(),
            'village' => fake()->optional()->city(),
            'credit_limit' => fake()->randomFloat(2, 10000, 100000),
            'credit_balance_fcfa' => 0,
        ];
    }
}
