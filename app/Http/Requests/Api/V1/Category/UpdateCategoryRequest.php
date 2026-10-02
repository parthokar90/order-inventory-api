<?php

namespace App\Http\Requests\Api\V1\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:categories,id', "different:{$categoryId}"],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.different' => 'A category cannot be its own parent.',
        ];
    }
}