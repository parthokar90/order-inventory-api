<?php

namespace App\Services;

use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Support\Str;
use Exception;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    public function listProducts(array $filters, int $perPage)
    {
        return $this->productRepository->getPaginatedProducts($filters, $perPage);
    }

    public function getProduct(string $identifier): Product
    {
        $product = $this->productRepository->findByIdOrSlug($identifier);
        if (!$product) {
            throw new Exception('Product not found', 404);
        }
        return $product;
    }

    public function createProduct(array $data): Product
    {
       return $this->productRepository->createProductWithVariantsAndInventory($data);
    }

    public function updateProduct(string $identifier, array $data): Product
    {
        $product = $this->getProduct($identifier);

        $productData = array_filter([
            'category_id' => $data['category_id'] ?? null,
            'name'        => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active'   => $data['is_active'] ?? null,
        ], fn($value) => !is_null($value));

        if (isset($data['name'])) {
            $productData['slug'] = Str::slug($data['name']) . '-' . Str::random(5);
        }

        return $this->productRepository->updateWithVariants($product, $productData, $data['variants'] ?? null);
    }

    public function deleteProduct(string $identifier): bool
    {
        $product = $this->getProduct($identifier);
        return $this->productRepository->delete($product);
    }
}