<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quantity = $this->inventory->quantity ?? 0;
        $threshold = $this->inventory->low_stock_threshold ?? 5;

        $status = 'in_stock';
        if ($quantity <= 0) {
            $status = 'out_of_stock';
        } elseif ($quantity <= $threshold) {
            $status = 'low_stock';
        }

        return [
            'variant_id'    => $this->id,
            'sku'           => $this->sku,
            'price'         => $this->price,
            'product_name'  => $this->product->name ?? null,
            'category_name' => $this->product->category->name ?? null,
            'attributes'    => $this->attributeValues->pluck('value'),
            'stock' => [
                'quantity'            => $quantity,
                'low_stock_threshold' => $threshold,
                'status'              => $status, 
                'last_updated'        => $this->inventory->updated_at?->toIso8601String(),
            ]
        ];
    }
}