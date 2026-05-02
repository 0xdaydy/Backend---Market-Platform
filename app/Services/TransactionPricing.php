<?php

namespace App\Services;

use App\Models\Product;

class TransactionPricing
{
    /**
     * Calculate pricing for transaction items and detect mismatches.
     *
     * @param array<int, array{product_id: int, quantity: int, unit_price?: float}> $items
     * @return array{reasons: array<int, string>, total_amount: float, priced_items: array<int, array>}
     */
    public static function calculate(array $items): array
    {
        $reasons = [];
        $totalAmount = 0;
        $pricedItems = [];

        foreach ($items as $item) {
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

            $unitPrice = $product->price_fcfa;
            $totalPrice = round($item['quantity'] * $unitPrice, 2);
            $totalAmount += $totalPrice;

            $pricedItems[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ];
        }

        return [
            'reasons' => $reasons,
            'total_amount' => $totalAmount,
            'priced_items' => $pricedItems,
        ];
    }
}
