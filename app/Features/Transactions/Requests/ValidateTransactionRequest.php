<?php

namespace App\Features\Transactions\Requests;

class ValidateTransactionRequest extends StoreTransactionRequest
{
    public function rules(): array
    {
        return [
            'farmer_id' => ['required', 'integer'],
            'payment_method' => ['required', 'in:cash,credit,commodity'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
