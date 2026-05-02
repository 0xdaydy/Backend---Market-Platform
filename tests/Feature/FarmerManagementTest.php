<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Farmer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarmerManagementTest extends TestCase
{
    use RefreshDatabase;

    // =================== INDEX ===================

    public function test_can_list_farmers(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        Farmer::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/farmers');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_search_farmers_by_card_id(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create(['card_id' => 'F-12345678']);
        Farmer::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/farmers?search=F-12345678');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.card_id', 'F-12345678');
    }

    public function test_can_search_farmers_by_phone(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create(['phone' => '+22507080910']);
        Farmer::factory()->count(2)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/farmers?search=07080910');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.phone', '+22507080910');
    }

    public function test_unauthenticated_cannot_list_farmers(): void
    {
        $response = $this->getJson('/api/v1/farmers');

        $response->assertUnauthorized();
    }

    // =================== SHOW ===================

    public function test_can_show_farmer(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/farmers/'.$farmer->id);

        $response->assertOk()
            ->assertJsonPath('id', $farmer->id)
            ->assertJsonPath('card_id', $farmer->card_id);
    }

    // =================== STORE ===================

    public function test_can_create_farmer_with_explicit_credit_limit(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->postJson('/api/v1/farmers', [
                'card_id' => 'F-99999999',
                'name' => 'John Doe',
                'phone' => '+22507080910',
                'village' => 'Abidjan',
                'credit_limit' => 75000,
            ]);

        $response->assertCreated()
            ->assertJsonPath('card_id', 'F-99999999')
            ->assertJsonPath('credit_limit', '75000.00')
            ->assertJsonPath('credit_balance_fcfa', '0.00');

        $this->assertDatabaseHas('farmers', [
            'card_id' => 'F-99999999',
            'credit_limit' => 75000,
        ]);
    }

    public function test_creates_farmer_with_default_credit_limit_when_not_provided(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->postJson('/api/v1/farmers', [
                'card_id' => 'F-88888888',
                'name' => 'Jane Doe',
            ]);

        $response->assertCreated()
            ->assertJsonPath('card_id', 'F-88888888')
            ->assertJsonPath('credit_limit', '50000.00');
    }

    public function test_cannot_create_farmer_with_duplicate_card_id(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        Farmer::factory()->create(['card_id' => 'F-77777777']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->postJson('/api/v1/farmers', [
                'card_id' => 'F-77777777',
                'name' => 'Duplicate',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['card_id']);
    }

    public function test_store_requires_name_and_card_id(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->postJson('/api/v1/farmers', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['card_id', 'name']);
    }

    // =================== UPDATE ===================

    public function test_can_update_farmer(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create(['name' => 'Old Name']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->putJson('/api/v1/farmers/'.$farmer->id, [
                'name' => 'New Name',
                'credit_limit' => 100000,
            ]);

        $response->assertOk()
            ->assertJsonPath('name', 'New Name')
            ->assertJsonPath('credit_limit', '100000.00');
    }

    public function test_cannot_update_farmer_with_duplicate_card_id(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $farmer1 = Farmer::factory()->create(['card_id' => 'F-11111111']);
        Farmer::factory()->create(['card_id' => 'F-22222222']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->putJson('/api/v1/farmers/'.$farmer1->id, [
                'card_id' => 'F-22222222',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['card_id']);
    }

    // =================== DESTROY ===================

    public function test_can_delete_farmer(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->deleteJson('/api/v1/farmers/'.$farmer->id);

        $response->assertOk()
            ->assertJson(['message' => 'Farmer deleted successfully.']);

        $this->assertDatabaseMissing('farmers', ['id' => $farmer->id]);
    }

    // =================== DEBTS ===================

    public function test_can_get_farmer_debts_summary(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 15000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/farmers/'.$farmer->id.'/debts');

        $response->assertOk()
            ->assertJsonPath('credit_limit', '50000.00')
            ->assertJsonPath('credit_balance_fcfa', '15000.00')
            ->assertJsonPath('available_credit', 35000);
    }

    // =================== TRANSACTIONS ===================

    public function test_can_get_farmer_transactions_placeholder(): void
    {
        $user = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($user))
            ->getJson('/api/v1/farmers/'.$farmer->id.'/transactions');

        $response->assertOk()
            ->assertJsonPath('farmer_id', $farmer->id)
            ->assertJsonPath('transactions', []);
    }
}
