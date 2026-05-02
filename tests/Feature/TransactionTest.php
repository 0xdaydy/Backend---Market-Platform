<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_cash_transaction_with_snapshot_pricing(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();
        $product = Product::factory()->create(['price_fcfa' => 1500]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
            ]);

        $response->assertCreated()
            ->assertJsonPath('payment_method', 'cash')
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('total_amount', '4500.00')
            ->assertJsonPath('operator_id', $operator->id)
            ->assertJsonPath('items.0.unit_price', '1500.00')
            ->assertJsonPath('items.0.total_price', '4500.00')
            ->assertJsonPath('items.0.quantity', 3);

        $this->assertDatabaseHas('transactions', [
            'farmer_id' => $farmer->id,
            'payment_method' => 'cash',
            'total_amount' => 4500,
        ]);

        $this->assertDatabaseHas('transaction_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 1500,
            'total_price' => 4500,
        ]);
    }

    public function test_transaction_reference_is_unique(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();
        $product = Product::factory()->create();

        $response1 = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response2 = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response1->assertCreated();
        $response2->assertCreated();

        $ref1 = $response1->json('reference');
        $ref2 = $response2->json('reference');

        $this->assertNotEquals($ref1, $ref2);
        $this->assertStringStartsWith('TXN-', $ref1);
        $this->assertStringStartsWith('TXN-', $ref2);
    }

    public function test_snapshot_pricing_is_immutable(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();
        $product = Product::factory()->create(['price_fcfa' => 1500]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);

        $response->assertCreated();

        // Update product price after transaction
        $product->update(['price_fcfa' => 2000]);

        $transaction = Transaction::with('items')->find($response->json('id'));
        $this->assertEquals(1500, $transaction->items->first()->unit_price);
        $this->assertEquals(3000, $transaction->items->first()->total_price);
    }

    public function test_cannot_create_transaction_without_items(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'cash',
                'items' => [],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_cannot_create_transaction_with_invalid_product(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmer->id,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => 99999, 'quantity' => 1],
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_can_list_transactions(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        Transaction::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/transactions');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_transactions_by_payment_method(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        Transaction::factory()->count(2)->create(['payment_method' => 'cash']);
        Transaction::factory()->count(3)->create(['payment_method' => 'credit']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/transactions?payment_method=cash');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_show_transaction(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $transaction = Transaction::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/transactions/'.$transaction->id);

        $response->assertOk()
            ->assertJsonPath('id', $transaction->id)
            ->assertJsonPath('reference', $transaction->reference);
    }
}
