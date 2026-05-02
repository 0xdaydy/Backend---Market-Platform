<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Transaction;

class TransactionEngine
{
    public static function processCreditTransaction(Transaction $transaction, float $interestRate): Debt
    {
        $principal = $transaction->total_amount;
        $totalDue = round($principal * (1 + $interestRate), 2);

        $debt = Debt::create([
            'transaction_id' => $transaction->id,
            'farmer_id' => $transaction->farmer_id,
            'principal' => $principal,
            'interest_rate' => $interestRate,
            'total_due' => $totalDue,
            'amount_repaid' => 0,
            'balance' => $totalDue,
            'status' => 'open',
        ]);

        // Update farmer credit balance
        $farmer = Farmer::find($transaction->farmer_id);
        $farmer->credit_balance_fcfa = round($farmer->credit_balance_fcfa + $totalDue, 2);
        $farmer->save();

        return $debt;
    }
}
