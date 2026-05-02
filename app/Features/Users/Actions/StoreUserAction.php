<?php

namespace App\Features\Users\Actions;

use App\Features\Users\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class StoreUserAction
{
    public function __invoke(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->user()->isSupervisor()) {
            $data['supervisor_id'] = $request->user()->id;
        }

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json($user->load('supervisor'), 201);
    }
}
