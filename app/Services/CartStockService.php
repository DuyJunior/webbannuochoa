<?php

namespace App\Services;

use App\Models\Perfume;

final class CartStockService
{
    /** Cart allocations are not reservations; checkout still locks and checks physical stock. */
    public static function remaining(Perfume $perfume, int $volume, array $cart): int
    {
        if (! $perfume->is_active || $perfume->trashed()) {
            return 0;
        }
        $allocated = 0;
        $column = self::stockColumn($perfume, $volume);
        if ($column === null) {
            return 0;
        }
        foreach ($cart as $key => $item) {
            $quantity = max(0, (int) (is_array($item) ? ($item['quantity'] ?? 1) : $item));
            if (is_array($item) && (! empty($item['is_discovery_box']) || ! empty($item['is_gift_bundle']))) {
                $sampleIds = is_array($item['sample_ids'] ?? null) ? $item['sample_ids'] : [];
                foreach ($sampleIds as $id) {
                    if (is_scalar($id) && (int) $id === $perfume->id && self::stockColumn($perfume, 5) === $column) {
                        $allocated += $quantity;
                    }
                }
                if (! empty($item['is_gift_bundle']) && (int) ($item['perfume_id'] ?? 0) === $perfume->id
                    && $column === 'stock') {
                    $allocated += $quantity;
                }
            } else {
                $id = is_array($item) ? (int) ($item['perfume_id'] ?? $item['id'] ?? 0) : (int) $key;
                $itemVolume = is_array($item) ? (int) ($item['volume_ml'] ?? $perfume->volume_ml ?: 100) : (int) ($perfume->volume_ml ?: 100);
                if ($id === $perfume->id && self::stockColumn($perfume, $itemVolume) === $column) {
                    $allocated += $quantity;
                }
            }
        }

        return max(0, $perfume->getStockForVolume($volume) - $allocated);
    }

    /** Maximum quantity of one quoted line, after the other cart lines have been counted. */
    public static function limitFor(array $quotedItem, array $otherCart): int
    {
        $components = ($quotedItem['stock_components'] ?? null) ?: [['perfume_id' => $quotedItem['perfume_id'], 'volume_ml' => $quotedItem['volume_ml']]];
        $products = Perfume::whereKey(array_column($components, 'perfume_id'))->get()->keyBy('id');
        $demands = [];
        $limit = 999;
        foreach ($components as $component) {
            $product = $products->get($component['perfume_id']);
            $column = $product ? self::stockColumn($product, (int) $component['volume_ml']) : null;
            if (! $product || $column === null) {
                return 0;
            }
            $bucket = $product->id.':'.$column;
            $demands[$bucket] = ($demands[$bucket] ?? 0) + 1;
        }
        foreach ($components as $component) {
            $product = $products->get($component['perfume_id']);
            $column = self::stockColumn($product, (int) $component['volume_ml']);
            $remaining = self::remaining($product, (int) $component['volume_ml'], $otherCart);
            $limit = min($limit, intdiv($remaining, $demands[$product->id.':'.$column]));
        }

        return $limit;
    }

    private static function stockColumn(Perfume $product, int $volume): ?string
    {
        return $product->stockBucketForVolume($volume);
    }
}
