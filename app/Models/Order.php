<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'merchant_id',
        'rider_id',
        'coupon_id',
        'order_number',
        'total_amount',
        'delivery_fee',
        'commission_amount',
        'discount_amount',
        'rider_share',
        'status',
        'payment_status',
        'payment_method',
        'delivery_address',
        'delivery_lat',
        'delivery_lng',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
