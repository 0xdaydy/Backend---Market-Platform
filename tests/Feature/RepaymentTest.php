<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepaymentTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser(User $user): string
    {
        return $user->createToken('test-device')->plainTextToken;
    }

    public function test_full_repayment_closes_single_debt(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 10000,
        ]);

        $transaction = Transaction::factory()->create([
            'farmer_id' => $farmer->id,
            'total_amount' => 10000,
            'payment_method' => 'credit',
        ]);

        $debt = Debt::factory()->create([
            'transaction_id' => $transaction->id,
            'farmer_id' => $farmer->id,
            'principal' => 10000,
            'interest_rate' => 0,
            'total_due' => 10000,
            'balance' => 10000,
            'amount_repaid' => 0,
            'status' => 'open',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmer->id,
                'amount' => 10000,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();

        $debt->refresh();
        $this->assertEquals('closed', $debt->status);
        $this->assertEquals(0, $debt->balance);
        $this->assertEquals(10000, $debt->amount_repaid);

        $farmer->refresh();
        $this->assertEquals(0, $farmer->credit_balance_fcfa);

        $this->assertDatabaseHas('debt_repayment', [
            'debt_id' => $debt->id,
            'amount_applied' => 10000,
        ]);
    }

    public function test_partial_repayment_updates_status_to_partially_paid(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 10000,
        ]);

        $transaction = Transaction::factory()->create([
            'farmer_id' => $farmer->id,
            'total_amount' => 10000,
            'payment_method' => 'credit',
        ]);

        $debt = Debt::factory()->create([
            'transaction_id' => $transaction->id,
            'farmer_id' => $farmer->id,
            'principal' => 10000,
            'interest_rate' => 0,
            'total_due' => 10000,
            'balance' => 10000,
            'amount_repaid' => 0,
            'status' => 'open',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmer->id,
                'amount' => 3000,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();

        $debt->refresh();
        $this->assertEquals('partially_paid', $debt->status);
        $this->assertEquals(7000, $debt->balance);
        $this->assertEquals(3000, $debt->amount_repaid);

        $farmer->refresh();
        $this->assertEquals(7000, $farmer->credit_balance_fcfa);
    }

    public function test_fifo_allocation_across_multiple_debts(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 25000,
        ]);

        // Create 3 debts with different created_at times
        $tx1 = Transaction::factory()->create([
            'farmer_id' => $farmer->id,
            'total_amount' => 5000,
            'payment_method' => 'credit',
        ]);
        $debt1 = Debt::factory()->create([
            'transaction_id' => $tx1->id,
            'farmer_id' => $farmer->id,
            'principal' => 5000,
            'total_due' => 5000,
            'balance' => 5000,
            'status' => 'open',
            'created_at' => now()->subDays(3),
        ]);

        $tx2 = Transaction::factory()->create([
            'farmer_id' => $farmer->id,
            'total_amount' => 10000,
            'payment_method' => 'credit',
        ]);
        $debt2 = Debt::factory()->create([
            'transaction_id' => $tx2->id,
            'farmer_id' => $farmer->id,
            'principal' => 10000,
            'total_due' => 10000,
            'balance' => 10000,
            'status' => 'open',
            'created_at' => now()->subDays(2),
        ]);

        $tx3 = Transaction::factory()->create([
            'farmer_id' => $farmer->id,
            'total_amount' => 10000,
            'payment_method' => 'credit',
        ]);
        $debt3 = Debt::factory()->create([
            'transaction_id' => $tx3->id,
            'farmer_id' => $farmer->id,
            'principal' => 10000,
            'total_due' => 10000,
            'balance' => 10000,
            'status' => 'open',
            'created_at' => now()->subDays(1),
        ]);

        // Repay 12000 - should close debt1 (5000), partially pay debt2 (7000)
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmer->id,
                'amount' => 12000,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();

        $debt1->refresh();
        $debt2->refresh();
        $debt3->refresh();

        $this->assertEquals('closed', $debt1->status);
        $this->assertEquals(0, $debt1->balance);

        $this->assertEquals('partially_paid', $debt2->status);
        $this->assertEquals(3000, $debt2->balance);

        $this->assertEquals('open', $debt3->status);
        $this->assertEquals(10000, $debt3->balance);

        $farmer->refresh();
        $this->assertEquals(13000, $farmer->credit_balance_fcfa);
    }

    public function test_overpayment_is_tracked_as_remaining(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 5000,
        ]);

        $tx = Transaction::factory()->create([
            'farmer_id' => $farmer->id,
            'total_amount' => 5000,
            'payment_method' => 'credit',
        ]);
        $debt = Debt::factory()->create([
            'transaction_id' => $tx->id,
            'farmer_id' => $farmer->id,
            'principal' => 5000,
            'total_due' => 5000,
            'balance' => 5000,
            'status' => 'open',
        ]);

        // Pay 8000 when only 5000 is owed
        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmer->id,
                'amount' => 8000,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();

        $debt->refresh();
        $this->assertEquals('closed', $debt->status);
        $this->assertEquals(0, $debt->balance);

        $farmer->refresh();
        // Excess 3000 stored as credit surplus (negative balance)
        $this->assertEquals(-3000, $farmer->credit_balance_fcfa);
    }

    public function test_repayment_creates_immutable_audit_trail(): void
    {
        $operator = User::factory()->create(['role' => UserRole::Operator]);
        $farmer = Farmer::factory()->create([
            'credit_limit' => 50000,
            'credit_balance_fcfa' => 10000,
        ]);

        $tx = Transaction::factory()->create([
            'farmer_id' => $farmer->id,
            'total_amount' => 10000,
            'payment_method' => 'credit',
        ]);
        $debt = Debt::factory()->create([
            'transaction_id' => $tx->id,
            'farmer_id' => $farmer->id,
            'principal' => 10000,
            'total_due' => 10000,
            'balance' => 10000,
            'status' => 'open',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->actingAsUser($operator))
            ->postJson('/api/v1/repayments', [
                'farmer_id' => $farmer->id,
                'amount' => 5000,
                'payment_method' => 'cash',
            ]);

        $response->assertCreated();

        $repaymentId = $response->json('id');

        $this->assertDatabaseHas('debt_repayment', [
            'debt_id' => $debt->id,
            'repayment_id' => $repaymentId,
            'amount_applied' => 5000,
        ]);
    }
}
