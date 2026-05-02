<?php

namespace App\Models;

class CreditAccount
{
    public function __construct(private Farmer $farmer) {}

    public function balance(): mixed
    {
        return $this->farmer->credit_balance_fcfa;
    }

    public function limit(): mixed
    {
        return $this->farmer->credit_limit;
    }

    public function availableCredit(): float
    {
        return max(0, $this->limit() - $this->balance());
    }

    public function surplus(): float
    {
        return max(0, -$this->balance());
    }

    public function canCharge(float $amount): bool
    {
        return $this->availableCredit() >= $amount;
    }

    public function charge(float $amount): void
    {
        $this->farmer->credit_balance_fcfa = round($this->balance() + $amount, 2);
        $this->farmer->save();
    }

    public function repay(float $amount): void
    {
        $this->farmer->credit_balance_fcfa = round($this->balance() - $amount, 2);
        $this->farmer->save();
    }
}
