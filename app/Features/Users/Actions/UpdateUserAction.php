<?php

namespace App\Features\Users\Actions;

use App\Features\Users\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UpdateUserAction
{
    public function __invoke(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if ($request->user()->isSupervisor()) {
            $data['supervisor_id'] = $request->user()->id;
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return response()->json($user->fresh('supervisor'));
    }
}
