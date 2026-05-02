<?php

namespace App\Features\Farmers\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFarmerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_id' => ['required', 'string', 'max:50', 'unique:farmers'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'village' => ['nullable', 'string', 'max:255'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
