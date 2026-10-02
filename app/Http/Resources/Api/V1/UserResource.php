<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'email'            => $this->email,
            'is_active'        => $this->is_active,
            'roles'            => $this->getRoleNames(),
            'permissions'      => $this->getAllPermissions()->pluck('name'),
            'customer_profile' => new CustomerResource($this->whenLoaded('customerProfile')),
            'created_at'       => $this->created_at?->toDateTimeString(),
        ];
    }
}