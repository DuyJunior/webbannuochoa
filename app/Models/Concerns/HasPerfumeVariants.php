<?php

namespace App\Models\Concerns;

use App\Models\PerfumeVariant;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasPerfumeVariants
{
    public function variants(): HasMany
    {
        return $this->hasMany(PerfumeVariant::class, 'perfume_id')->orderBy('volume_ml');
    }

    public function variantForVolume(int $volume): ?PerfumeVariant
    {
        // These volumes retain their existing physical stock and pricing rules.
        if (in_array($volume, [5, 10, 50, (int) ($this->volume_ml ?: 100)], true)) {
            return null;
        }

        return $this->variants->first(fn ($variant) => $variant->volume_ml === $volume && $variant->is_active);
    }

    public function saleVolumes(): array
    {
        return array_values(array_unique(array_merge(
            [(int) ($this->volume_ml ?: 100), 50, 10],
            $this->variants->where('is_active', true)->pluck('volume_ml')->all()
        )));
    }

    public function stockBucketForVolume(int $volume): ?string
    {
        if ($volume === (int) ($this->volume_ml ?: 100)) {
            return 'stock';
        }

        return match ($volume) {
            5 => 'stock_5ml', 10 => 'stock_10ml', 50 => 'stock_50ml',
            default => ($variant = $this->variantForVolume($volume)) ? 'variant:'.$variant->id : null,
        };
    }

    public function availableStock(): int
    {
        return array_sum(array_map(fn (int $volume) => max(0, $this->getStockForVolume($volume)), $this->saleVolumes()));
    }

    public function firstAvailableVolume(): ?int
    {
        foreach ($this->saleVolumes() as $volume) {
            if ($this->getStockForVolume($volume) > 0) {
                return $volume;
            }
        }

        return null;
    }
}
