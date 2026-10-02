<?php

namespace App\Services;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Exception;

class CategoryService
{
    protected const CACHE_KEY_TREE = 'categories_tree';

    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function getPaginatedCategories(array $filters, int $perPage)
    {
        return $this->categoryRepository->getPaginatedCategories($filters, $perPage);
    }

    public function getCategoryTree()
    {
        return Cache::remember(self::CACHE_KEY_TREE, 3600, function () {
            return $this->categoryRepository->getAllTree();
        });
    }

    public function getCategoryById(int $id): Category
    {
        $category = $this->categoryRepository->findById($id);

        if (! $category) {
            throw new Exception('Category not found', 404);
        }

        return $category;
    }

    public function createCategory(array $data): Category
    {
        $data['slug'] = Str::slug($data['name']);
        $category = $this->categoryRepository->create($data);

        Cache::forget(self::CACHE_KEY_TREE);

        return $category;
    }

    public function updateCategory(int $id, array $data): Category
    {
        $category = $this->getCategoryById($id);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $updatedCategory = $this->categoryRepository->update($category, $data);
        Cache::forget(self::CACHE_KEY_TREE);

        return $updatedCategory;
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->getCategoryById($id);

        if ($category->childrenRecursive()->count() > 0) {
            throw new Exception('Cannot delete category with active sub-categories.', 422);
        }

        $deleted = $this->categoryRepository->delete($category);
        Cache::forget(self::CACHE_KEY_TREE);

        return $deleted;
    }
}