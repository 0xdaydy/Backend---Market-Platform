<?php

namespace App\Support;

use App\Models\Transaction;

class TransactionReferenceGenerator
{
    public static function generate(): string
    {
        $date = now()->format('Ymd');
        $sequence = 1;

        do {
            $reference = sprintf('TXN-%s-%04d', $date, $sequence);
            $sequence++;
        } while (Transaction::where('reference', $reference)->exists());

        return $reference;
    }
}
