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
        'checkout_key', 'payment_expires_at',
        'inventory_status',
        'is_demo',
        'user_id',
        'customer_name',
        'name',
        'address',
        'note',
        'phone',
        'total_price',
        'coupon_code',
        'discount_amount',
        'points_used',
        'status',
        'shipping_status',
        // Các trường GHN:
        'ghn_order_code',
        'ghn_total_fee',
        'to_district_id',
        'to_ward_code',
        // Quà tặng cao cấp & Lời nhắn:
        'gift_wrap',
        'gift_card',
        'gift_message',
        'gift_delivery_date',
    ];

    protected function casts(): array
    {
        return [
            'payment_expires_at' => 'datetime',
            'is_demo' => 'boolean',
            'total_price' => 'decimal:0',
            'ghn_total_fee' => 'integer',
            'to_district_id' => 'integer',
        ];
    }

    public function getNameAttribute(): ?string
    {
        return $this->attributes['name'] ?? $this->attributes['customer_name'] ?? null;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
        if (! isset($this->attributes['customer_name']) || empty($this->attributes['customer_name'])) {
            $this->attributes['customer_name'] = $value;
        }
    }

    public function setCustomerNameAttribute(?string $value): void
    {
        $this->attributes['customer_name'] = $value;
        if (! isset($this->attributes['name']) || empty($this->attributes['name'])) {
            $this->attributes['name'] = $value;
        }
    }

    protected static function booted(): void
    {
        static::created(fn (Order $order) => $order->recordEvent(true));
        static::updated(function (Order $order) {
            if ($order->wasChanged(['status', 'shipping_status'])) {
                $order->recordEvent();
            }
        });
    }

    private function recordEvent(bool $created = false): void
    {
        $this->events()->create([
            'actor_id' => auth()->id(),
            'from_status' => $created ? null : $this->getRawOriginal('status'),
            'to_status' => $this->status,
            'from_shipping' => $created ? null : $this->getRawOriginal('shipping_status'),
            'to_shipping' => $this->shipping_status,
            'created_at' => now(),
        ]);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->orderBy('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canReviewProducts(): bool
    {
        return $this->status !== 'cancelled' && ($this->status === 'completed' || $this->shipping_status === 'delivered');
    }

    public function scopeReviewable(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query->where('status', '!=', 'cancelled')
            ->where(fn ($query) => $query->where('status', 'completed')->orWhere('shipping_status', 'delivered'));
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function emails(): HasMany
    {
        return $this->hasMany(OrderEmail::class)->orderBy('id');
    }
}
