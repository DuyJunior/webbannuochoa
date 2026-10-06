<?php

namespace App\Services;

use App\Models\Perfume;
use App\Models\PerfumeReview;

class OrderReviewService
{
    public static function viewData(iterable $orders, int $userId): array
    {
        $ids = collect($orders)->flatMap(fn ($order) => $order->items->flatMap(fn ($item) => $item->reviewProductIds()))->unique();

        return [
            'reviewProducts' => Perfume::whereKey($ids)->where('is_active', true)->get()->keyBy('id'),
            'customerReviews' => PerfumeReview::where('user_id', $userId)->whereIn('perfume_id', $ids)->get()->keyBy('perfume_id'),
        ];
    }
}
