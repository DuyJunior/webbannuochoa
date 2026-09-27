<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'perfume_id',
        'product_id',
        'quantity',
        'price',
        'volume_ml',
        'addon_gift',
        'engrave_text',
        'livestream_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:0',
            'volume_ml' => 'integer',
            'addon_gift' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function perfume(): BelongsTo
    {
        return $this->belongsTo(Perfume::class, 'perfume_id');
    }

    public function livestream(): BelongsTo
    {
        return $this->belongsTo(Livestream::class);
    }

    /**
     * Alias for product to support both $item->product and $item->perfume
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Perfume::class, 'perfume_id');
    }

    public function setProductIdAttribute($value): void
    {
        $this->attributes['perfume_id'] = $value;
    }

    public function getProductIdAttribute(): ?int
    {
        return isset($this->attributes['perfume_id']) ? (int) $this->attributes['perfume_id'] : null;
    }
}
