<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductRepository implements ProductRepositoryInterface
{
    public function getPaginatedProducts(array $filters = [], int $perPage = 15): CursorPaginator
    {
        $query = Product::query()
            ->with(['category:id,name,slug', 'variants.inventory', 'variants.attributeValues', 'attributeValues'])
            ->where('is_active', true);

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('id', 'desc')->cursorPaginate($perPage);
    }

    public function findByIdOrSlug(string $identifier): ?Product
    {
        return Product::with(['category:id,name,slug', 'variants.inventory', 'variants.attributeValues', 'attributeValues'])
            ->where(is_numeric($identifier) ? 'id' : 'slug', $identifier)
            ->first();
    }

    public function createProductWithVariantsAndInventory(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // 1. Create Base Product
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name'        => $data['name'],
                'slug'        => Str::slug($data['name']) . '-' . Str::random(5),
                'description' => $data['description'] ?? null,
                'is_active'   => $data['is_active'] ?? true,
            ]);

            // 2. Attach Global Product Attribute Values (if provided)
            if (!empty($data['attribute_value_ids'])) {
                $product->attributeValues()->sync($data['attribute_value_ids']);
            }

            // 3. Handle Variants & Inventory safely using Null Coalescing Operator
            $variants = $data['variants'] ?? null;

            if (!empty($variants)) {
                // Multi-Variant Product
                foreach ($variants as $variantData) {
                    $variant = $product->variants()->create([
                        'sku'              => $variantData['sku'],
                        'price'            => $variantData['price'],
                        'compare_at_price' => $variantData['compare_at_price'] ?? null,
                        'cost_price'       => $variantData['cost_price'] ?? null,
                        'is_active'        => $variantData['is_active'] ?? true,
                    ]);

                    // Attach Inventory
                    $variant->inventory()->create([
                        'quantity'            => $variantData['quantity'],
                        'low_stock_threshold' => $variantData['low_stock_threshold'] ?? 5,
                    ]);

                    // Attach Variant Attributes
                    if (!empty($variantData['attribute_value_ids'])) {
                        $variant->attributeValues()->sync($variantData['attribute_value_ids']);
                    }
                }
            } else {
                // Simple Product: Default Variant + Inventory Setup
                $defaultVariant = $product->variants()->create([
                    'sku'       => $data['sku'] ?? 'SKU-' . strtoupper(Str::random(8)),
                    'price'     => $data['price'],
                    'is_active' => true,
                ]);

                $defaultVariant->inventory()->create([
                    'quantity'            => $data['quantity'],
                    'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                ]);
            }

            return $product->load(['variants.inventory', 'variants.attributeValues', 'attributeValues']);
        });
    }

    public function updateWithVariants(Product $product, array $productData, ?array $variantsData = null): Product
    {
        return DB::transaction(function () use ($product, $productData, $variantsData) {
            // 1. Update Base Product Info
            $product->update(array_filter([
                'category_id' => $productData['category_id'] ?? $product->category_id,
                'name'        => $productData['name'] ?? $product->name,
                'description' => $productData['description'] ?? $product->description,
                'is_active'   => $productData['is_active'] ?? $product->is_active,
            ], fn($value) => $value !== null));

            if (isset($productData['name'])) {
                $product->slug = Str::slug($productData['name']) . '-' . Str::random(5);
                $product->save();
            }

            // Sync global product attribute values if provided
            if (isset($productData['attribute_value_ids'])) {
                $product->attributeValues()->sync($productData['attribute_value_ids']);
            }

            // 2. Update Variants & Inventory
            if ($variantsData !== null) {
                foreach ($variantsData as $variantData) {
                    if (isset($variantData['id'])) {
                        // Existing Variant Update
                        $variant = $product->variants()->findOrFail($variantData['id']);
                        $variant->update([
                            'sku'              => $variantData['sku'] ?? $variant->sku,
                            'price'            => $variantData['price'] ?? $variant->price,
                            'compare_at_price' => $variantData['compare_at_price'] ?? $variant->compare_at_price,
                            'cost_price'       => $variantData['cost_price'] ?? $variant->cost_price,
                            'is_active'        => $variantData['is_active'] ?? $variant->is_active,
                        ]);

                        // Update Inventory
                        if (isset($variantData['quantity']) || isset($variantData['stock'])) {
                            $qty = $variantData['quantity'] ?? $variantData['stock'];
                            $variant->inventory()->updateOrCreate(
                                ['product_variant_id' => $variant->id],
                                [
                                    'quantity'            => $qty,
                                    'low_stock_threshold' => $variantData['low_stock_threshold'] ?? 5,
                                ]
                            );
                        }

                        // Sync Variant Attributes
                        if (isset($variantData['attribute_value_ids'])) {
                            $variant->attributeValues()->sync($variantData['attribute_value_ids']);
                        }
                    } else {
                        // New Variant Creation
                        $newVariant = $product->variants()->create([
                            'sku'              => $variantData['sku'],
                            'price'            => $variantData['price'],
                            'compare_at_price' => $variantData['compare_at_price'] ?? null,
                            'cost_price'       => $variantData['cost_price'] ?? null,
                            'is_active'        => $variantData['is_active'] ?? true,
                        ]);

                        $newVariant->inventory()->create([
                            'quantity'            => $variantData['quantity'] ?? $variantData['stock'] ?? 0,
                            'low_stock_threshold' => $variantData['low_stock_threshold'] ?? 5,
                        ]);

                        if (!empty($variantData['attribute_value_ids'])) {
                            $newVariant->attributeValues()->sync($variantData['attribute_value_ids']);
                        }
                    }
                }
            }

            return $product->load(['variants.inventory', 'variants.attributeValues', 'attributeValues']);
        });
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }
}
