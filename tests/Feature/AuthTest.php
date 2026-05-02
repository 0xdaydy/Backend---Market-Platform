<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'operator@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Operator,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@example.com',
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user'])
            ->assertJsonPath('user.email', 'operator@example.com')
            ->assertJsonPath('user.role', 'operator');
    }

    public function test_login_with_invalid_credentials_returns_422(): void
    {
        User::factory()->create([
            'email' => 'operator@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Operator,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'operator@example.com',
            'password' => 'wrong-password',
            'device_name' => 'test-device',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_email_password_and_device_name(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'device_name']);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);

        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $response->assertOk()
            ->assertJson(['message' => 'Logged out successfully.']);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertUnauthorized();
    }

    public function test_admin_can_create_supervisor(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isSupervisor());
    }

    public function test_supervisor_can_create_operator(): void
    {
        $supervisor = User::factory()->create([
            'role' => UserRole::Supervisor,
        ]);

        $this->assertTrue($supervisor->isSupervisor());
        $this->assertFalse($supervisor->isAdmin());
    }

    public function test_user_has_self_referencing_supervisor_relation(): void
    {
        $supervisor = User::factory()->create([
            'role' => UserRole::Supervisor,
        ]);

        $operator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => $supervisor->id,
        ]);

        $this->assertTrue($operator->supervisor->is($supervisor));
        $this->assertTrue($supervisor->operators->contains($operator));
    }

    public function test_gates_resolve_correctly(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($admin)->allows('isAdmin'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($admin)->allows('isOperator'));

        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($supervisor)->allows('isSupervisor'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($supervisor)->allows('isAdmin'));

        $this->assertTrue(\Illuminate\Support\Facades\Gate::forUser($operator)->allows('isOperator'));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($operator)->allows('isAdmin'));
    }

    public function test_user_policy_allows_admin_to_view_any_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $other = User::factory()->create(['role' => UserRole::Supervisor]);

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('view', $other));
        $this->assertTrue($admin->can('update', $other));
    }

    public function test_user_policy_allows_supervisor_to_view_only_their_operators(): void
    {
        $supervisor = User::factory()->create(['role' => UserRole::Supervisor]);
        $theirOperator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => $supervisor->id,
        ]);
        $otherOperator = User::factory()->create([
            'role' => UserRole::Operator,
            'supervisor_id' => null,
        ]);

        $this->assertTrue($supervisor->can('viewAny', User::class));
        $this->assertTrue($supervisor->can('view', $theirOperator));
        $this->assertFalse($supervisor->can('view', $otherOperator));
        $this->assertTrue($supervisor->can('update', $theirOperator));
        $this->assertFalse($supervisor->can('update', $otherOperator));
    }

    public function test_operator_cannot_view_other_users(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $other = User::factory()->create(['role' => UserRole::Operator]);

        $this->assertFalse($operator->can('viewAny', User::class));
        $this->assertFalse($operator->can('view', $other));
        $this->assertFalse($operator->can('update', $other));
    }
}
