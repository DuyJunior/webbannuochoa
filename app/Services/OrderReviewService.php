<?php

namespace App\Services;

use App\Models\Perfume;
use App\Models\PerfumeReview;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

class OrderReviewService
{
    public static function eligibleItems(int $userId, Perfume $perfume): Collection
    {
        return OrderItem::whereHas('order', fn ($query) => $query->where('user_id', $userId)->reviewable())
            ->with('order')->orderByDesc('id')->get()
            ->filter(fn ($item) => in_array((int) $perfume->id, $item->reviewProductIds(), true))->values();
    }

    public static function viewData(iterable $orders, int $userId): array
    {
        $items = collect($orders)->flatMap(fn ($order) => $order->items);
        $ids = $items->flatMap(fn ($item) => $item->reviewProductIds())->unique();

        return [
            'reviewProducts' => Perfume::whereKey($ids)->where('is_active', true)->get()->keyBy('id'),
            'customerReviews' => PerfumeReview::where('user_id', $userId)->whereIn('order_item_id', $items->pluck('id'))
                ->whereIn('perfume_id', $ids)->get()->keyBy(fn ($review) => $review->order_item_id.'-'.$review->perfume_id),
        ];
    }
}
