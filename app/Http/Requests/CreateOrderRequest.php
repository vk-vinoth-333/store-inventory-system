<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_email' => 'required|email',
            'customer_name' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'amount_given' => 'sometimes|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_email.required' => 'Customer email is required',
            'customer_email.email' => 'Please provide a valid email address',
            'customer_name.required' => 'Customer name is required',
            'items.required' => 'At least one item is required',
            'items.*.product_id.required' => 'Product ID is required',
            'items.*.product_id.exists' => 'Product does not exist',
            'items.*.quantity.required' => 'Quantity is required',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            'amount_given.numeric' => 'Amount given must be a number',
            'amount_given.min' => 'Amount given cannot be negative',
        ];
    }
}
