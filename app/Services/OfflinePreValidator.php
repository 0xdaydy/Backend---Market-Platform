<?php

namespace App\Services;

use App\Models\Farmer;

class OfflinePreValidator
{
    /**
     * Validate an offline transaction against current server state.
     *
     * @param array<string, mixed> $data
     * @return array{valid: bool, reasons: array<int, string>}
     */
    public static function validate(array $data): array
    {
        $reasons = [];

        $farmer = Farmer::find($data['farmer_id']);
        if (! $farmer) {
            $reasons[] = 'Farmer not found.';

            return ['valid' => false, 'reasons' => $reasons];
        }

        $pricing = TransactionPricing::calculate($data['items']);
        $reasons = array_merge($reasons, $pricing['reasons']);
        $totalAmount = $pricing['total_amount'];

        if ($data['payment_method'] === 'credit' && empty($reasons)) {
            $interestRate = (float) config('market.interest_rate', 0);
            $totalDue = round($totalAmount * (1 + $interestRate), 2);

            $creditAccount = $farmer->creditAccount();
            $surplus = $creditAccount->surplus();
            $netDue = max(0, round($totalDue - $surplus, 2));

            if ($netDue > 0 && ! $creditAccount->canCharge($netDue)) {
                $reasons[] = sprintf(
                    'Credit limit exceeded. Available: %s, Required: %s',
                    $creditAccount->availableCredit(),
                    $netDue
                );
            }
        }

        return [
            'valid' => empty($reasons),
            'reasons' => $reasons,
        ];
    }
}
