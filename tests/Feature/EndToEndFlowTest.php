<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Commodity;
use App\Models\CommodityRate;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_market_flow(): void
    {
        // 1. Setup: Admin creates supervisor and operator
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => $supervisor->id,
        ]);

        // 2. Admin creates catalog
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price_fcfa' => 5000,
        ]);

        // 3. Operator creates farmer
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/farmers', [
                'name' => 'Moussa Traore',
                'card_id' => 'FARM-001',
                'phone' => '+22370000001',
            ]);
        $response->assertCreated();
        $farmerId = $response->json('id');

        // 4. Operator creates cash transaction
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmerId,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
            ]);
        $response->assertCreated()
            ->assertJsonPath('total_amount', '10000.00')
            ->assertJsonPath('payment_method', 'cash');

        // 5. Verify no debt for cash transaction
        $this->assertDatabaseCount('debts', 0);

        // 6. Operator creates credit transaction
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmerId,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
            ]);
        $response->assertCreated();
        $creditTxId = $response->json('id');

        // 7. Verify debt created with interest
        $this->assertDatabaseHas('debts', [
            'transaction_id' => $creditTxId,
            'principal' => '15000.00',
            'total_due' => '15750.00',
            'balance' => '15750.00',
            'status' => 'open',
        ]);

        // 8. Verify farmer credit balance updated
        $farmer = Farmer::find($farmerId);
        $this->assertEquals(15750, $farmer->credit_balance_fcfa);

        // 9. Operator makes partial repayment
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmerId,
                'amount' => 5000,
                'payment_method' => 'cash',
            ]);
        $response->assertCreated();

        // 10. Verify debt partially paid
        $this->assertDatabaseHas('debts', [
            'transaction_id' => $creditTxId,
            'balance' => '10750.00',
            'amount_repaid' => '5000.00',
            'status' => 'partially_paid',
        ]);

        // 11. Operator makes full repayment with overpayment
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmerId,
                'amount' => 12000,
                'payment_method' => 'cash',
            ]);
        $response->assertCreated();

        // 12. Verify debt closed and surplus stored
        $this->assertDatabaseHas('debts', [
            'transaction_id' => $creditTxId,
            'balance' => '0.00',
            'status' => 'closed',
        ]);

        $farmer->refresh();
        $this->assertEquals(-1250, $farmer->credit_balance_fcfa); // 12000 - 10750 = 1250 surplus

        // 13. Setup commodity for repayment
        $commodity = Commodity::factory()->create(['name' => 'Maize', 'unit' => 'kg']);
        CommodityRate::factory()->create([
            'commodity_id' => $commodity->id,
            'rate_fcfa_per_unit' => 500,
            'is_active' => true,
        ]);

        // 14. Create new credit transaction to test surplus auto-apply
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions', [
                'farmer_id' => $farmerId,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);
        $response->assertCreated();

        // 15. Verify surplus was consumed (1250 surplus vs 5250 total due = 4000 net)
        $farmer->refresh();
        $this->assertEquals(4000, $farmer->credit_balance_fcfa);

        // 16. Make commodity repayment
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmerId,
                'payment_method' => 'commodity',
                'commodity_id' => $commodity->id,
                'quantity' => 10, // 10 * 500 = 5000 FCFA
            ]);
        $response->assertCreated()
            ->assertJsonPath('amount', '5000.00')
            ->assertJsonPath('payment_method', 'commodity');

        // 17. Verify offline validation works (credit limit should be exceeded for large tx)
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/transactions/validate', [
                'farmer_id' => $farmerId,
                'payment_method' => 'credit',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 5000],
                ],
            ]);
        $response->assertUnprocessable()
            ->assertJsonPath('valid', false)
            ->assertJsonPath('reasons.0', 'Credit limit exceeded. Available: 51000, Required: 51500');

        // 18. Verify settings can be read
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->getJson('/api/v1/settings');
        $response->assertOk();
    }

    public function test_admin_user_management_flow(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        // Create supervisor
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->postJson('/api/v1/users', [
                'name' => 'New Supervisor',
                'email' => 'supervisor2@market.local',
                'password' => 'password',
                'role' => 'supervisor',
            ]);
        $response->assertCreated();
        $supervisorId = $response->json('id');

        // Create operator under supervisor
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->postJson('/api/v1/users', [
                'name' => 'New Operator',
                'email' => 'operator2@market.local',
                'password' => 'password',
                'role' => 'operator',
                'supervisor_id' => $supervisorId,
            ]);
        $response->assertCreated()
            ->assertJsonPath('role', 'operator')
            ->assertJsonPath('supervisor_id', $supervisorId);

        // Verify operator listing is restricted (covered in UserManagementTest)
        $this->assertDatabaseHas('users', [
            'email' => 'operator2@market.local',
            'role' => 'operator',
            'supervisor_id' => $supervisorId,
        ]);
    }
}
