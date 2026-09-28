<?php

namespace App\Services;

use App\Models\Perfume;
use Illuminate\Validation\ValidationException;

class CartQuoteService
{
    public function unitPrice(Perfume $product, int $volume, bool $gift = false): int
    {
        if (! in_array($volume, [10, 50, (int) ($product->volume_ml ?: 100)], true)) {
            throw ValidationException::withMessages(['cart' => 'Dung tích sản phẩm không hợp lệ.']);
        }
        $base = (int) ($product->sale_price ?? $product->price);
        $price = $volume === (int) ($product->volume_ml ?: 100) ? $base : match ($volume) {
            10 => max(20000, (int) (round($base * 0.22 / 10000) * 10000)),
            50 => (int) (round($base * 0.65 / 10000) * 10000),
        };

        return $price + ($gift ? 50000 : 0);
    }

    /** Prices are always recalculated from the catalog, never trusted from session. */
    public function quote(array $cart): array
    {
        $items = [];
        $total = 0;
        $weight = 0;
        foreach ($cart as $key => $value) {
            $data = is_array($value) ? $value : ['perfume_id' => $key, 'quantity' => $value];
            $product = Perfume::find((int) ($data['perfume_id'] ?? $data['id'] ?? 0));
            $quantity = filter_var($data['quantity'] ?? 1, FILTER_VALIDATE_INT);
            if (! $product || ! $product->is_active || ! $quantity || $quantity < 1 || $quantity > 999) {
                throw ValidationException::withMessages(['cart' => 'Sản phẩm hoặc số lượng trong giỏ không hợp lệ.']);
            }
            $volume = (int) ($data['volume_ml'] ?? $product->volume_ml ?: 100);
            $components = null;
            if (! empty($data['is_discovery_box'])) {
                $ids = array_values(array_unique(array_map('intval', $data['sample_ids'] ?? [])));
                if (! in_array(count($ids), [3, 5], true) ||
                    Perfume::whereKey($ids)->where('is_active', true)->count() !== count($ids)) {
                    throw ValidationException::withMessages(['cart' => 'Hộp thử mùi phải có đúng 3 hoặc 5 sản phẩm đang bán khác nhau.']);
                }
                $volume = 5;
                $price = count($ids) === 5 ? 299000 : 199000;
                $components = array_map(fn ($id) => ['perfume_id' => $id, 'volume_ml' => 5], $ids);
                $lineWeight = 50 * count($ids);
            } else {
                $price = $this->unitPrice($product, $volume, (bool) ($data['has_gift'] ?? false));
                $lineWeight = $product->getWeightForVolume($volume);
            }
            $items[] = [
                'perfume_id' => $product->id, 'product_id' => $product->id,
                'quantity' => $quantity, 'price' => $price, 'volume_ml' => $volume,
                'addon_gift' => (bool) ($data['has_gift'] ?? false),
                'engrave_text' => $data['engrave_text'] ?? null,
                'livestream_id' => $data['livestream_id'] ?? null,
                'stock_components' => $components,
            ];
            $total += $price * $quantity;
            $weight += $lineWeight * $quantity;
        }
        if (! $items) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
        }

        return ['items' => $items, 'total' => $total, 'weight' => $weight];
    }
}
