<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\Farmer;
use App\Models\Transaction;

class TransactionEngine
{
    /**
     * Preview the credit computation without persisting anything.
     * Used for fail-fast validation before creating the transaction.
     *
     * @return array{total_due: float, net_due: float, consumed_surplus: float, interest_rate: float}
     */
    public static function preview(float $totalAmount, float $surplus, float $interestRate): array
    {
        $totalDue = round($totalAmount * (1 + $interestRate), 2);
        $consumedSurplus = min($surplus, $totalDue);
        $netDue = max(0, round($totalDue - $consumedSurplus, 2));

        return [
            'total_due' => $totalDue,
            'net_due' => $netDue,
            'consumed_surplus' => $consumedSurplus,
            'interest_rate' => $interestRate,
        ];
    }

    /**
     * Process a credit transaction: consume surplus, create debt, update farmer balance.
     *
     * All interest/principal math is computed here — callers should NOT duplicate the formulas.
     */
    public static function processCreditTransaction(
        Farmer $farmer,
        Transaction $transaction,
        float $totalAmount,
        float $surplus,
        float $interestRate,
    ): ?Debt {
        $preview = self::preview($totalAmount, $surplus, $interestRate);
        $consumedSurplus = $preview['consumed_surplus'];
        $netDue = $preview['net_due'];

        // Consume surplus
        if ($consumedSurplus > 0) {
            $farmer->credit_balance_fcfa = $farmer->creditAccount()->charge($consumedSurplus);
            $farmer->save();
        }

        if ($netDue <= 0) {
            return null;
        }

        // Charge net due to farmer
        $farmer->credit_balance_fcfa = $farmer->creditAccount()->charge($netDue);
        $farmer->save();

        $principal = round($netDue / (1 + $interestRate), 2);

        return Debt::create([
            'transaction_id' => $transaction->id,
            'farmer_id' => $farmer->id,
            'principal' => $principal,
            'interest_rate' => $interestRate,
            'total_due' => $netDue,
            'amount_repaid' => 0,
            'balance' => $netDue,
            'status' => 'open',
        ]);
    }
}
