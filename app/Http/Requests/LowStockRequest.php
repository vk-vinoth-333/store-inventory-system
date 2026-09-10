<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LowStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'threshold' => 'sometimes|integer|min:0',
        ];
    }

    public function getThreshold(): int
    {
        return $this->input('threshold', 10);
    }
}