<?php

namespace App\Features\Transactions\Actions;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexTransactionAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = Transaction::query()->with('farmer', 'operator');

        if ($request->filled('farmer_id')) {
            $query->where('farmer_id', $request->input('farmer_id'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }
}
