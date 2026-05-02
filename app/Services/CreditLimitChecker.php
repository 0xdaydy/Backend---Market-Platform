<?php

namespace App\Services;

use App\Models\Farmer;

class CreditLimitChecker
{
    public static function check(Farmer $farmer, float $amount): bool
    {
        $availableCredit = $farmer->credit_limit - $farmer->credit_balance_fcfa;

        return $availableCredit >= $amount;
    }

    public static function availableCredit(Farmer $farmer): float
    {
        return max(0, $farmer->credit_limit - $farmer->credit_balance_fcfa);
    }
}
