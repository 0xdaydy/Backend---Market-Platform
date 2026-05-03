<?php

namespace App\Features\Users;

use App\Features\Users\Requests\StoreUserRequest;
use App\Features\Users\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class UserController
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
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

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json($user->load('supervisor'));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->user()->isSupervisor()) {
            $data['supervisor_id'] = $request->user()->id;
        }

        $user = User::create($data);

        return response()->json($user->load('supervisor'), 201);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if ($request->user()->isSupervisor()) {
            $data['supervisor_id'] = $request->user()->id;
        }

        $user->update($data);

        return response()->json($user->fresh('supervisor'));
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }
}
