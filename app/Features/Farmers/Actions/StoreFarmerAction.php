<?php

namespace App\Features\Farmers\Actions;

use App\Features\Farmers\Requests\StoreFarmerRequest;
use App\Models\Farmer;
use Illuminate\Http\JsonResponse;

class StoreFarmerAction
{
    public function __invoke(StoreFarmerRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! isset($data['credit_limit'])) {
            $data['credit_limit'] = config('market.default_credit_limit', 50000);
        }

        if (! isset($data['credit_balance_fcfa'])) {
            $data['credit_balance_fcfa'] = 0;
        }

        $farmer = Farmer::create($data);

        return response()->json($farmer, 201);
    }
}
