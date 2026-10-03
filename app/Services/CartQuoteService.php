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
            if (GiftBundleService::isLegacyMarker($data['engrave_text'] ?? null)) {
                throw ValidationException::withMessages(['cart' => 'Combo cũ chưa có hai mẫu thử được chọn. Hãy xóa dòng này và chọn lại combo cùng hai mẫu 5 ml.']);
            }
            $volume = (int) ($data['volume_ml'] ?? $product->volume_ml ?: 100);
            $components = null;
            $isGiftBundle = ! empty($data['is_gift_bundle']);
            $productName = $product->name;
            $sampleNames = [];
            if ($isGiftBundle) {
                if (! empty($data['is_discovery_box']) || $volume !== (int) ($product->volume_ml ?: 100)) {
                    throw ValidationException::withMessages(['cart' => 'Thông tin combo không hợp lệ. Hãy xóa và chọn lại combo nguyên chai cùng hai mẫu thử.']);
                }
                $bundle = app(GiftBundleService::class)->quote($product, $data['sample_ids'] ?? null);
                $volume = $bundle['volume_ml'];
                $price = $bundle['price'];
                $lineWeight = $bundle['weight'];
                $components = $bundle['stock_components'];
                $productName = $bundle['product_name'];
                $sampleNames = $bundle['sample_names'];
            } elseif (! empty($data['is_discovery_box'])) {
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
            $quotedItem = [
                'perfume_id' => $product->id, 'product_id' => $product->id,
                'product_name' => $productName, 'is_gift_bundle' => $isGiftBundle,
                'sample_names' => $sampleNames, 'weight' => $lineWeight,
                'quantity' => $quantity, 'price' => $price, 'volume_ml' => $volume,
                'addon_gift' => $isGiftBundle || (bool) ($data['has_gift'] ?? false),
                'engrave_text' => $isGiftBundle ? null : ($data['engrave_text'] ?? null),
                'livestream_id' => $data['livestream_id'] ?? null,
                'stock_components' => $components,
            ];
            if ($isGiftBundle && $quantity > CartStockService::limitFor($quotedItem, array_diff_key($cart, [$key => true]))) {
                throw ValidationException::withMessages(['cart' => 'Không đủ chai chính hoặc mẫu thử cho combo sau khi tính các món khác trong giỏ. Hãy giảm số lượng hoặc chọn lại mẫu.']);
            }
            $items[] = $quotedItem;
            $total += $price * $quantity;
            $weight += $lineWeight * $quantity;
        }
        if (! $items) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
        }

        return ['items' => $items, 'total' => $total, 'weight' => $weight];
    }
}
