<?php

namespace App\Services;

use App\Models\Perfume;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class GiftBundleService
{
    public const EXTRA_PRICE = 90000;

    public const LEGACY_MARKER = 'Combo Trọn Vẹn + 2 Sample';

    public static function isLegacyMarker(mixed $text): bool
    {
        return is_string($text) && mb_stripos($text, self::LEGACY_MARKER) !== false;
    }

    public static function availableSamples(Perfume $main, array $cart = []): Collection
    {
        return Perfume::where('is_active', true)->whereKeyNot($main->id)->orderBy('name')->get()
            ->filter(fn (Perfume $sample) => CartStockService::remaining($sample, 5, $cart) > 0)->values();
    }

    /** One full bottle, two different physical 5ml samples, and the included gift box. */
    public function quote(Perfume $main, mixed $sampleIds): array
    {
        if (! $main->is_active || $main->trashed()) {
            throw ValidationException::withMessages(['bundle' => 'Chai chính của combo đã ngừng bán. Vui lòng chọn sản phẩm khác.']);
        }
        if (! is_array($sampleIds) || ! array_is_list($sampleIds) || count($sampleIds) !== 2) {
            throw ValidationException::withMessages(['sample_ids' => 'Vui lòng chọn đúng hai mẫu 5 ml khác nhau cho combo.']);
        }
        $ids = [];
        foreach ($sampleIds as $id) {
            if ((! is_int($id) && ! is_string($id)) || filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1) {
                throw ValidationException::withMessages(['sample_ids' => 'Mẫu thử không hợp lệ. Vui lòng chọn lại hai mẫu 5 ml.']);
            }
            $ids[] = (int) $id;
        }
        if (count(array_unique($ids)) !== 2 || in_array((int) $main->id, $ids, true)) {
            throw ValidationException::withMessages(['sample_ids' => 'Chọn hai mùi khác nhau và khác với chai chính của combo.']);
        }
        sort($ids, SORT_NUMERIC);
        $samples = Perfume::whereKey($ids)->where('is_active', true)->get()->keyBy('id');
        if ($samples->count() !== 2) {
            throw ValidationException::withMessages(['sample_ids' => 'Có mẫu thử đã ngừng bán. Vui lòng chọn lại hai mẫu.']);
        }
        $volume = (int) ($main->volume_ml ?: 100);
        if ($main->getStockForVolume($volume) < 1) {
            throw ValidationException::withMessages(['bundle' => 'Chai chính của combo đã hết hàng. Vui lòng chọn sản phẩm khác.']);
        }
        $bottleWeight = (int) ($main->weight > 0 ? $main->weight : 200);
        $components = [['perfume_id' => $main->id, 'volume_ml' => $volume, 'role' => 'main',
            'product_name' => $main->name, 'weight_grams' => $bottleWeight]];
        $names = [];
        foreach ($ids as $id) {
            $sample = $samples->get($id);
            if ($sample->getStockForVolume(5) < 1) {
                throw ValidationException::withMessages(['sample_ids' => $sample->name.': mẫu 5 ml đã hết hàng. Vui lòng chọn mùi khác.']);
            }
            $components[] = ['perfume_id' => $id, 'volume_ml' => 5, 'role' => 'sample', 'product_name' => $sample->name, 'weight_grams' => 50];
            $names[] = $sample->name;
        }

        return [
            'price' => (int) ($main->sale_price ?? $main->price) + self::EXTRA_PRICE,
            'volume_ml' => $volume, 'weight' => $bottleWeight + 100,
            'product_name' => 'Combo Trọn Vẹn · '.$main->name,
            'sample_ids' => $ids, 'sample_names' => $names, 'stock_components' => $components,
        ];
    }
}
