<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Core Product Fields
            'name'                     => ['required', 'string', 'max:255'],
            'category_id'              => ['required', 'exists:categories,id'],
            'is_active'                => ['required', 'boolean'],
            'description'              => ['nullable', 'string'],
            
            // Simple Product Specific (Variants na thakle eigula lagbe)
            'price'                    => ['required_without:variants', 'nullable', 'numeric', 'min:0'],
            'quantity'                 => ['required_without:variants', 'nullable', 'integer', 'min:0'],
            'sku'                      => ['nullable', 'string', 'unique:product_variants,sku'],

            // Global/Product Level Attributes (Optional array of attribute_value_ids)
            'attribute_value_ids'      => ['nullable', 'array'],
            'attribute_value_ids.*'    => ['exists:attribute_values,id'],

            // Variants Array (Optional)
            'variants'                 => ['nullable', 'array', 'min:1'],
            'variants.*.sku'           => ['required_with:variants', 'string', 'unique:product_variants,sku'],
            'variants.*.price'         => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price'    => ['nullable', 'numeric', 'min:0'],
            'variants.*.quantity'      => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => ['exists:attribute_values,id'],
        ];
    }
}