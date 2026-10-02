<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'is_active'   => $this->is_active,
            'category'    => new CategoryResource($this->whenLoaded('category')),
            'variants'    => $this->whenLoaded('variants', function () {
                return $this->variants->map(function ($variant) {
                    return [
                        'id'                  => $variant->id,
                        'sku'                 => $variant->sku,
                        'price'               => $variant->price,
                        'stock'               => $variant->inventory?->quantity ?? 0,
                        'low_stock_threshold' => $variant->inventory?->low_stock_threshold ?? 5,
                    ];
                });
            }),
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}