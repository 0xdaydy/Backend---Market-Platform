<?php

namespace App\Features\Users\Actions;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class ShowUserAction
{
    use AuthorizesRequests;

    public function __invoke(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json($user->load('supervisor'));
    }
}
