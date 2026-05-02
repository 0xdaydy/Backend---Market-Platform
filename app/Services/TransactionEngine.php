<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Transaction;

class TransactionEngine
{
    public static function processCreditTransaction(Transaction $transaction, float $interestRate, float $netDue = null): ?Debt
    {
        $grossTotalDue = round($transaction->total_amount * (1 + $interestRate), 2);

        $amountToAdd = $netDue ?? $grossTotalDue;

        if ($amountToAdd <= 0) {
            return null;
        }

        $principal = $netDue !== null
            ? round($netDue / (1 + $interestRate), 2)
            : $transaction->total_amount;

        $debt = Debt::create([
            'transaction_id' => $transaction->id,
            'farmer_id' => $transaction->farmer_id,
            'principal' => $principal,
            'interest_rate' => $interestRate,
            'total_due' => $amountToAdd,
            'amount_repaid' => 0,
            'balance' => $amountToAdd,
            'status' => 'open',
        ]);

        // Update farmer credit balance
        $farmer = Farmer::find($transaction->farmer_id);
        $farmer->credit_balance_fcfa = round($farmer->credit_balance_fcfa + $amountToAdd, 2);
        $farmer->save();

        return $debt;
    }
}
