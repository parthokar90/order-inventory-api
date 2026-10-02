<?php

namespace App\Http\Requests\Api\V1\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class InventoryIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'       => ['nullable', 'string', 'max:100'],
            'stock_status' => ['nullable', 'string', 'in:all,in_stock,low_stock,out_of_stock'],
            'category_id'  => ['nullable', 'exists:categories,id'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
            'cursor'       => ['nullable', 'string'],
        ];
    }
}