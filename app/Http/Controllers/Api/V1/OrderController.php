<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProcessPaymentCallbackRequest;
use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use App\Http\Requests\Api\V1\Order\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository
    ) {}

    /**
     * Place a new order with atomic operations and idempotency.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('X-Idempotency-Key');

        try {
            $order = $this->orderRepository->createOrder($request->validated(), $idempotencyKey);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data'    => $order
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Handle payment webhook/callback response to update Payment & Order table status.
     */
    public function handlePaymentCallback(ProcessPaymentCallbackRequest $request, int $orderId): JsonResponse
    {
        $order = Order::with('payment')->findOrFail($orderId);

        try {
            DB::transaction(function () use ($order, $request) {
                $payment = $order->payment;

                if (!$payment) {
                    throw new \Exception("No payment record associated with Order ID: {$order->id}");
                }

                // Update Payment model status and record payment metadata
                $payment->update([
                    'transaction_id' => $request->transaction_id,
                    'status'         => $request->status === 'completed' ? Payment::STATUS_COMPLETED : Payment::STATUS_FAILED,
                    'payload'        => $request->payload ?? null,
                    'paid_at'        => $request->status === 'completed' ? now() : null,
                ]);

                // Update Order status based on payment outcome
                if ($request->status === 'completed') {
                    $this->orderRepository->updateStatus($order, Order::STATUS_PROCESSING, 'Payment confirmed via gateway.');
                } else {
                    $this->orderRepository->updateStatus($order, Order::STATUS_CANCELLED, 'Payment failed at gateway.');
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully.',
                'data'    => $order->fresh(['payment'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Manual Order status update API endpoint.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        try {
            $updatedOrder = $this->orderRepository->updateStatus(
                $order,
                $request->status,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$request->status}.",
                'data'    => $updatedOrder
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}