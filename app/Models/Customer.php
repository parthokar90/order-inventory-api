<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{

    protected $fillable = [
        'name',
        'email',
        'phone',
        'shipping_address',
        'is_active',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'shipping_address' => 'array',   
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeOrders(): HasMany
    {
        return $this->hasMany(Order::class)
                    ->whereIn('status', ['pending', 'processing']);
    }

    public function completedOrders(): HasMany
    {
        return $this->hasMany(Order::class)
                    ->where('status', 'completed');
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function getTotalOrdersCountAttribute(): int
    {
        return $this->orders()->count();
    }

    public function getTotalSpentAttribute(): float
    {
        return $this->orders()
                    ->where('status', 'completed')
                    ->sum('total_amount');
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }
}