<?php

namespace App\Features\Farmers;

use App\Features\Farmers\Requests\StoreFarmerRequest;
use App\Features\Farmers\Requests\UpdateFarmerRequest;
use App\Models\Farmer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmerController
{
    public function index(Request $request): JsonResponse
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

    public function show(Farmer $farmer): JsonResponse
    {
        return response()->json($farmer);
    }

    public function store(StoreFarmerRequest $request): JsonResponse
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

    public function update(UpdateFarmerRequest $request, Farmer $farmer): JsonResponse
    {
        $farmer->update($request->validated());

        return response()->json($farmer->fresh());
    }

    public function destroy(Farmer $farmer): JsonResponse
    {
        $farmer->delete();

        return response()->json(['message' => 'Farmer deleted successfully.']);
    }

    public function debts(Farmer $farmer): JsonResponse
    {
        $account = $farmer->creditAccount();

        return response()->json([
            'farmer_id' => $farmer->id,
            'credit_limit' => $account->limit(),
            'credit_balance_fcfa' => $account->balance(),
            'available_credit' => $account->availableCredit(),
        ]);
    }

    public function transactions(Farmer $farmer): JsonResponse
    {
        return response()->json([
            'farmer_id' => $farmer->id,
            'transactions' => [],
        ]);
    }
}
