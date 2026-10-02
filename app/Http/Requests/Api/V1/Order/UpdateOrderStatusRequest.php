<?php

namespace App\Http\Requests\Api\V1\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    Order::STATUS_PENDING,
                    Order::STATUS_PROCESSING,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_CANCELLED,
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom validation messages for better API error feedback.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'The order status field is mandatory.',
            'status.in'       => 'The provided order status is invalid.',
            'notes.max'       => 'Status update notes cannot exceed 500 characters.',
        ];
    }
}