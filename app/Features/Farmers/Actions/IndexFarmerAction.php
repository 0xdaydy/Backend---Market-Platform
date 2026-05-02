<?php

namespace App\Features\Farmers\Actions;

use App\Models\Farmer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexFarmerAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = Farmer::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('card_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }
}
