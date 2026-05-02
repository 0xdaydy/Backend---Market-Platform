<?php

namespace App\Features\Users\Actions;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class DestroyUserAction
{
    use AuthorizesRequests;

    public function __invoke(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
