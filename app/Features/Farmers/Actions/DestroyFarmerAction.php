<?php

namespace App\Features\Farmers\Actions;

use App\Models\Farmer;
use Illuminate\Http\JsonResponse;

class DestroyFarmerAction
{
    public function __invoke(Farmer $farmer): JsonResponse
    {
        $farmer->delete();

        return response()->json(['message' => 'Farmer deleted successfully.']);
    }
}
