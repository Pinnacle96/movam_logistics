<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'starts_at',
        'expires_at',
        'usage_limit',
        'used_count',
        'is_active',
        'merchant_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'value' => 'float',
        'min_order_amount' => 'float',
        'max_discount_amount' => 'float',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('used_at')->withTimestamps();
    }

    public function isValidForUser(User $user, float $orderAmount): bool
    {
        if (!$this->is_active) return false;
        
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) return false;
        if ($this->expires_at && $now->gt($this->expires_at)) return false;
        
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        
        if ($orderAmount < $this->min_order_amount) return false;
        
        // Check if user already used this coupon (optional: could allow multiple uses)
        // For production, usually one use per customer is standard unless specified
        $alreadyUsed = $this->users()->where('user_id', $user->id)->exists();
        if ($alreadyUsed) return false;

        return true;
    }

    public function calculateDiscount(float $orderAmount): float
    {
        if ($this->type === 'fixed') {
            $discount = $this->value;
        } else {
            $discount = $orderAmount * ($this->value / 100);
        }

        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        return min($discount, $orderAmount);
    }
}
