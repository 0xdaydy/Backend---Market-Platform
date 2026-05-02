<?php

namespace App\Features\Auth\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
