<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        return [
            'customer_id'                => ['required', 'exists:customers,id'],
            'shipping_address'           => ['required', 'array'],
            'shipping_address.city'      => ['required', 'string'],
            'shipping_address.phone'     => ['required', 'string'],
            'payment_method'             => ['required', 'string', 'in:cod,stripe,sslcommerz,bkash'],
            'currency'                   => ['nullable', 'string', 'size:3'],
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'exists:product_variants,id'],
            'items.*.quantity'           => ['required', 'integer', 'min:1'],
            'items.*.unit_price'         => ['required', 'numeric', 'min:0'],
        ];
    }
}