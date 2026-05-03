<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorTestSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_can_login_after_seed(): void
    {
        $this->seed(\Database\Seeders\OperatorTestSeeder::class);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test-operator@market.local',
            'password' => 'password',
            'device_name' => 'web',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user'])
            ->assertJsonPath('user.email', 'test-operator@market.local')
            ->assertJsonPath('user.role', 'operator');
    }

    public function test_web_token_created_after_seed(): void
    {
        $this->seed(\Database\Seeders\OperatorTestSeeder::class);

        $operator = User::where('email', 'test-operator@market.local')->first();

        $this->assertNotNull($operator);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $operator->id,
            'name' => 'web',
        ]);
    }

    public function test_mobile_token_created_after_seed(): void
    {
        $this->seed(\Database\Seeders\OperatorTestSeeder::class);

        $operator = User::where('email', 'test-operator@market.local')->first();

        $this->assertNotNull($operator);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $operator->id,
            'name' => 'mobile',
        ]);
    }

    public function test_web_token_authenticates_protected_route(): void
    {
        $this->seed(\Database\Seeders\OperatorTestSeeder::class);

        $operator = User::where('email', 'test-operator@market.local')->first();
        $token = $operator->createToken('web-test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/categories');

        $response->assertOk();
    }

    public function test_operator_has_supervisor_and_correct_role(): void
    {
        $this->seed(\Database\Seeders\OperatorTestSeeder::class);

        $operator = User::where('email', 'test-operator@market.local')->first();

        $this->assertNotNull($operator);
        $this->assertTrue($operator->isOperator());
        $this->assertNotNull($operator->supervisor);
        $this->assertEquals('test-supervisor@market.local', $operator->supervisor->email);
        $this->assertTrue($operator->supervisor->isSupervisor());
    }
}
