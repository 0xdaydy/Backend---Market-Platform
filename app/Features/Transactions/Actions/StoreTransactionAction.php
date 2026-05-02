<?php

namespace App\Features\Transactions\Actions;

use App\Features\Transactions\Requests\StoreTransactionRequest;
use App\Models\Farmer;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\TransactionEngine;
use App\Services\TransactionPricing;
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
            $pricing = TransactionPricing::calculate($data['items']);
            $totalAmount = $pricing['total_amount'];
            $items = $pricing['priced_items'];

            $farmer = Farmer::findOrFail($data['farmer_id']);
            $creditPreview = null;

            if ($data['payment_method'] === 'credit') {
                $interestRate = (float) config('market.interest_rate', 0);
                $creditAccount = $farmer->creditAccount();
                $surplus = $creditAccount->surplus();

                // Compute credit values without persisting — single source of truth
                $creditPreview = TransactionEngine::preview($totalAmount, $surplus, $interestRate);

                if ($creditPreview['net_due'] > 0 && ! $creditAccount->canCharge($creditPreview['net_due'])) {
                    throw ValidationException::withMessages([
                        'credit_limit' => [
                            sprintf(
                                'Credit limit exceeded. Available: %s, Required: %s',
                                $creditAccount->availableCredit(),
                                $creditPreview['net_due']
                            ),
                        ],
                    ]);
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

            // Delegate all credit math to the engine: surplus consumption, balance update, debt creation
            if ($data['payment_method'] === 'credit' && $creditPreview !== null) {
                TransactionEngine::processCreditTransaction(
                    $farmer, $transaction, $totalAmount,
                    $farmer->creditAccount()->surplus(),
                    $creditPreview['interest_rate'],
                );
            }

            return $transaction->load('items.product');
        });

        return response()->json($transaction, 201);
    }
}
