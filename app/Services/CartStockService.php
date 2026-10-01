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
        foreach ($cart as $key => $item) {
            $quantity = max(0, (int) (is_array($item) ? ($item['quantity'] ?? 1) : $item));
            if (is_array($item) && ! empty($item['is_discovery_box'])) {
                if ($volume === 5 && in_array($perfume->id, array_map('intval', $item['sample_ids'] ?? []), true)) {
                    $allocated += $quantity;
                }
            } else {
                $id = is_array($item) ? (int) ($item['perfume_id'] ?? $item['id'] ?? 0) : (int) $key;
                $itemVolume = is_array($item) ? (int) ($item['volume_ml'] ?? $perfume->volume_ml ?: 100) : (int) ($perfume->volume_ml ?: 100);
                if ($id === $perfume->id && $itemVolume === $volume) {
                    $allocated += $quantity;
                }
            }
        }

        return max(0, $perfume->getStockForVolume($volume) - $allocated);
    }

    /** Maximum quantity of one quoted line, after the other cart lines have been counted. */
    public static function limitFor(array $quotedItem, array $otherCart): int
    {
        $components = $quotedItem['stock_components'] ?: [['perfume_id' => $quotedItem['perfume_id'], 'volume_ml' => $quotedItem['volume_ml']]];
        $products = Perfume::whereKey(array_column($components, 'perfume_id'))->get()->keyBy('id');
        $limit = 999;
        foreach ($components as $component) {
            $product = $products->get($component['perfume_id']);
            $limit = min($limit, $product ? self::remaining($product, (int) $component['volume_ml'], $otherCart) : 0);
        }

        return $limit;
    }
}
