<?php

namespace App\Features\Users\Actions;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndexUserAction
{
    use AuthorizesRequests;

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $user = $request->user();

        $query = User::query();

        if ($user->isSupervisor()) {
            $query->where('supervisor_id', $user->id);
        }

        if ($request->has('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->has('supervisor_id')) {
            $query->where('supervisor_id', $request->input('supervisor_id'));
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }
}
