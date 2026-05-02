<?php

namespace App\Features\Farmers\Actions;

use App\Models\Farmer;
use Illuminate\Http\JsonResponse;

class FarmerTransactionsAction
{
    public function __invoke(Farmer $farmer): JsonResponse
    {
        return response()->json([
            'farmer_id' => $farmer->id,
            'transactions' => [], // placeholder for future transaction relation
        ]);
    }
}
