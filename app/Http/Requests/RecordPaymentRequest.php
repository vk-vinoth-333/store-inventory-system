<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01|max:9999999.99',
            'type' => 'required|in:due_payment,credit,refund',
            'notes' => 'nullable|string|max:500',
            'order_id' => 'nullable|exists:orders,id',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Please enter a payment amount.',
            'amount.min' => 'Payment amount must be at least ₹0.01.',
            'type.required' => 'Please select a payment type.',
            'type.in' => 'Invalid payment type selected.',
        ];
    }
}
