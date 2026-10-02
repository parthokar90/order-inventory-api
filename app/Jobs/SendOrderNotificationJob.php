<?php

namespace App\Jobs;

use App\Mail\OrderNotificationMail;
use App\Models\Order;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendOrderNotificationJob implements ShouldQueue
{
    use Batchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order,
        public string $type = 'created'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if the batch has been cancelled before processing
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        // Ensure customer email exists before sending notification
        if ($this->order->customer && $this->order->customer->email) {
            Mail::to($this->order->customer->email)->send(
                new OrderNotificationMail($this->order, $this->type)
            );
        }
    }
}