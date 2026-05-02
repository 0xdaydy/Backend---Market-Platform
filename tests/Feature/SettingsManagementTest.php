<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(User $user): string
    {
        return $user->createToken('test-device')->plainTextToken;
    }

    public function test_can_list_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Setting::factory()->create(['key' => 'interest_rate', 'value' => 0.05]);
        Setting::factory()->create(['key' => 'default_credit_limit', 'value' => 50000]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->getJson('/api/v1/settings');

        $response->assertOk()
            ->assertJsonPath('interest_rate', '0.05')
            ->assertJsonPath('default_credit_limit', '50000');
    }

    public function test_non_admin_cannot_update_settings(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->patchJson('/api/v1/settings', [
                'settings' => [
                    'interest_rate' => 0.1,
                ],
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_update_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Setting::factory()->create(['key' => 'interest_rate', 'value' => 0.05]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->patchJson('/api/v1/settings', [
                'settings' => [
                    'interest_rate' => 0.1,
                    'default_credit_limit' => 75000,
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('interest_rate', '0.1')
            ->assertJsonPath('default_credit_limit', '75000');
    }

    public function test_update_skips_null_values(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Setting::factory()->create(['key' => 'interest_rate', 'value' => 0.05]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->patchJson('/api/v1/settings', [
                'settings' => [
                    'interest_rate' => 0.08,
                    'default_credit_limit' => null,
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('interest_rate', '0.08')
            ->assertJsonMissingPath('default_credit_limit');
    }

    public function test_settings_returns_empty_when_none_exist(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->getJson('/api/v1/settings');

        $response->assertOk()
            ->assertJsonCount(0);
    }

    public function test_update_validates_interest_rate_range(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->patchJson('/api/v1/settings', [
                'settings' => [
                    'interest_rate' => 1.5,
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['settings.interest_rate']);
    }

    public function test_update_validates_credit_limit_non_negative(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->patchJson('/api/v1/settings', [
                'settings' => [
                    'default_credit_limit' => -100,
                ],
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['settings.default_credit_limit']);
    }

    public function test_settings_key_is_unique(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Setting::factory()->create(['key' => 'interest_rate', 'value' => 0.05]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($admin))
            ->patchJson('/api/v1/settings', [
                'settings' => [
                    'interest_rate' => 0.1,
                ],
            ]);

        $response->assertOk();
        $this->assertDatabaseCount('settings', 1);
        $this->assertDatabaseHas('settings', [
            'key' => 'interest_rate',
            'value' => '0.1',
        ]);
    }
}
