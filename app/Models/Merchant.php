<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Merchant extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::creating(function ($merchant) {
            if (empty($merchant->slug)) {
                $merchant->slug = Str::slug($merchant->business_name);
                // Ensure uniqueness
                $originalSlug = $merchant->slug;
                $count = 1;
                while (static::where('slug', $merchant->slug)->exists()) {
                    $merchant->slug = $originalSlug . '-' . $count++;
                }
            }
        });
    }

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'address',
        'phone',
        'commission_rate',
        'is_verified',
        'is_active',
        'logo',
        'cover_image',
    ];

    protected $appends = ['logo_url', 'cover_image_url', 'average_rating', 'review_count'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(MerchantSubscription::class);
    }

    public function hasPriorityListing(): bool
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->whereHas('plan', function($q) {
                $q->whereJsonContains('features', 'priority_listing');
            })->exists();
    }

    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getCoverImageUrlAttribute()
    {
        return $this->cover_image ? asset('storage/' . $this->cover_image) : null;
    }
}
