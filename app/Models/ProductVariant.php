<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'compare_at_price',
        'cost_price',
        'is_active',
    ];

    protected $casts = [
        'price'             => 'decimal:2',
        'compare_at_price'  => 'decimal:2',
        'cost_price'        => 'decimal:2',
        'is_active'         => 'boolean',
    ];

    protected $appends = ['is_on_sale', 'discount_percentage'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'product_variant_id');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(
            AttributeValue::class,
            'variant_attribute_values'
        );
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id')
                    ->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_variant_id');
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('inventory', function ($q) {
            $q->inStock();
        });
    }

    // Price range filter
    public function scopePriceBetween(Builder $query, float $min, float $max): Builder
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getIsOnSaleAttribute(): bool
    {
        return $this->compare_at_price !== null
            && $this->compare_at_price > $this->price;
    }

    public function getDiscountPercentageAttribute(): ?float
    {
        if (! $this->is_on_sale) {
            return null;
        }

        return round(
            (($this->compare_at_price - $this->price) / $this->compare_at_price) * 100,
            2
        );
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->inventory?->is_in_stock;
    }

    public function getAvailableStock(): int
    {
        return $this->inventory?->available_quantity ?? 0;
    }
}