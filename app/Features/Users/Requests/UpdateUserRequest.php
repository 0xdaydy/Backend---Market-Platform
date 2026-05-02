<?php

namespace App\Features\Users\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $user = $this->user();
        /** @var \App\Models\User $targetUser */
        $targetUser = $this->route('user');

        $roleRule = Rule::enum(UserRole::class);

        if ($user->isSupervisor()) {
            $roleRule = Rule::enum(UserRole::class)->only([UserRole::Operator]);
        }

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($targetUser->id)],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'role' => ['sometimes', 'required', $roleRule],
            'supervisor_id' => [
                'sometimes',
                'nullable',
                'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($user) {
                    if ($user->isSupervisor() && $value !== null && $value != $user->id) {
                        $fail('Supervisors can only assign themselves as supervisor.');
                    }
                },
            ],
        ];
    }
}
