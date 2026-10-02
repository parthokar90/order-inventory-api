<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{

    const TYPE_PRIMARY = 'primary';
    const TYPE_GALLERY = 'gallery';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'path',
        'disk',
        'alt_text',
        'image_type',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected $appends = ['url'];  

    protected $hidden  = ['path', 'disk'];  

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopePrimary($query)
    {
        return $query->where('image_type', self::TYPE_PRIMARY);
    }

    public function scopeGallery($query)
    {
        return $query->where('image_type', self::TYPE_GALLERY);
    }

    public function scopeForVariant($query, ?int $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function isPrimary(): bool
    {
        return $this->image_type === self::TYPE_PRIMARY;
    }

    public function isGallery(): bool
    {
        return $this->image_type === self::TYPE_GALLERY;
    }
}