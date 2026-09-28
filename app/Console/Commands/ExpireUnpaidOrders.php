<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderInventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUnpaidOrders extends Command
{
    protected $signature = 'orders:expire-unpaid';

    protected $description = 'Release inventory for expired, unpaid online orders with no shipment';

    public function handle(OrderInventoryService $inventory): int
    {
        $count = 0;
        Order::where('status', 'pending')->where('payment_expires_at', '<=', now())
            ->orderBy('id')->chunkById(100, function ($orders) use ($inventory, &$count) {
                foreach ($orders as $candidate) {
                    DB::transaction(function () use ($candidate, $inventory, &$count) {
                        $order = Order::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
                        if ($order->status !== 'pending' || ! $order->payment_expires_at || $order->payment_expires_at->isFuture()
                            || $order->ghn_order_code || ! in_array($order->shipping_status, [null, 'pending', 'not_shipped'], true)
                            || $order->paymentTransactions()->whereIn('status', ['paid', 'refund_pending', 'refunded'])->exists()
                            || $order->paymentTransactions()->where('gateway', 'cod')->exists()) {
                            return;
                        }
                        $inventory->release($order);
                        $order->paymentTransactions()->whereIn('status', ['pending', 'initiated'])->update(['status' => 'cancelled']);
                        $order->update(['status' => 'cancelled', 'shipping_status' => 'cancelled']);
                        $count++;
                    });
                }
            });
        $this->info("Expired {$count} unpaid orders.");

        return self::SUCCESS;
    }
}
