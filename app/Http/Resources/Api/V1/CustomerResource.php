<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'phone'            => $this->phone,
            'shipping_address' => $this->shipping_address,
            'billing_address'  => $this->billing_address,
            'city'             => $this->city,
        ];
    }
}