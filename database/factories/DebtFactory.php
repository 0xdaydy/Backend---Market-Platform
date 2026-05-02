<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debt>
 */
class DebtFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $principal = fake()->randomFloat(2, 1000, 50000);
        $rate = fake()->randomFloat(4, 0, 0.05);
        $totalDue = round($principal * (1 + $rate), 2);

        return [
            'transaction_id' => Transaction::factory(),
            'farmer_id' => Farmer::factory(),
            'principal' => $principal,
            'interest_rate' => $rate,
            'total_due' => $totalDue,
            'amount_repaid' => 0,
            'balance' => $totalDue,
            'status' => 'open',
        ];
    }
}
