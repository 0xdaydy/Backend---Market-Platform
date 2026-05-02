<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_offline_transaction_passes_validation(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 0,
        ]);
        $product = Product::factory()->create(['price_fcfa' => 1000]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions/validate', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('reasons', []);
    }

    public function test_rejects_invalid_farmer(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $product = Product::factory()->create(['price_fcfa' => 1000]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions/validate', [
                'farmer_id' => 999999,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reasons.0', 'Farmer not found.');
    }

    public function test_detects_price_mismatch(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();
        $product = Product::factory()->create(['price_fcfa' => 1500]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions/validate', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reasons.0', 'Product '.$product->name.' price changed from 1000 to 1500.');
    }

    public function test_detects_credit_limit_exceeded(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 5000,
            'credit_balance_fcfa' => 0,
        ]);
        $product = Product::factory()->create(['price_fcfa' => 3000]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions/validate', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 3000],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reasons.0', 'Credit limit exceeded. Available: 5000, Required: 6300');
    }

    public function test_credit_transaction_passes_when_within_limit(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 0,
        ]);
        $product = Product::factory()->create(['price_fcfa' => 1000]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions/validate', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('reasons', []);
    }

    public function test_detects_missing_product(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions/validate', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => 999999, 'quantity' => 2, 'unit_price' => 1000],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reasons.0', 'Product 999999 not found.');
    }

    public function test_allows_price_match_without_unit_price(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();
        $product = Product::factory()->create(['price_fcfa' => 1000]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions/validate', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('valid', true);
    }
}
