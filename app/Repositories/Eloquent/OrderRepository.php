<?php

namespace App\Repositories\Eloquent;

use App\Events\OrderCancelled;
use App\Events\OrderPlaced;
use App\Exceptions\DuplicateOrderException;
use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Create order with:
     * - Idempotency check (retry-safe)
     * - Pessimistic locking (prevent overselling)
     * - Price from DB (not from client)
     * - reserved_quantity instead of hard deduction
     * - Async event after transaction commits
     */
    public function createOrder(array $data, string $idempotencyKey): Order
    {
        // Idempotency check — BEFORE transaction starts
        $existing = Order::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            throw new DuplicateOrderException($existing);
        }

        $order = DB::transaction(function () use ($data, $idempotencyKey) {
            $subtotal      = 0;
            $itemsToInsert = [];

            // 1. Lock inventory rows & validate stock availability
            foreach ($data['items'] as $item) {
                $inventory = Inventory::where('product_variant_id', $item['product_variant_id'])
                    ->lockForUpdate() // pessimistic lock — concurrent requests queue হবে
                    ->firstOrFail();

                // available_quantity = quantity - reserved_quantity (Model accessor)
                if (! $inventory->canFulfill($item['quantity'])) {
                    throw new InsufficientStockException(
                        "Insufficient stock for variant ID: {$item['product_variant_id']}. " .
                        "Available: {$inventory->available_quantity}, Requested: {$item['quantity']}"
                    );
                }

                // Fetch price from DB — client থেকে price নেওয়া security risk
                $variant = ProductVariant::select(['id', 'product_id', 'sku', 'price'])
                    ->with('product:id,name')
                    ->findOrFail($item['product_variant_id']);

                // Soft reserve — actual deduction হবে order complete হলে
                $inventory->increment('reserved_quantity', $item['quantity']);

                // Invalidate inventory cache
                Cache::forget("inventory:variant:{$variant->id}");

                $itemSubtotal  = $item['quantity'] * $variant->price; // DB price
                $subtotal     += $itemSubtotal;

                // Snapshot — price/name পরে change হলেও order history ঠিক থাকবে
                $itemsToInsert[] = [
                    'product_variant_id' => $variant->id,
                    'variant_sku'        => $variant->sku,
                    'product_name'       => $variant->product->name,
                    'quantity'           => $item['quantity'],
                    'unit_price'         => $variant->price,
                    'subtotal'           => $itemSubtotal,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            $taxAmount      = round($subtotal * 0.05, 2);
            $discountAmount = $data['discount_amount'] ?? 0.00;
            $totalAmount    = ($subtotal + $taxAmount) - $discountAmount;

            // 2. Create order record
            $order = Order::create([
                'order_number'     => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
                'customer_id'      => $data['customer_id'],
                'idempotency_key'  => $idempotencyKey,
                'subtotal'         => $subtotal,
                'tax_amount'       => $taxAmount,
                'discount_amount'  => $discountAmount,
                'total_amount'     => $totalAmount,
                'status'           => Order::STATUS_PENDING,
                'shipping_address' => $data['shipping_address'],
                'notes'            => $data['notes'] ?? null,
            ]);

            // 3. Bulk insert order items — single query, no loop
            $order->items()->insert(
                collect($itemsToInsert)
                    ->map(fn($item) => array_merge($item, ['order_id' => $order->id]))
                    ->toArray()
            );

            // 4. Initial status history
            OrderStatusHistory::create([
                'order_id'        => $order->id,
                'from_status'     => null,
                'to_status'       => Order::STATUS_PENDING,
                'changed_by_type' => 'customer',
                'changed_by_id'   => $data['customer_id'],
                'note'            => 'Order placed.',
            ]);

            // 5. Payment record
            $order->payment()->create([
                'payment_number' => 'PAY-' . strtoupper(Str::random(10)),
                'payment_method' => $data['payment_method'],
                'amount'         => $totalAmount,
                'currency'       => $data['currency'] ?? 'BDT',
                'status'         => Payment::STATUS_PENDING,
            ]);

            return $order;
        });

        // Fire event AFTER transaction commits
        // Transaction rollback হলে event fire হবে না
        event(new OrderPlaced($order));

        return $order->load([
            'items:id,order_id,product_name,variant_sku,quantity,unit_price,subtotal',
            'payment:id,order_id,payment_number,payment_method,amount,currency,status',
        ]);
    }

    /**
     * Update order status with:
     * - Valid transition check
     * - Stock release on cancellation (reserved_quantity)
     * - Stock confirm on completion (hard deduct)
     * - Status history tracking
     */
    public function updateStatus(Order $order, string $newStatus, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $notes) {
            if (! $order->canTransitionTo($newStatus)) {
                throw new \InvalidArgumentException(
                    "Cannot transition from [{$order->status}] to [{$newStatus}]."
                );
            }

            $fromStatus = $order->status;

            // Cancellation — reserved stock release করো
            if ($newStatus === Order::STATUS_CANCELLED) {
                foreach ($order->items as $item) {
                    $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                        ->lockForUpdate()
                        ->first();

                    if ($inventory) {
                        $release = min($inventory->reserved_quantity, $item->quantity);
                        $inventory->decrement('reserved_quantity', $release);
                        Cache::forget("inventory:variant:{$item->product_variant_id}");
                    }
                }

                // Async event after commit
                DB::afterCommit(fn() => event(new OrderCancelled($order)));
            }

            // Completion — actual stock deduct করো
            if ($newStatus === Order::STATUS_COMPLETED) {
                foreach ($order->items as $item) {
                    $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                        ->lockForUpdate()
                        ->first();

                    if ($inventory) {
                        // reserved থেকে বাদ দাও + total quantity থেকেও বাদ দাও
                        $deduct = min($inventory->reserved_quantity, $item->quantity);
                        $inventory->decrement('reserved_quantity', $deduct);
                        $inventory->decrement('quantity', $deduct);
                        Cache::forget("inventory:variant:{$item->product_variant_id}");
                    }
                }
            }

            // Update status
            $updateData = ['status' => $newStatus];
            if ($notes) {
                $updateData['notes'] = $order->notes
                    ? $order->notes . "\n" . $notes
                    : $notes;
            }

            $order->update($updateData);

            // Status history record
            OrderStatusHistory::create([
                'order_id'        => $order->id,
                'from_status'     => $fromStatus,
                'to_status'       => $newStatus,
                'changed_by_type' => 'system',
                'changed_by_id'   => 0,
                'note'            => $notes,
            ]);

            return $order->fresh();
        });
    }
}