<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentCallbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'string'],
            'status'         => ['required', 'string', 'in:completed,failed'],
            'payload'        => ['nullable', 'array'],
        ];
    }
}