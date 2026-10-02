<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\CursorPaginator;

interface InventoryRepositoryInterface
{
    public function getStockList(array $filters = [], int $perPage = 15): CursorPaginator;
}