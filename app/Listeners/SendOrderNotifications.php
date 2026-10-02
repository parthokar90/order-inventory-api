<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;

class SendOrderNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->load('customer');

        // Execute background email notification job via batching mechanism
        Bus::batch([
            new SendOrderNotificationJob($order, 'created'),
        ])->name("Order Mail Processing Batch #{$order->id}")
          ->allowFailures()
          ->dispatch();
    }
}