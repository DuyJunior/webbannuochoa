<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name', 'product_brand',
        'stock_components',
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
            'stock_components' => 'array',
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
        return $this->belongsTo(Perfume::class, 'perfume_id')->withTrashed();
    }

    public function livestream(): BelongsTo
    {
        return $this->belongsTo(Livestream::class);
    }

    public function getIsGiftBundleAttribute(): bool
    {
        return collect($this->stock_components ?? [])->contains('role', 'main');
    }

    public function getIsDiscoveryBoxAttribute(): bool
    {
        $components = collect($this->stock_components ?? []);

        return ! $this->is_gift_bundle && $components->count() > 1
            && $components->every(fn ($component) => (int) ($component['volume_ml'] ?? 0) === 5);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->is_discovery_box
            ? 'Hộp thử mùi · '.count($this->stock_components).' mẫu'
            : ($this->product_name ?: $this->perfume?->name ?: 'Sản phẩm');
    }

    public function getVolumeLabelAttribute(): string
    {
        if ($this->is_gift_bundle) {
            return $this->volume_ml.'ml + 2 × 5ml';
        }

        return $this->is_discovery_box ? count($this->stock_components).' × 5ml' : ($this->volume_ml ?: 100).'ml';
    }

    public function getSampleNamesAttribute(): array
    {
        return collect($this->stock_components ?? [])
            ->filter(fn ($component) => $this->is_discovery_box || ($component['role'] ?? null) === 'sample')
            ->pluck('product_name')->filter()->values()->all();
    }

    /**
     * Alias for product to support both $item->product and $item->perfume
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Perfume::class, 'perfume_id')->withTrashed();
    }

    protected static function booted(): void
    {
        static::creating(function (OrderItem $item) {
            $product = Perfume::withTrashed()->find($item->perfume_id);
            $item->product_name ??= $product?->name ?? 'Sản phẩm';
            $item->product_brand ??= $product?->brand;
        });
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
