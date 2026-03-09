<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'vehicle_number',
        'license_number',
        'id_card_image',
        'license_image',
        'is_verified',
        'is_active',
        'is_available',
        'current_lat',
        'current_lng',
        'rating',
    ];

    protected $appends = ['id_card_image_url', 'license_image_url', 'average_rating', 'review_count'];

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

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getIdCardImageUrlAttribute()
    {
        return $this->id_card_image ? asset('storage/' . $this->id_card_image) : null;
    }

    public function getLicenseImageUrlAttribute()
    {
        return $this->license_image ? asset('storage/' . $this->license_image) : null;
    }
}
