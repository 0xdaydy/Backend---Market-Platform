<?php

namespace App\Features\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->value === 'admin';
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.interest_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'settings.default_credit_limit' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
