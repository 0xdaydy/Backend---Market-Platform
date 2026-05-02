<?php

namespace App\Features\Transactions\Actions;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class ShowTransactionAction
{
    public function __invoke(Transaction $transaction): JsonResponse
    {
        return response()->json($transaction->load('items.product', 'farmer', 'operator'));
    }
}
