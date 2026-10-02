<?php

namespace App\Repositories\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function createOrder(array $data, string $idempotencyKey): Order;
    public function updateStatus(Order $order, string $newStatus, ?string $notes = null): Order;
}