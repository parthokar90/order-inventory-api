<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotifyCustomerOnCancellation implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'emails';

    public int $tries = 3;

    public int $backoff = 60;

    public function handle(OrderCancelled $event): void
    {
        $order = $event->order;

        // TODO: Mail::to($order->customer->email)
        //           ->send(new OrderCancelledMail($order));

        Log::info('Order cancellation notification sent.', [
            'order_id'     => $order->id,
            'order_number' => $order->order_number,
            'customer_id'  => $order->customer_id,
        ]);
    }

    public function failed(OrderCancelled $event, \Throwable $exception): void
    {
        Log::error('Failed to send order cancellation notification.', [
            'order_id' => $event->order->id,
            'error'    => $exception->getMessage(),
        ]);
    }
}