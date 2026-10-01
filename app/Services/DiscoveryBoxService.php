<?php

namespace App\Services;

use App\Models\Perfume;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class DiscoveryBoxService
{
    /** Cart contents do not reserve stock, but must not promise the same vial twice. */
    public static function remaining(Perfume $perfume, array $cart): int
    {
        return CartStockService::remaining($perfume, 5, $cart);
    }

    public static function assertAvailable(Collection $perfumes, array $cart): void
    {
        foreach ($perfumes as $perfume) {
            if (self::remaining($perfume, $cart) < 1) {
                throw ValidationException::withMessages([
                    'discovery' => $perfume->name.': mẫu 5ml đã hết hoặc đã được chọn hết trong giỏ. Vui lòng chọn mùi khác.',
                ]);
            }
        }
    }
}
