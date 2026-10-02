<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Category\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Category\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Services\CategoryService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Exception;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Display a paginated listing of categories with search support (Public)
     */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $filters = [
                'search'    => $request->query('search'),
                'root_only' => $request->boolean('root_only'),
                'is_active' => $request->query('is_active'),
            ];

            $perPage = (int) $request->query('per_page', 15);

            // Fetch Cursor Paginated Data
            $categories = $this->categoryService->getPaginatedCategories($filters, $perPage);

            return $this->successResponse([
                'items'      => CategoryResource::collection($categories->items()),
                'pagination' => [
                    'per_page'      => $categories->perPage(),
                    'next_cursor'   => $categories->nextCursor()?->encode(),
                    'prev_cursor'   => $categories->previousCursor()?->encode(),
                    'has_more'      => $categories->hasMorePages(),
                ]
            ], 'Categories retrieved successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Failed to fetch categories: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created category (Admin Only)
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->createCategory($request->validated());

            return $this->successResponse(
                new CategoryResource($category),
                'Category created successfully',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified category details (Public)
     */
    public function show(int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->getCategoryById($id);

            return $this->successResponse(
                new CategoryResource($category),
                'Category details retrieved successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode() === 404 ? 404 : 500
            );
        }
    }

    /**
     * Update the specified category (Admin Only)
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->categoryService->updateCategory($id, $request->validated());

            return $this->successResponse(
                new CategoryResource($category),
                'Category updated successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode() === 404 ? 404 : 500
            );
        }
    }

    /**
     * Remove the specified category (Admin Only)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($id);

            return $this->successResponse(null, 'Category deleted successfully');
        } catch (Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500
            );
        }
    }
}
