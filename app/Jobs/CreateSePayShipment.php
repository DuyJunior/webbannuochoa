<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\GHNOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateSePayShipment implements ShouldQueue
{
    use Queueable;

    // A timed-out external create may have succeeded. Do not automatically create twice.
    public int $tries = 1;

    public int $timeout = 40;

    public function __construct(public int $orderId) {}

    public function handle(GHNOrderService $shipping): void
    {
        // Persist the attempt before contacting GHN: a worker crash must not create it twice.
        $claimed = DB::transaction(function () {
            $order = Order::whereKey($this->orderId)->lockForUpdate()->first();
            if (! $order || $order->is_demo || $order->status !== 'paid' || $order->ghn_order_code) {
                return false;
            }
            $paymentIds = $order->paymentTransactions()->where('gateway', 'sepay')->where('status', 'paid')->pluck('id');
            $receipt = DB::table('sepay_webhook_receipts')->whereIn('payment_transaction_id', $paymentIds)
                ->where('result', 'paid')->lockForUpdate()->first();
            if (! $receipt || $receipt->shipment_attempted_at) {
                return false;
            }
            DB::table('sepay_webhook_receipts')->where('id', $receipt->id)->update(['shipment_attempted_at' => now()]);

            return true;
        });
        if (! $claimed) {
            return;
        }
        DB::transaction(function () use ($shipping) {
            $order = Order::whereKey($this->orderId)->lockForUpdate()->first();
            if (! $order || $order->is_demo || $order->status !== 'paid' || $order->ghn_order_code
                || ! in_array($order->shipping_status, [null, 'pending', 'not_shipped'], true)
                || ! $order->paymentTransactions()->where('gateway', 'sepay')->where('status', 'paid')->exists()) {
                return;
            }
            // Serialize against cancellation while creating the shipment.
            $order->load('items.product');
            try {
                $result = $shipping->create($order, true);
                if (($result['code'] ?? null) === 200 && ! empty($result['data']['order_code'])) {
                    $order->update(['ghn_order_code' => $result['data']['order_code'], 'shipping_status' => 'ready_to_pick']);

                    return;
                }
            } catch (Throwable $exception) {
                Log::warning('SePay paid order needs shipment review', ['order_id' => $order->id]);
            }
            // Payment remains paid. Admin can inspect/reconcile GHN before any manual retry.
            Log::warning('GHN shipment not confirmed for SePay order', ['order_id' => $order->id]);
        });
    }
}
