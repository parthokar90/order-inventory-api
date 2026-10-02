<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param Order $order
     * @param string $type The context of the notification (e.g., 'created', 'updated', 'cancelled')
     */
    public function __construct(
        public Order $order,
        public string $type = 'created'
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'updated'   => "Order Status Updated - #{$this->order->order_number}",
            'cancelled' => "Order Cancelled - #{$this->order->order_number}",
            default     => "Order Confirmation - #{$this->order->order_number}",
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.notification',
            with: [
                'order' => $this->order->load(['items', 'customer', 'payment']),
                'type'  => $this->type,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}