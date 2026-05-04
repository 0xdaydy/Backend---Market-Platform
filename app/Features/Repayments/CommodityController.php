<?php

namespace App\Features\Repayments;

use App\Models\Commodity;
use Illuminate\Http\JsonResponse;

class CommodityController
{
    public function index(): JsonResponse
    {
        $commodities = Commodity::withWhereHas(
            'rates',
            fn ($query) => $query->where('is_active', true)
        )->get()->map(fn ($commodity) => [
            'id' => $commodity->id,
            'name' => $commodity->name,
            'unit' => $commodity->unit,
            'rate_fcfa_per_unit' => $commodity->rates->first()->rate_fcfa_per_unit,
        ]);

        return response()->json(['data' => $commodities]);
    }
}
