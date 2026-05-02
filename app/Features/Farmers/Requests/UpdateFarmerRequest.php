<?php

namespace App\Features\Farmers\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFarmerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Farmer $farmer */
        $farmer = $this->route('farmer');

        return [
            'card_id' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('farmers')->ignore($farmer->id)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'village' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credit_limit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
