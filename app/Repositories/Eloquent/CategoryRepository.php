<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\CursorPaginator;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function getAllTree(): Collection
    {
        return Category::with('childrenRecursive')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->get();
    }

    public function getPaginatedCategories(array $filters = [], int $perPage = 15): CursorPaginator
    {
        $query = Category::query()->with('parent');

        // Search by Name
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Filter by Root Categories only if requested
        if (isset($filters['root_only']) && $filters['root_only'] === true) {
            $query->whereNull('parent_id');
        }

        // Filter by Status
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        // Cursor Pagination-এর জন্য deterministic order (id/created_at) আবশ্যক
        return $query->orderBy('id', 'desc')->cursorPaginate($perPage);
    }

    public function findById(int $id): ?Category
    {
        return Category::with(['parent', 'childrenRecursive'])->find($id);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category;
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }
}