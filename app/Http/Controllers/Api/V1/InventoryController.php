<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Inventory\InventoryIndexRequest;
use App\Http\Resources\Api\V1\InventoryResource;
use App\Repositories\Contracts\InventoryRepositoryInterface;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryRepositoryInterface $inventoryRepository
    ) {}

    public function index(InventoryIndexRequest $request)
    {
        $perPage = $request->input('per_page', 15);
        
        $filters = $request->only(['search', 'stock_status', 'category_id']);

        $inventories = $this->inventoryRepository->getStockList($filters, $perPage);

        return InventoryResource::collection($inventories)
            ->additional([
                'success' => true,
                'message' => 'Inventory records retrieved successfully.'
            ]);
    }
}