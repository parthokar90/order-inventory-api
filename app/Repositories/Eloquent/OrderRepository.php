<?php

namespace App\Repositories\Eloquent;

use App\Events\OrderPlaced;
use App\Exceptions\InsufficientStockException;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Create order with database transaction, pessimistic locking, stock deduction, and payment record creation.
     */
    public function createOrder(array $data, string $idempotencyKey): Order
    {
        $order = DB::transaction(function () use ($data, $idempotencyKey) {
            $subtotal = 0;
            $itemsToInsert = [];

            // 1. Lock inventory rows & validate stock availability
            foreach ($data['items'] as $item) {
                $inventory = Inventory::where('product_variant_id', $item['product_variant_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$inventory->canFulfill($item['quantity'])) {
                    throw new InsufficientStockException("Insufficient stock for Product Variant ID: {$item['product_variant_id']}");
                }

                // Retrieve product variant and parent product for snapshots
                $variant = ProductVariant::with('product')->findOrFail($item['product_variant_id']);

                // Deduct actual available quantity
                $inventory->decrement('quantity', $item['quantity']);

                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $itemSubtotal;

                // Prepare order item snapshot array
                $itemsToInsert[] = [
                    'product_variant_id' => $variant->id,
                    'variant_sku'        => $variant->sku ?? 'SKU-' . $variant->id,
                    'product_name'       => $variant->product->name ?? 'Product Item',
                    'quantity'           => $item['quantity'],
                    'unit_price'         => $item['unit_price'],
                    'subtotal'           => $itemSubtotal,
                ];
            }

            $taxAmount = $data['tax_amount'] ?? 0.00;
            $discountAmount = $data['discount_amount'] ?? 0.00;
            $totalAmount = ($subtotal + $taxAmount) - $discountAmount;

            // 2. Create the Order master record
            $order = Order::create([
                'order_number'     => 'ORD-' . strtoupper(Str::random(10)),
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

            // 3. Create relational Order Items
            $order->items()->createMany($itemsToInsert);

            // 4. Create initial Payment record linked to this order
            $order->payment()->create([
                'payment_number' => 'PAY-' . strtoupper(Str::random(10)),
                'payment_method' => $data['payment_method'],
                'amount'         => $totalAmount,
                'currency'       => $data['currency'] ?? 'BDT',
                'status'         => Payment::STATUS_PENDING,
            ]);

            return $order;
        });

        // 5. Fire async event only after DB transaction successfully commits
        event(new OrderPlaced($order));

        return $order->load(['items', 'payment']);
    }

    /**
     * Update order status safely and handle stock restock on cancellation.
     */
    public function updateStatus(Order $order, string $newStatus, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $notes) {
            if (!$order->canTransitionTo($newStatus)) {
                throw new \InvalidArgumentException("Cannot transition order status from {$order->status} to {$newStatus}.");
            }

            // If order is cancelled, return reserved quantities back to stock inventory
            if ($newStatus === Order::STATUS_CANCELLED) {
                foreach ($order->items as $item) {
                    $inventory = Inventory::where('product_variant_id', $item->product_variant_id)
                        ->lockForUpdate()
                        ->first();

                    if ($inventory) {
                        $inventory->increment('quantity', $item->quantity);
                    }
                }
            }

            $updateData = ['status' => $newStatus];
            if ($notes) {
                $updateData['notes'] = $order->notes ? $order->notes . "\n" . $notes : $notes;
            }

            $order->update($updateData);

            return $order;
        });
    }
}