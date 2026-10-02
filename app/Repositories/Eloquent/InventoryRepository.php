<?php

namespace App\Repositories\Eloquent;

use App\Models\ProductVariant;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function getStockList(array $filters = [], int $perPage = 15): CursorPaginator
    {
        $query = ProductVariant::query()
            ->with([
                'product:id,name,slug,category_id',
                'product.category:id,name',
                'inventory',
                'attributeValues:id,value'
            ])
            ->whereHas('inventory'); 

        // 1. Search Filter (by Product Name or SKU)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', '%' . $search . '%')
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        // 2. Category Filter
        if (!empty($filters['category_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        // 3. Stock Status Filter
        if (!empty($filters['stock_status']) && $filters['stock_status'] !== 'all') {
            $status = $filters['stock_status'];

            $query->whereHas('inventory', function ($q) use ($status) {
                if ($status === 'out_of_stock') {
                    $q->where('quantity', '<=', 0);
                } elseif ($status === 'low_stock') {
                    $q->where('quantity', '>', 0)
                      ->whereColumn('quantity', '<=', 'low_stock_threshold');
                } elseif ($status === 'in_stock') {
                    $q->whereColumn('quantity', '>', 'low_stock_threshold');
                }
            });
        }

        // Cursor Pagination ordered by ProductVariant ID
        return $query->orderBy('id', 'desc')->cursorPaginate($perPage);
    }
}