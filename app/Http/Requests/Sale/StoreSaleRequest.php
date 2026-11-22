<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'mobile'])],
            'tendered' => ['required_if:payment_method,cash', 'nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required for checkout.',
            'tendered.required_if' => 'Tendered amount is required for cash payments.',
        ];
    }
}
