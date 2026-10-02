<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Inventory extends Model
{

    protected $fillable = [
        'product_variant_id',
        'quantity',
        'reserved_quantity',
        'low_stock_threshold',
    ];

    protected $casts = [
        'quantity'            => 'integer',
        'reserved_quantity'   => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    protected $appends = ['available_quantity', 'is_in_stock', 'is_low_stock'];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // ─── Accessors ────────────────────────────────────────────

    // actual sellable stock = total - reserved
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    public function getIsInStockAttribute(): bool
    {
        return $this->available_quantity > 0;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->available_quantity > 0
            && $this->available_quantity <= $this->low_stock_threshold;
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereRaw('(quantity - reserved_quantity) > 0');
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereRaw('(quantity - reserved_quantity) > 0')
                     ->whereRaw('(quantity - reserved_quantity) <= low_stock_threshold');
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->whereRaw('(quantity - reserved_quantity) <= 0');
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function canFulfill(int $requestedQty): bool
    {
        return $this->available_quantity >= $requestedQty;
    }
}