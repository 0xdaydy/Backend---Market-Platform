<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\DebtRepayment;
use App\Models\Repayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DebtRepayment>
 */
class DebtRepaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'debt_id' => Debt::factory(),
            'repayment_id' => Repayment::factory(),
            'amount_applied' => fake()->randomFloat(2, 100, 10000),
        ];
    }
}
