<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface ProductRepositoryInterface
{
    public function getPaginatedProducts(array $filters = [], int $perPage = 15): CursorPaginator;
    
    public function findByIdOrSlug(string $identifier): ?Product;
    
    public function createProductWithVariantsAndInventory(array $data): Product;
    
    public function updateWithVariants(Product $product, array $productData, ?array $variantsData = null): Product;
    
    public function delete(Product $product): bool;
}