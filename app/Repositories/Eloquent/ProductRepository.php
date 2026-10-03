<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductRepository implements ProductRepositoryInterface
{
    // Cache TTL constants
    const CACHE_TTL_SINGLE  = 1800; 
    const CACHE_TTL_LIST    = 300;  

    // ─── Read Operations ──────────────────────────────────────

    public function getPaginatedProducts(array $filters = [], int $perPage = 15): CursorPaginator
    {
        // List page not cached — too many filter combinations
        // Only single product detail is cached
        return Product::query()
            ->select([
                'id', 'category_id', 'name', 'slug',
                'description', 'is_active', 'created_at',
            ])
            ->with([
                // Specific columns only — no SELECT *
                'category:id,name,slug',
                'primaryImage:id,product_id,path,disk,alt_text',
                'variants' => fn($q) => $q
                    ->select(['id', 'product_id', 'sku', 'price', 'compare_at_price', 'is_active'])
                    ->where('is_active', true)
                    ->with([
                        'inventory:id,product_variant_id,quantity,reserved_quantity,low_stock_threshold',
                        'attributeValues:id,attribute_id,value',
                    ]),
            ])
            ->where('is_active', true)
            // category filter — direct where (no subquery)
            ->when(! empty($filters['category_id']), fn($q) =>
                $q->where('category_id', $filters['category_id'])
            )
            ->when(! empty($filters['search']), fn($q) =>
                $q->where(function ($q) use ($filters) {
                    $q->where('name', 'like', "%{$filters['search']}%")
                      ->orWhere('description', 'like', "%{$filters['search']}%");
                })
            )
            ->when(! empty($filters['min_price']), fn($q) =>
                $q->whereHas('variants', fn($vq) =>
                    $vq->where('price', '>=', $filters['min_price'])
                )
            )
            ->when(! empty($filters['max_price']), fn($q) =>
                $q->whereHas('variants', fn($vq) =>
                    $vq->where('price', '<=', $filters['max_price'])
                )
            )
            ->orderBy('id', 'desc')
            ->cursorPaginate($perPage);
    }

    public function findByIdOrSlug(string $identifier): ?Product
    {
        $cacheKey = "product:" . (is_numeric($identifier) ? "id:{$identifier}" : "slug:{$identifier}");

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL_SINGLE,
            function () use ($identifier) {
                return Product::select([
                    'id', 'category_id', 'name', 'slug', 'description', 'is_active', 'created_at',
                ])
                ->with([
                    'category:id,name,slug',
                    'primaryImage:id,product_id,path,disk,alt_text',
                    'galleryImages:id,product_id,path,disk,alt_text,sort_order',
                    'attributeValues:id,attribute_id,value',
                    'variants' => fn($q) => $q
                        ->select(['id', 'product_id', 'sku', 'price', 'compare_at_price', 'cost_price', 'is_active'])
                        ->where('is_active', true)
                        ->with([
                            'inventory:id,product_variant_id,quantity,reserved_quantity,low_stock_threshold',
                            'attributeValues:id,attribute_id,value',
                            'images:id,product_variant_id,path,disk,alt_text,sort_order',
                        ]),
                ])
                ->where(is_numeric($identifier) ? 'id' : 'slug', $identifier)
                ->first();
            }
        );
    }

    // ─── Write Operations ─────────────────────────────────────

    public function createProductWithVariantsAndInventory(array $data): Product
    {
        $product = DB::transaction(function () use ($data) {
            // 1. Create base product
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name'        => $data['name'],
                'slug'        => Str::slug($data['name']) . '-' . Str::random(5),
                'description' => $data['description'] ?? null,
                'is_active'   => $data['is_active'] ?? true,
            ]);

            // 2. Attach global attribute values
            if (! empty($data['attribute_value_ids'])) {
                $product->attributeValues()->sync($data['attribute_value_ids']);
            }

            // 3. Multi-variant or simple product
            if (! empty($data['variants'])) {
                foreach ($data['variants'] as $variantData) {
                    $this->createVariantWithInventory($product, $variantData);
                }
            } else {
                // Simple product — single default variant
                $this->createVariantWithInventory($product, [
                    'sku'                 => $data['sku'] ?? 'SKU-' . strtoupper(Str::random(8)),
                    'price'               => $data['price'],
                    'quantity'            => $data['quantity'],
                    'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                    'is_active'           => true,
                ]);
            }

            return $product->load([
                'variants.inventory',
                'variants.attributeValues:id,attribute_id,value',
                'attributeValues:id,attribute_id,value',
            ]);
        });

        // No cache to invalidate yet — new product
        return $product;
    }

    public function updateWithVariants(Product $product, array $productData, ?array $variantsData = null): Product
    {
        $updated = DB::transaction(function () use ($product, $productData, $variantsData) {
            // 1. Update base product
            $product->update(array_filter([
                'category_id' => $productData['category_id'] ?? null,
                'name'        => $productData['name'] ?? null,
                'description' => $productData['description'] ?? null,
                'is_active'   => $productData['is_active'] ?? null,
            ], fn($v) => $v !== null));

            // Regenerate slug only if name changed
            if (isset($productData['name'])) {
                $product->update([
                    'slug' => Str::slug($productData['name']) . '-' . Str::random(5),
                ]);
            }

            // Sync global attribute values
            if (isset($productData['attribute_value_ids'])) {
                $product->attributeValues()->sync($productData['attribute_value_ids']);
            }

            // 2. Update or create variants
            if ($variantsData !== null) {
                foreach ($variantsData as $variantData) {
                    if (isset($variantData['id'])) {
                        $this->updateVariantWithInventory($product, $variantData);
                    } else {
                        $this->createVariantWithInventory($product, $variantData);
                    }
                }
            }

            return $product->load([
                'variants.inventory',
                'variants.attributeValues:id,attribute_id,value',
                'attributeValues:id,attribute_id,value',
            ]);
        });

        // Invalidate cache — both id and slug keys
        $this->invalidateProductCache($product);

        return $updated;
    }

    public function delete(Product $product): bool
    {
        $deleted = $product->delete();

        // Invalidate cache on delete
        $this->invalidateProductCache($product);

        return $deleted;
    }

    // ─── Private Helpers ──────────────────────────────────────

    private function createVariantWithInventory(Product $product, array $variantData): void
    {
        $variant = $product->variants()->create([
            'sku'              => $variantData['sku'],
            'price'            => $variantData['price'],
            'compare_at_price' => $variantData['compare_at_price'] ?? null,
            'cost_price'       => $variantData['cost_price'] ?? null,
            'is_active'        => $variantData['is_active'] ?? true,
        ]);

        $variant->inventory()->create([
            'quantity'            => $variantData['quantity'] ?? 0,
            'low_stock_threshold' => $variantData['low_stock_threshold'] ?? 5,
        ]);

        if (! empty($variantData['attribute_value_ids'])) {
            $variant->attributeValues()->sync($variantData['attribute_value_ids']);
        }
    }

    private function updateVariantWithInventory(Product $product, array $variantData): void
    {
        $variant = $product->variants()->findOrFail($variantData['id']);

        $variant->update(array_filter([
            'sku'              => $variantData['sku'] ?? null,
            'price'            => $variantData['price'] ?? null,
            'compare_at_price' => $variantData['compare_at_price'] ?? null,
            'cost_price'       => $variantData['cost_price'] ?? null,
            'is_active'        => $variantData['is_active'] ?? null,
        ], fn($v) => $v !== null));

        // Update inventory if quantity provided
        if (isset($variantData['quantity'])) {
            $variant->inventory()->updateOrCreate(
                ['product_variant_id' => $variant->id],
                [
                    'quantity'            => $variantData['quantity'],
                    'low_stock_threshold' => $variantData['low_stock_threshold'] ?? 5,
                ]
            );

            // Invalidate inventory cache for this variant
            Cache::forget("inventory:variant:{$variant->id}");
        }

        if (isset($variantData['attribute_value_ids'])) {
            $variant->attributeValues()->sync($variantData['attribute_value_ids']);
        }
    }

    /**
     * Invalidate all cache keys related to a product.
     */
    private function invalidateProductCache(Product $product): void
    {
        Cache::forget("product:id:{$product->id}");
        Cache::forget("product:slug:{$product->slug}");
    }
}