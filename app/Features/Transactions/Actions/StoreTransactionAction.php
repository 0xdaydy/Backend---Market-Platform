<?php

namespace App\Features\Transactions\Actions;

use App\Features\Transactions\Requests\StoreTransactionRequest;
use App\Models\Farmer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\CreditLimitChecker;
use App\Services\TransactionEngine;
use App\Support\TransactionReferenceGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreTransactionAction
{
    public function __invoke(StoreTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();

        $transaction = DB::transaction(function () use ($data, $request) {
            $totalAmount = 0;
            $items = [];

            foreach ($data['items'] as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $unitPrice = $product->price_fcfa;
                $totalPrice = round($itemData['quantity'] * $unitPrice, 2);
                $totalAmount += $totalPrice;

                $items[] = [
                    'product_id' => $product->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ];
            }

            $farmer = Farmer::findOrFail($data['farmer_id']);

            // Credit limit check for credit transactions
            $netDue = null;
            if ($data['payment_method'] === 'credit') {
                $interestRate = (float) config('market.interest_rate', 0);
                $totalDue = round($totalAmount * (1 + $interestRate), 2);

                // Auto-apply credit surplus (negative credit_balance_fcfa)
                $surplus = max(0, -$farmer->credit_balance_fcfa);
                $netDue = max(0, round($totalDue - $surplus, 2));

                if ($netDue > 0 && ! CreditLimitChecker::check($farmer, $netDue)) {
                    throw ValidationException::withMessages([
                        'credit_limit' => [
                            sprintf(
                                'Credit limit exceeded. Available: %s, Required: %s',
                                CreditLimitChecker::availableCredit($farmer),
                                $netDue
                            ),
                        ],
                    ]);
                }

                // Consume surplus
                if ($surplus > 0) {
                    $consumed = min($surplus, $totalDue);
                    $farmer->credit_balance_fcfa = round($farmer->credit_balance_fcfa + $consumed, 2);
                    $farmer->save();
                }
            }

            $transaction = Transaction::create([
                'reference' => TransactionReferenceGenerator::generate(),
                'farmer_id' => $data['farmer_id'],
                'operator_id' => $request->user()->id,
                'payment_method' => $data['payment_method'],
                'total_amount' => $totalAmount,
                'status' => 'completed',
            ]);

            foreach ($items as $item) {
                $item['transaction_id'] = $transaction->id;
                TransactionItem::create($item);
            }

            // Create debt record for credit transactions
            if ($data['payment_method'] === 'credit' && $netDue > 0) {
                $interestRate = (float) config('market.interest_rate', 0);
                TransactionEngine::processCreditTransaction($transaction, $interestRate, $netDue);
            }

            return $transaction->load('items.product');
        });

        return response()->json($transaction, 201);
    }
}
