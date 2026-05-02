<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Commodity;
use App\Models\CommodityRate;
use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommodityRepaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_repay_with_commodity_conversion(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 10000,
        ]);

        $commodity = Commodity::factory()->create(['name' => 'Maize', 'unit' => 'kg']);
        CommodityRate::factory()->create([
            'commodity_id' => $commodity->id,
            'rate_fcfa_per_unit' => 500,
            'is_active' => true,
        ]);

        $tx = Transaction::factory()->create([
            'farmer_id' => $farmer->id,
            'total_amount' => 10000,
            'payment_method' => 'credit',
        ]);
        Debt::factory()->create([
            'transaction_id' => $tx->id,
            'farmer_id' => $farmer->id,
            'principal' => 10000,
            'total_due' => 10000,
            'balance' => 10000,
            'status' => 'open',
        ]);

        // 20 kg * 500 FCFA/kg = 10,000 FCFA
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'commodity',
                'commodity_id' => $commodity->id,
                'quantity' => 20,
            ]);

        $response->assertCreated()
            ->assertJsonPath('amount', '10000.00')
            ->assertJsonPath('payment_method', 'commodity');

        $farmer->refresh();
        $this->assertEquals(0, $farmer->credit_balance_fcfa);
    }

    public function test_commodity_repayment_fails_without_active_rate(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();
        $commodity = Commodity::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'commodity',
                'commodity_id' => $commodity->id,
                'quantity' => 10,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['commodity_id']);
    }

    public function test_excess_repayment_stored_as_credit_surplus(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 5000,
        ]);

        $tx = Transaction::factory()->create([
            'farmer_id' => $farmer->id,
            'total_amount' => 5000,
            'payment_method' => 'credit',
        ]);
        Debt::factory()->create([
            'transaction_id' => $tx->id,
            'farmer_id' => $farmer->id,
            'principal' => 5000,
            'total_due' => 5000,
            'balance' => 5000,
            'status' => 'open',
        ]);

        // Pay 8000 when only 5000 is owed -> 3000 surplus
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmer->id,
                'amount' => 8000,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();

        $farmer->refresh();
        $this->assertEquals(-3000, $farmer->credit_balance_fcfa);
    }

    public function test_credit_surplus_auto_applied_to_future_credit_transaction(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => -5000, // 5000 surplus
        ]);
        $product = Product::factory()->create(['price_fcfa' => 3000]);

        // Buy 2 items = 6000, but have 5000 surplus, so net due = 1000
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertCreated();

        $farmer->refresh();
        // Surplus consumed: 5000 - 6300 = -1300 (new debt of 1300 with 5% interest)
        $this->assertEquals(1300, $farmer->credit_balance_fcfa);
    }

    public function test_credit_surplus_covers_full_transaction(): void
    {
        config(['market.interest_rate' => 0]);
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => -10000, // 10000 surplus
        ]);
        $product = Product::factory()->create(['price_fcfa' => 5000]);

        // Buy 2 items = 10000, fully covered by surplus
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertCreated();

        $farmer->refresh();
        $this->assertEquals(0, $farmer->credit_balance_fcfa);
    }
}
