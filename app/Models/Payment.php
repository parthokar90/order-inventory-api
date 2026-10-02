<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_REFUNDED  = 'refunded';

    protected $fillable = [
        'order_id',
        'payment_number',
        'payment_method',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'payload',
        'paid_at',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'payload' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}