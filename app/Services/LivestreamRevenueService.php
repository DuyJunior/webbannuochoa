<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonInterface;

class LivestreamRevenueService
{
    /**
     * Revenue is paid merchandise attributed to a live, after a proportional
     * share of order discounts and redeemed points. Shipping is excluded.
     */
    public function summaries(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $query = Order::query()
            ->whereHas('items', fn ($items) => $items->whereNotNull('livestream_id'))
            ->with(['items', 'paymentTransactions']);
        if ($from) $query->where('created_at', '>=', $from->toDateTimeString());
        if ($to) $query->where('created_at', '<', $to->toDateTimeString());

        $byStream = [];
        $total = ['revenue' => 0, 'orders' => 0, 'units' => 0, 'pending_value' => 0, 'pending_orders' => 0];
        foreach ($query->lazyById(100) as $order) {
            $paymentState = $this->paymentState($order);
            if ($paymentState === 'excluded') continue;

            $merchandise = $order->items->sum(fn ($item) => (int) $item->price * (int) $item->quantity);
            if ($merchandise <= 0) continue;
            $discount = min($merchandise, (int) ($order->discount_amount ?? 0) + (int) ($order->points_used ?? 0) * 1000);
            $factor = ($merchandise - $discount) / $merchandise;
            $countedOrder = false;

            foreach ($order->items->whereNotNull('livestream_id')->groupBy('livestream_id') as $streamId => $items) {
                $revenue = $items->sum(fn ($item) => (int) round((int) $item->price * (int) $item->quantity * $factor));
                $units = $items->sum(fn ($item) => (int) $item->quantity);
                $byStream[$streamId] ??= ['revenue' => 0, 'orders' => 0, 'units' => 0, 'pending_value' => 0, 'pending_orders' => 0];
                if ($paymentState === 'paid') {
                    $byStream[$streamId]['revenue'] += $revenue;
                    $byStream[$streamId]['orders']++;
                    $byStream[$streamId]['units'] += $units;
                    $total['revenue'] += $revenue;
                    $total['units'] += $units;
                } else {
                    $byStream[$streamId]['pending_value'] += $revenue;
                    $byStream[$streamId]['pending_orders']++;
                    $total['pending_value'] += $revenue;
                }
                $countedOrder = true;
            }
            if ($countedOrder) $total[$paymentState === 'paid' ? 'orders' : 'pending_orders']++;
        }

        return ['total' => $total, 'by_stream' => $byStream];
    }

    private function paymentState(Order $order): string
    {
        if ($order->status === 'cancelled'
            || in_array($order->shipping_status, ['cancelled', 'return', 'returned'], true)) {
            return 'excluded';
        }

        $terminal = $order->paymentTransactions
            ->whereIn('status', ['paid', 'refund_pending', 'refunded'])
            ->sortByDesc('id')->first();
        if ($terminal) return $terminal->status === 'paid' ? 'paid' : 'excluded';
        if ($order->paymentTransactions->isNotEmpty()) {
            return $order->paymentTransactions->sortByDesc('id')->first()->status === 'pending' ? 'pending' : 'excluded';
        }

        if (in_array($order->status, ['paid', 'cod_paid', 'paid_momo', 'completed'], true)) return 'paid';
        return in_array($order->status, ['pending', 'confirmed', 'cod_ordered'], true) ? 'pending' : 'excluded';
    }
}
