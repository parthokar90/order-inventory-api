<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'                    => ['sometimes', 'required', 'exists:categories,id'],
            'name'                           => ['sometimes', 'required', 'string', 'max:255'],
            'description'                    => ['nullable', 'string'],
            'is_active'                      => ['boolean'],
            'variants'                       => ['nullable', 'array'],
            'variants.*.id'                  => ['nullable', 'exists:product_variants,id'],
            'variants.*.sku'                 => ['required_with:variants', 'string'],
            'variants.*.price'               => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.stock'               => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }
}