<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(User $user): string
    {
        return $user->createToken('test-device')->plainTextToken;
    }

    // =================== INDEX ===================

    public function test_admin_can_list_all_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonCount(4, 'data'); // admin + 3 others
    }

    public function test_supervisor_can_list_only_their_operators(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        User::factory()->count(2)->create([
            'role' => UserRole::Operator,
            'supervisor_id' => $supervisor->id,
        ]);
        User::factory()->count(2)->create([
            'role' => UserRole::Operator,
            'supervisor_id' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($supervisor))
            ->getJson('/api/v1/users');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_operator_cannot_list_users(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->getJson('/api/v1/users');

        $response->assertForbidden();
    }

    public function test_index_filters_by_role(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->count(2)->create(['role' => UserRole::Supervisor]);
        User::factory()->count(3)->create(['role' => UserRole::Operator]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->getJson('/api/v1/users?role=supervisor');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_index_filters_by_supervisor_id(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        User::factory()->count(2)->create([
            'role' => UserRole::Operator,
            'supervisor_id' => $supervisor->id,
        ]);
        User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->getJson('/api/v1/users?supervisor_id='.$supervisor->id);

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    // =================== SHOW ===================

    public function test_admin_can_view_any_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->getJson('/api/v1/users/'.$user->id);

        $response->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_supervisor_can_view_their_operator(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => $supervisor->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($supervisor))
            ->getJson('/api/v1/users/'.$operator->id);

        $response->assertOk()
            ->assertJsonPath('id', $operator->id);
    }

    public function test_supervisor_cannot_view_other_operators(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $otherOperator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($supervisor))
            ->getJson('/api/v1/users/'.$otherOperator->id);

        $response->assertForbidden();
    }

    public function test_operator_cannot_view_other_users(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $other = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->getJson('/api/v1/users/'.$other->id);

        $response->assertForbidden();
    }

    // =================== STORE ===================

    public function test_admin_can_create_supervisor(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->postJson('/api/v1/users', [
                'name' => 'New Supervisor',
                'email' => 'supervisor@example.com',
                'password' => 'password123',
                'role' => 'supervisor',
            ]);

        $response->assertCreated()
            ->assertJsonPath('role', 'supervisor')
            ->assertJsonPath('supervisor_id', null);

        $this->assertDatabaseHas('users', [
            'email' => 'supervisor@example.com',
            'role' => 'supervisor',
        ]);
    }

    public function test_admin_can_create_operator_with_supervisor_id(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->postJson('/api/v1/users', [
                'name' => 'New Operator',
                'email' => 'operator@example.com',
                'password' => 'password123',
                'role' => 'operator',
                'supervisor_id' => $supervisor->id,
            ]);

        $response->assertCreated()
            ->assertJsonPath('role', 'operator')
            ->assertJsonPath('supervisor_id', $supervisor->id);
    }

    public function test_supervisor_can_create_operator(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($supervisor))
            ->postJson('/api/v1/users', [
                'name' => 'New Operator',
                'email' => 'operator@example.com',
                'password' => 'password123',
                'role' => 'operator',
            ]);

        $response->assertCreated()
            ->assertJsonPath('role', 'operator')
            ->assertJsonPath('supervisor_id', $supervisor->id);
    }

    public function test_supervisor_cannot_create_non_operator(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($supervisor))
            ->postJson('/api/v1/users', [
                'name' => 'New Admin',
                'email' => 'admin@example.com',
                'password' => 'password123',
                'role' => 'admin',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_operator_cannot_create_user(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/users', [
                'name' => 'New User',
                'email' => 'user@example.com',
                'password' => 'password123',
                'role' => 'operator',
            ]);

        $response->assertForbidden();
    }

    // =================== UPDATE ===================

    public function test_admin_can_update_any_user_role_and_supervisor(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->putJson('/api/v1/users/'.$operator->id, [
                'role' => 'supervisor',
                'supervisor_id' => $supervisor->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('role', 'supervisor')
            ->assertJsonPath('supervisor_id', $supervisor->id);
    }

    public function test_supervisor_can_update_their_operator(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => $supervisor->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($supervisor))
            ->putJson('/api/v1/users/'.$operator->id, [
                'name' => 'Updated Name',
            ]);

        $response->assertOk()
            ->assertJsonPath('name', 'Updated Name');
    }

    public function test_supervisor_cannot_update_other_operators(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $otherOperator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($supervisor))
            ->putJson('/api/v1/users/'.$otherOperator->id, [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    }

    public function test_operator_cannot_update_user(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $other = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->putJson('/api/v1/users/'.$other->id, [
                'name' => 'Updated Name',
            ]);

        $response->assertForbidden();
    }

    // =================== DESTROY ===================

    public function test_admin_can_delete_any_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $user = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->deleteJson('/api/v1/users/'.$user->id);

        $response->assertOk()
            ->assertJson(['message' => 'User deleted successfully.']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_supervisor_can_delete_their_operator(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => $supervisor->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($supervisor))
            ->deleteJson('/api/v1/users/'.$operator->id);

        $response->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $operator->id]);
    }

    public function test_supervisor_cannot_delete_other_operators(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $otherOperator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => null,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($supervisor))
            ->deleteJson('/api/v1/users/'.$otherOperator->id);

        $response->assertForbidden();
    }

    public function test_operator_cannot_delete_user(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $other = User::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->deleteJson('/api/v1/users/'.$other->id);

        $response->assertForbidden();
    }
}
