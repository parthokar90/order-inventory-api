<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\StoreProductRequest;
use App\Http\Requests\Api\V1\Product\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Services\ProductService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display a listing of products with search, filtering and cursor pagination (Public)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'category_id' => $request->query('category_id'),
                'search'      => $request->query('search'),
            ];

            $perPage = (int) $request->query('per_page', 15);
            $products = $this->productService->listProducts($filters, $perPage);

            return $this->successResponse([
                'items' => ProductResource::collection($products->items()),
                'pagination' => [
                    'per_page'    => $products->perPage(),
                    'next_cursor' => $products->nextCursor()?->encode(),
                    'prev_cursor' => $products->previousCursor()?->encode(),
                    'has_more'    => $products->hasMorePages(),
                ]
            ], 'Products retrieved successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to fetch products: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created product with variants (Admin Only)
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = $this->productService->createProduct($request->validated());
            return $this->successResponse(
                new ProductResource($product->load(['category', 'variants.inventory'])),
                'Product created successfully',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Display specified product details (Public)
     */
    public function show(string $identifier): JsonResponse
    {
        try {
            $product = $this->productService->getProduct($identifier);
            return $this->successResponse(
                new ProductResource($product),
                'Product details retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() === 404 ? 404 : 500);
        }
    }

    /**
     * Update specified product details and variants (Admin Only)
     */
    public function update(UpdateProductRequest $request, string $identifier): JsonResponse
    {
        try {
            $product = $this->productService->updateProduct($identifier, $request->validated());
            return $this->successResponse(
                new ProductResource($product->load(['category', 'variants.inventory'])),
                'Product updated successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() === 404 ? 404 : 500);
        }
    }

    /**
     * Soft delete specified product (Admin Only)
     */
    public function destroy(string $identifier): JsonResponse
    {
        try {
            $this->productService->deleteProduct($identifier);
            return $this->successResponse(null, 'Product deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() === 404 ? 404 : 500);
        }
    }
}