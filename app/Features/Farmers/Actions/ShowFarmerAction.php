<?php

namespace App\Features\Farmers\Actions;

use App\Models\Farmer;
use Illuminate\Http\JsonResponse;

class ShowFarmerAction
{
    public function __invoke(Farmer $farmer): JsonResponse
    {
        return response()->json($farmer);
    }
}
