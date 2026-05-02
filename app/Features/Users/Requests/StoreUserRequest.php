<?php

namespace App\Features\Users\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    public function rules(): array
    {
        $user = $this->user();

        $roleRule = Rule::enum(UserRole::class);

        if ($user->isSupervisor()) {
            $roleRule = Rule::enum(UserRole::class)->only([UserRole::Operator]);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', $roleRule],
            'supervisor_id' => [
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
