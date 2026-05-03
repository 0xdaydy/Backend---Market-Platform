<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OperatorTestSeeder extends Seeder
{
    public function run(): void
    {
        $supervisor = User::factory()->create([
            'name' => 'Test Supervisor',
            'email' => 'test-supervisor@market.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Supervisor,
        ]);

        $operator = User::factory()->create([
            'name' => 'Test Operator',
            'email' => 'test-operator@market.local',
            'password' => Hash::make('password'),
            'role' => UserRole::Operator,
            'supervisor_id' => $supervisor->id,
        ]);

        $webToken = $operator->createToken('web')->plainTextToken;
        $mobileToken = $operator->createToken('mobile')->plainTextToken;

        $this->command->info('OperatorTestSeeder seeded successfully.');
        $this->command->info("Operator: test-operator@market.local / password");
        $this->command->info("Web token: {$webToken}");
        $this->command->info("Mobile token: {$mobileToken}");
    }
}
