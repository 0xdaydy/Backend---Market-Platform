<?php

namespace App\Features\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_fcfa' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
        ];
    }
}
