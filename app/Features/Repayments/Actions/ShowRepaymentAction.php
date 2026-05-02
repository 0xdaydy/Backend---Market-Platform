<?php

namespace App\Features\Repayments\Actions;

use App\Models\Repayment;
use Illuminate\Http\JsonResponse;

class ShowRepaymentAction
{
    public function __invoke(Repayment $repayment): JsonResponse
    {
        return response()->json($repayment->load('debts', 'farmer', 'operator'));
    }
}
