<?php

namespace App\Services;

use App\Models\Debt;
use App\Models\DebtRepayment;
use App\Models\Repayment;
use Illuminate\Support\Facades\DB;

class RepaymentAllocator
{
    /**
     * Allocate a repayment across open debts using FIFO ordering.
     *
     * @return array{allocated: array<int, float>, remaining: float}
     */
    public static function allocate(Repayment $repayment): array
    {
        $remaining = $repayment->amount;
        $allocations = [];

        $debts = Debt::where('farmer_id', $repayment->farmer_id)
            ->whereIn('status', ['open', 'partially_paid'])
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->get();

        foreach ($debts as $debt) {
            if ($remaining <= 0) {
                break;
            }

            $toApply = min($remaining, $debt->balance);
            $remaining = round($remaining - $toApply, 2);

            $newBalance = round($debt->balance - $toApply, 2);
            $newRepaid = round($debt->amount_repaid + $toApply, 2);

            $debt->update([
                'balance' => $newBalance,
                'amount_repaid' => $newRepaid,
                'status' => $newBalance <= 0 ? 'closed' : 'partially_paid',
            ]);

            DebtRepayment::create([
                'debt_id' => $debt->id,
                'repayment_id' => $repayment->id,
                'amount_applied' => $toApply,
            ]);

            $allocations[$debt->id] = $toApply;
        }

        // Update farmer credit balance
        $repayment->farmer->creditAccount()->repay($repayment->amount);

        return [
            'allocated' => $allocations,
            'remaining' => $remaining,
        ];
    }
}
