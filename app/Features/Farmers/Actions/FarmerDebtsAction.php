<?php

namespace App\Features\Farmers\Actions;

use App\Models\Farmer;
use Illuminate\Http\JsonResponse;

class FarmerDebtsAction
{
    public function __invoke(Farmer $farmer): JsonResponse
    {
        return response()->json([
            'farmer_id' => $farmer->id,
            'credit_limit' => $farmer->credit_limit,
            'credit_balance_fcfa' => $farmer->credit_balance_fcfa,
            'available_credit' => max(0, $farmer->credit_limit - $farmer->credit_balance_fcfa),
        ]);
    }
}
