<?php

namespace App\Repositories\Contracts;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\CursorPaginator;

interface CategoryRepositoryInterface
{
    public function getAllTree(): Collection;
    public function getPaginatedCategories(array $filters = [], int $perPage = 15): CursorPaginator;
    public function findById(int $id): ?Category;
    public function create(array $data): Category;
    public function update(Category $category, array $data): Category;
    public function delete(Category $category): bool;
}