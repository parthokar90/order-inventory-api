<?php

namespace App\Repositories\Eloquent;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;

class InventoryRepository implements InventoryRepositoryInterface
{
    const CACHE_TTL_INVENTORY = 300; 

    // ─── Read Operations ──────────────────────────────────────

    public function getStockList(array $filters = [], int $perPage = 15): CursorPaginator
    {
        // List not cached — filter combinations too many
        return ProductVariant::query()
            ->select(['id', 'product_id', 'sku', 'price', 'is_active'])
            ->with([
                // Specific columns only
                'product:id,category_id,name,slug',
                'product.category:id,name',
                'inventory:id,product_variant_id,quantity,reserved_quantity,low_stock_threshold',
                'attributeValues:id,attribute_id,value',
            ])
            ->whereHas('inventory')
            ->when(! empty($filters['search']), fn($q) =>
                $q->where(function ($q) use ($filters) {
                    $q->where('sku', 'like', "%{$filters['search']}%")
                      ->orWhereHas('product', fn($pq) =>
                          $pq->where('name', 'like', "%{$filters['search']}%")
                      );
                })
            )
            // Direct join more efficient than whereHas for category
            ->when(! empty($filters['category_id']), fn($q) =>
                $q->whereHas('product', fn($pq) =>
                    $pq->where('category_id', $filters['category_id'])
                )
            )
            ->when(! empty($filters['stock_status']), fn($q) =>
                $q->whereHas('inventory', function ($iq) use ($filters) {
                    match ($filters['stock_status']) {
                        'out_of_stock' => $iq->whereRaw('(quantity - reserved_quantity) <= 0'),
                        'low_stock'    => $iq->whereRaw('(quantity - reserved_quantity) > 0')
                                            ->whereRaw('(quantity - reserved_quantity) <= low_stock_threshold'),
                        'in_stock'     => $iq->whereRaw('(quantity - reserved_quantity) > low_stock_threshold'),
                        default        => null,
                    };
                })
            )
            ->orderBy('id', 'desc')
            ->cursorPaginate($perPage);
    }

    /**
     * Get single inventory by variant ID.
     * Redis cached — used heavily during order creation stock check.
     */
    public function findByVariantId(int $variantId): ?Inventory
    {
        return Cache::remember(
            "inventory:variant:{$variantId}",
            self::CACHE_TTL_INVENTORY,
            fn() => Inventory::select([
                'id', 'product_variant_id',
                'quantity', 'reserved_quantity', 'low_stock_threshold',
            ])
            ->where('product_variant_id', $variantId)
            ->first()
        );
    }

    /**
     * Get low stock items for admin alert.
     * Cached 5 minutes.
     */
    public function getLowStockItems(int $perPage = 15): CursorPaginator
    {
        return ProductVariant::query()
            ->select(['id', 'product_id', 'sku', 'price'])
            ->with([
                'product:id,name,slug',
                'inventory:id,product_variant_id,quantity,reserved_quantity,low_stock_threshold',
            ])
            ->whereHas('inventory', fn($q) =>
                $q->whereRaw('(quantity - reserved_quantity) > 0')
                  ->whereRaw('(quantity - reserved_quantity) <= low_stock_threshold')
            )
            ->orderBy('id', 'desc')
            ->cursorPaginate($perPage);
    }

    // ─── Write Operations ─────────────────────────────────────

    /**
     * Update stock quantity directly (admin restock).
     * Invalidates cache after update.
     */
    public function updateStock(int $variantId, int $quantity, int $lowStockThreshold = 5): Inventory
    {
        $inventory = Inventory::where('product_variant_id', $variantId)->firstOrFail();

        $inventory->update([
            'quantity'            => $quantity,
            'low_stock_threshold' => $lowStockThreshold,
        ]);

        // Invalidate cache — stock changed
        $this->invalidateInventoryCache($variantId);

        return $inventory->fresh();
    }

    /**
     * Invalidate inventory cache for a variant.
     * Called from InventoryService after reserve/release/confirm.
     */
    public function invalidateInventoryCache(int $variantId): void
    {
        Cache::forget("inventory:variant:{$variantId}");
    }
}