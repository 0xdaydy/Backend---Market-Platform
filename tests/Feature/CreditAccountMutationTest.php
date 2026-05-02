<?php

namespace Tests\Feature;

use App\Models\Farmer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditAccountMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_charge_increases_balance(): void
    {
        $farmer = Farmer::factory()->create(['credit_balance_fcfa' => 5000]);
        $account = $farmer->creditAccount();

        $account->charge(2500);

        $farmer->refresh();
        $this->assertEquals(7500, $farmer->credit_balance_fcfa);
    }

    public function test_repay_decreases_balance(): void
    {
        $farmer = Farmer::factory()->create(['credit_balance_fcfa' => 10000]);
        $account = $farmer->creditAccount();

        $account->repay(3000);

        $farmer->refresh();
        $this->assertEquals(7000, $farmer->credit_balance_fcfa);
    }

    public function test_repay_can_create_surplus(): void
    {
        $farmer = Farmer::factory()->create(['credit_balance_fcfa' => 5000]);
        $account = $farmer->creditAccount();

        $account->repay(8000);

        $farmer->refresh();
        $this->assertEquals(-3000, $farmer->credit_balance_fcfa);
        $this->assertEquals(3000, $account->surplus());
    }
}
