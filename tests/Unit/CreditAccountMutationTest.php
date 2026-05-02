<?php

namespace Tests\Unit;

use App\Models\CreditAccount;
use App\Models\Farmer;
use PHPUnit\Framework\TestCase;

class CreditAccountMutationTest extends TestCase
{
    public function test_charge_returns_increased_balance(): void
    {
        $farmer = new Farmer(['credit_balance_fcfa' => 5000]);
        $account = new CreditAccount($farmer);

        $newBalance = $account->charge(2500);

        $this->assertEquals(7500, $newBalance);
    }

    public function test_charge_does_not_mutate_farmer(): void
    {
        $farmer = new Farmer(['credit_balance_fcfa' => 5000]);
        $account = new CreditAccount($farmer);

        $account->charge(2500);

        $this->assertEquals(5000, $farmer->credit_balance_fcfa);
    }

    public function test_repay_returns_decreased_balance(): void
    {
        $farmer = new Farmer(['credit_balance_fcfa' => 10000]);
        $account = new CreditAccount($farmer);

        $newBalance = $account->repay(3000);

        $this->assertEquals(7000, $newBalance);
    }

    public function test_repay_can_return_surplus(): void
    {
        $farmer = new Farmer(['credit_balance_fcfa' => 5000]);
        $account = new CreditAccount($farmer);

        $newBalance = $account->repay(8000);

        $this->assertEquals(-3000, $newBalance);
    }

    public function test_repay_does_not_mutate_farmer(): void
    {
        $farmer = new Farmer(['credit_balance_fcfa' => 10000]);
        $account = new CreditAccount($farmer);

        $account->repay(3000);

        $this->assertEquals(10000, $farmer->credit_balance_fcfa);
    }
}
