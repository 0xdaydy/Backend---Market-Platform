<?php

namespace App\Features\Repayments;

use App\Models\Repayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepaymentController
{
    public function index(Request $request): JsonResponse
    {
        $query = Repayment::query()->with('farmer', 'operator');

        if ($request->filled('farmer_id')) {
            $query->where('farmer_id', $request->input('farmer_id'));
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    public function show(Repayment $repayment): JsonResponse
    {
        return response()->json($repayment->load('debts', 'farmer', 'operator'));
    }
}
