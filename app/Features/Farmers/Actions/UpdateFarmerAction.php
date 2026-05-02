<?php

namespace App\Features\Farmers\Actions;

use App\Features\Farmers\Requests\UpdateFarmerRequest;
use App\Models\Farmer;
use Illuminate\Http\JsonResponse;

class UpdateFarmerAction
{
    public function __invoke(UpdateFarmerRequest $request, Farmer $farmer): JsonResponse
    {
        $farmer->update($request->validated());

        return response()->json($farmer->fresh());
    }
}
