<?php

namespace App\Features\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'price_fcfa' => ['sometimes', 'required', 'numeric', 'min:0'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
        ];
    }
}
