<?php

namespace App\Services;

use App\Models\Perfume;
use Illuminate\Support\Collection;

final class MoodCollectionService
{
    /** Curated mood advice uses verified notes and only currently purchasable sizes. */
    public static function from(Collection $products): array
    {
        $groups = ['rose' => [], 'velvet' => [], 'sage' => []];
        $quotes = app(CartQuoteService::class);
        foreach ($products->sortBy('id') as $product) {
            if (! $product instanceof Perfume || ! $product->is_active) {
                continue;
            }
            $profile = FragranceEditorialService::forPerfume($product);
            if (! $profile['verified']) {
                continue;
            }
            $volume = collect(array_unique([(int) ($product->volume_ml ?: 100), 50, 10]))
                ->first(fn ($size) => $product->getStockForVolume($size) > 0);
            if ($volume === null) {
                continue;
            }
            foreach (array_keys($groups) as $mood) {
                if (count($groups[$mood]) >= 3 || ! in_array($mood, $profile['moods'], true)) {
                    continue;
                }
                $reason = $profile['mood_reason'][$mood] ?? null;
                if (! $reason) {
                    continue;
                }
                $groups[$mood][] = ['product' => $product, 'reason' => $reason,
                    'volume' => $volume, 'price' => $quotes->unitPrice($product, $volume)];
            }
        }

        return $groups;
    }
}
