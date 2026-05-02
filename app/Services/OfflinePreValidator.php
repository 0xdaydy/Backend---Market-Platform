<?php

namespace App\Services;

use App\Models\Farmer;
use App\Models\Product;

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

        $totalAmount = 0;

        foreach ($data['items'] as $item) {
            $product = Product::find($item['product_id']);
            if (! $product) {
                $reasons[] = "Product {$item['product_id']} not found.";
                continue;
            }

            if (isset($item['unit_price']) && (float) $product->price_fcfa !== (float) $item['unit_price']) {
                $reasons[] = sprintf(
                    'Product %s price changed from %s to %s.',
                    $product->name,
                    $item['unit_price'],
                    (float) $product->price_fcfa
                );
            }

            $currentPrice = $product->price_fcfa;
            $totalAmount += $item['quantity'] * $currentPrice;
        }

        if ($data['payment_method'] === 'credit' && empty($reasons)) {
            $interestRate = (float) config('market.interest_rate', 0);
            $totalDue = round($totalAmount * (1 + $interestRate), 2);

            $surplus = max(0, -$farmer->credit_balance_fcfa);
            $netDue = max(0, round($totalDue - $surplus, 2));

            if ($netDue > 0 && ! CreditLimitChecker::check($farmer, $netDue)) {
                $reasons[] = sprintf(
                    'Credit limit exceeded. Available: %s, Required: %s',
                    CreditLimitChecker::availableCredit($farmer),
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
