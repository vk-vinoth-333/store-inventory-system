<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'unique_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9\-_]+$/',
                Rule::unique('products', 'unique_code'),
            ],
            'price_per_unit' => 'required|numeric|min:0|max:9999999.99',
            'tax_percentage' => 'required|numeric|min:0|max:100',
            'stock_on_hand' => 'required|integer|min:0|max:1000000',
        ];
    }

    public function messages(): array
    {
        return [
            'unique_code.regex' => 'Code may only contain letters, numbers, hyphens and underscores.',
            'unique_code.unique' => 'This product code is already in use.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'unique_code' => strtoupper(trim($this->unique_code ?? '')),
        ]);
    }
}
