<?php

namespace App\Features\Repayments\Actions;

use App\Features\Repayments\Requests\StoreRepaymentRequest;
use App\Models\CommodityRate;
use App\Models\Farmer;
use App\Models\Repayment;
use App\Services\RepaymentAllocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreRepaymentAction
{
    public function __invoke(StoreRepaymentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $repayment = DB::transaction(function () use ($data, $request) {
            $amount = $data['amount'] ?? 0;

            if ($data['payment_method'] === 'commodity') {
                $rate = CommodityRate::where('commodity_id', $data['commodity_id'])
                    ->where('is_active', true)
                    ->first();

                if (! $rate) {
                    throw ValidationException::withMessages([
                        'commodity_id' => ['No active rate found for this commodity.'],
                    ]);
                }

                $amount = round($data['quantity'] * $rate->rate_fcfa_per_unit, 2);
            }

            $repayment = Repayment::create([
                'farmer_id' => $data['farmer_id'],
                'operator_id' => $request->user()->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'reference' => 'RPY-' . now()->format('Ymd') . '-' . sprintf('%04d', rand(1, 9999)),
            ]);

            $result = RepaymentAllocator::allocate($repayment);

            return $repayment->load(['debts']);
        });

        return response()->json($repayment, 201);
    }
}
