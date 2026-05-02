<?php

namespace Database\Factories;

use App\Models\Commodity;
use App\Models\CommodityRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommodityRate>
 */
class CommodityRateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commodity_id' => Commodity::factory(),
            'rate_fcfa_per_unit' => fake()->randomFloat(2, 100, 5000),
            'is_active' => true,
        ];
    }
}
