<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StockMovementStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'string', 'in:purchase,adjustment,return,opening'],
            'quantity' => ['required', 'numeric', 'not_in:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}
