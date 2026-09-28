<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ShippingUpdateService
{
    public function apply(Order $candidate, string $status): void
    {
        $ranks = ['pending' => 0, 'not_shipped' => 0, 'processing' => 1, 'ready_to_pick' => 2,
            'picking' => 3, 'picked' => 4, 'storing' => 5, 'transporting' => 5, 'sorting' => 5,
            'delivering' => 6, 'delivery_fail' => 6, 'delivered' => 7,
            'return' => 8, 'returning' => 8, 'return_transporting' => 8, 'return_sorting' => 8, 'returned' => 9];
        $status = $status === 'cancel' ? 'cancelled' : $status;
        if (! isset($ranks[$status]) && $status !== 'cancelled') {
            return;
        }
        DB::transaction(function () use ($candidate, $status, $ranks) {
            $order = Order::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            if ($order->is_demo || in_array($order->status, ['cancelled', 'completed'], true)
                || in_array($order->shipping_status, ['cancelled', 'returned', 'delivered'], true)) {
                return;
            }
            if ($status === 'cancelled') {
                // Do not release goods already handed to the carrier without a return inspection.
                if (! in_array($order->shipping_status, [null, 'pending', 'not_shipped', 'processing', 'ready_to_pick', 'picking'], true)) {
                    return;
                }
                app(OrderInventoryService::class)->release($order);
                $order->paymentTransactions()->where('status', 'paid')->update(['status' => 'refund_pending']);
                $order->paymentTransactions()->whereIn('status', ['pending', 'initiated'])->update(['status' => 'cancelled']);
                $order->update(['status' => 'cancelled', 'shipping_status' => 'cancelled']);

                return;
            }
            if (($ranks[$status] ?? 0) < ($ranks[$order->shipping_status] ?? 0)) {
                return;
            }
            $updates = ['shipping_status' => $status];
            if ($status === 'delivered') {
                $updates['status'] = 'completed';
                $order->paymentTransactions()->where('gateway', 'cod')->where('status', 'pending')
                    ->update(['status' => 'paid', 'paid_at' => now()]);
            }
            // A carrier return is not proof that the goods passed inspection: no automatic restock/refund.
            $order->update($updates);
        });
    }
}
