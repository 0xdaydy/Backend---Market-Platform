<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_credit_transaction_with_interest(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 0,
        ]);
        $product = Product::factory()->create(['price_fcfa' => 10000]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('payment_method', 'credit')
            ->assertJsonPath('total_amount', '10000.00');

        // 5% interest on 10000 = 10500 total due
        $this->assertDatabaseHas('debts', [
            'farmer_id' => $farmer->id,
            'principal' => 10000,
            'interest_rate' => 0.05,
            'total_due' => 10500,
            'balance' => 10500,
            'status' => 'open',
        ]);

        $farmer->refresh();
        $this->assertEquals(10500, $farmer->credit_balance_fcfa);
    }

    public function test_credit_transaction_rejected_if_limit_exceeded(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 10000,
            'credit_balance_fcfa' => 0,
        ]);
        $product = Product::factory()->create(['price_fcfa' => 10000]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        // 5% interest on 10000 = 10500 total due > 10000 limit
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['credit_limit']);

        $this->assertDatabaseCount('debts', 0);
    }

    public function test_credit_transaction_allowed_at_exact_boundary(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        // With 0% interest, principal = total_due
        config(['market.interest_rate' => 0]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 10000,
            'credit_balance_fcfa' => 0,
        ]);
        $product = Product::factory()->create(['price_fcfa' => 10000]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('debts', [
            'farmer_id' => $farmer->id,
            'principal' => 10000,
            'interest_rate' => 0,
            'total_due' => 10000,
            'balance' => 10000,
        ]);
    }

    public function test_zero_interest_credit_transaction(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        config(['market.interest_rate' => 0]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 0,
        ]);
        $product = Product::factory()->create(['price_fcfa' => 5000]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('debts', [
            'farmer_id' => $farmer->id,
            'principal' => 10000,
            'interest_rate' => 0,
            'total_due' => 10000,
        ]);
    }

    public function test_multiple_credit_transactions_accumulate_balance(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        config(['market.interest_rate' => 0]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 20000,
            'credit_balance_fcfa' => 0,
        ]);
        $product = Product::factory()->create(['price_fcfa' => 5000]);

        // First transaction: 5000
        $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])->assertCreated();

        $farmer->refresh();
        $this->assertEquals(5000, $farmer->credit_balance_fcfa);

        // Second transaction: 5000 (total 10000, still under 20000 limit)
        $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ])->assertCreated();

        $farmer->refresh();
        $this->assertEquals(10000, $farmer->credit_balance_fcfa);

        // Third transaction: 15000 would exceed limit (10000 + 15000 = 25000 > 20000)
        $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
            ])->assertUnprocessable();
    }
}
