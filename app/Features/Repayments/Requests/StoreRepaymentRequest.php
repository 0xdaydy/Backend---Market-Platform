<?php

namespace App\Features\Repayments\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'farmer_id' => ['required', 'exists:farmers,id'],
            'payment_method' => ['required', 'in:cash,commodity'],
        ];

        if ($this->input('payment_method') === 'commodity') {
            $rules['commodity_id'] = ['required', 'exists:commodities,id'];
            $rules['quantity'] = ['required', 'numeric', 'min:0.01'];
        } else {
            $rules['amount'] = ['required', 'numeric', 'min:0.01'];
        }

        return $rules;
    }
}
