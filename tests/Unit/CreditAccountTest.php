<?php

namespace Tests\Unit;

use App\Models\CreditAccount;
use App\Models\Farmer;
use PHPUnit\Framework\TestCase;

class CreditAccountTest extends TestCase
{
    public function test_balance_returns_farmer_credit_balance(): void
    {
        $farmer = new Farmer(['credit_balance_fcfa' => 15000.50]);
        $account = new CreditAccount($farmer);

        $this->assertEquals('15000.50', $account->balance());
    }

    public function test_limit_returns_farmer_credit_limit(): void
    {
        $farmer = new Farmer(['credit_limit' => 50000.00]);
        $account = new CreditAccount($farmer);

        $this->assertEquals('50000.00', $account->limit());
    }

    public function test_available_credit_when_balance_is_zero(): void
    {
        $farmer = new Farmer(['credit_limit' => 50000, 'credit_balance_fcfa' => 0]);
        $account = new CreditAccount($farmer);

        $this->assertEquals(50000, $account->availableCredit());
    }

    public function test_available_credit_when_balance_is_positive(): void
    {
        $farmer = new Farmer(['credit_limit' => 50000, 'credit_balance_fcfa' => 15000]);
        $account = new CreditAccount($farmer);

        $this->assertEquals(35000, $account->availableCredit());
    }

    public function test_available_credit_is_zero_when_balance_exceeds_limit(): void
    {
        $farmer = new Farmer(['credit_limit' => 50000, 'credit_balance_fcfa' => 60000]);
        $account = new CreditAccount($farmer);

        $this->assertEquals(0, $account->availableCredit());
    }

    public function test_surplus_is_zero_when_balance_is_positive(): void
    {
        $farmer = new Farmer(['credit_balance_fcfa' => 5000]);
        $account = new CreditAccount($farmer);

        $this->assertEquals(0, $account->surplus());
    }

    public function test_surplus_when_balance_is_negative(): void
    {
        $farmer = new Farmer(['credit_balance_fcfa' => -3000]);
        $account = new CreditAccount($farmer);

        $this->assertEquals(3000, $account->surplus());
    }

    public function test_can_charge_when_amount_is_within_available_credit(): void
    {
        $farmer = new Farmer(['credit_limit' => 50000, 'credit_balance_fcfa' => 10000]);
        $account = new CreditAccount($farmer);

        $this->assertTrue($account->canCharge(30000));
        $this->assertTrue($account->canCharge(40000));
        $this->assertFalse($account->canCharge(40001));
    }

    public function test_can_charge_when_amount_is_exactly_at_limit(): void
    {
        $farmer = new Farmer(['credit_limit' => 10000, 'credit_balance_fcfa' => 0]);
        $account = new CreditAccount($farmer);

        $this->assertTrue($account->canCharge(10000));
    }
}
