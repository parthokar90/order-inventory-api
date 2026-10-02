<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'            => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'billing_address'  => ['nullable', 'string', 'max:500'],
            'city'             => ['nullable', 'string', 'max:100'],
        ];
    }
}