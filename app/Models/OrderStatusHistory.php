<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    const UPDATED_AT = null;

    // ─── Constants ────────────────────────────────────────────

    const ACTOR_CUSTOMER = 'customer';
    const ACTOR_ADMIN    = 'admin';
    const ACTOR_SYSTEM   = 'system';

    // ─── Config ───────────────────────────────────────────────

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'changed_by_type',
        'changed_by_id',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return match ($this->changed_by_type) {
            self::ACTOR_ADMIN,
            self::ACTOR_SYSTEM   => $this->belongsTo(User::class, 'changed_by_id'),
            self::ACTOR_CUSTOMER => $this->belongsTo(User::class, 'changed_by_id'),
            default              => $this->belongsTo(User::class, 'changed_by_id'),
        };
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isInitial(): bool
    {
        return $this->from_status === null;
    }

    public function wasCancelledBy(): ?string
    {
        return $this->to_status === Order::STATUS_CANCELLED
            ? $this->changed_by_type
            : null;
    }
}