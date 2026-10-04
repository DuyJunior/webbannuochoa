<?php

namespace App\Services;

use App\Jobs\CreateSePayShipment;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Support\DemoMode;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SePayService
{
    public function ready(): bool
    {
        return ! DemoMode::enabled() && (bool) config('sepay.enabled')
            && config('queue.connections.database.driver') === 'database'
            && (config('queue.connections.database.connection') ?: config('database.default')) === config('database.default')
            && strlen((string) config('sepay.webhook_secret')) >= 32
            && preg_match('/^[A-Z]{2,5}$/D', (string) config('sepay.payment_prefix')) === 1
            && trim((string) config('sepay.bank')) !== ''
            && trim((string) config('sepay.account_number')) !== ''
            && trim((string) config('sepay.account_name')) !== ''
            && (strcasecmp((string) config('sepay.bank'), 'BIDV') !== 0 || trim((string) config('sepay.sub_account')) !== '');
    }

    public function validSignature(Request $request): bool
    {
        $timestamp = (string) $request->header('X-SePay-Timestamp', '');
        $signature = (string) $request->header('X-SePay-Signature', '');
        if (! $this->ready() || ! preg_match('/^[0-9]{10}$/D', $timestamp)
            || abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        return hash_equals('sha256='.hash_hmac('sha256', $timestamp.'.'.$request->getContent(),
            (string) config('sepay.webhook_secret')), $signature);
    }

    /** Called within checkout's transaction, after its inventory reservation. */
    public function prepare(PaymentTransaction $payment): void
    {
        if ($payment->id > 99999999) {
            throw ValidationException::withMessages(['payment_method' => __('Không thể tạo mã thanh toán. Vui lòng liên hệ cửa hàng.')]);
        }
        $payment->update([
            'gateway_order_id' => config('sepay.payment_prefix').str_pad((string) $payment->id, 8, '0', STR_PAD_LEFT),
            'request_payload' => [
                'bank' => config('sepay.bank'),
                'account_number' => config('sepay.account_number'),
                'sub_account' => config('sepay.sub_account'),
                'account_name' => config('sepay.account_name'),
            ],
        ]);
    }

    public function qrUrl(PaymentTransaction $payment): string
    {
        $receiver = $payment->request_payload;

        return 'https://vietqr.app/img?'.http_build_query([
            'acc' => $receiver['sub_account'] ?: $receiver['account_number'],
            'bank' => $receiver['bank'], 'amount' => (int) $payment->amount,
            // Build the compact frame locally: the provider's compact image adds
            // SePay branding for some BIDV VAs instead of the requested VietQR logo.
            'des' => $payment->gateway_order_id,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function canPay(Order $order): bool
    {
        return $order->status === 'pending' && $order->inventory_status === 'reserved'
            && ! $order->ghn_order_code
            && in_array($order->shipping_status, [null, 'pending', 'not_shipped'], true)
            && $order->payment_expires_at && $order->payment_expires_at->isFuture()
            && ! $order->paymentTransactions()->whereIn('status', ['paid', 'refund_pending', 'refunded'])->exists()
            && ! $order->paymentTransactions()->where('gateway', 'cod')->exists();
    }

    public function shipmentNeedsReview(Order $order): bool
    {
        return ! $order->ghn_order_code && DB::table('sepay_webhook_receipts')
            ->whereIn('payment_transaction_id', $order->paymentTransactions()->where('gateway', 'sepay')->select('id'))
            ->where('result', 'paid')->whereNotNull('shipment_attempted_at')->exists();
    }

    /** Only authenticated, validated JSON reaches here. No network calls in the webhook. */
    public function receive(array $data): void
    {
        if ($data['transferType'] !== 'in') {
            return;
        }
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $candidate = $code === '' ? null : PaymentTransaction::where('gateway', 'sepay')->where('gateway_order_id', $code)->first();
        $receiver = $candidate?->request_payload ?? [
            'bank' => config('sepay.bank'), 'account_number' => config('sepay.account_number'),
            'sub_account' => config('sepay.sub_account'),
        ];
        // Validate both the underlying account and the VA, never just one of them.
        if (strcasecmp($data['gateway'], (string) ($receiver['bank'] ?? '')) !== 0
            || $data['accountNumber'] !== ($receiver['account_number'] ?? '')
            || (string) ($data['subAccount'] ?? '') !== (string) ($receiver['sub_account'] ?? '')) {
            return;
        }

        try {
            DB::transaction(function () use ($data, $candidate, $code) {
                // Same lock order as cancellation and expiration: order, then payment.
                $order = $candidate ? Order::whereKey($candidate->order_id)->lockForUpdate()->firstOrFail() : null;
                $payment = $candidate ? PaymentTransaction::whereKey($candidate->id)->lockForUpdate()->firstOrFail() : null;
                if (DB::table('sepay_webhook_receipts')->where('provider_id', (string) $data['id'])->exists()) {
                    return;
                }
                $receiptId = DB::table('sepay_webhook_receipts')->insertGetId([
                    'provider_id' => (string) $data['id'], 'payment_transaction_id' => $payment?->id,
                    'payment_code' => $code ?: null, 'amount' => $data['transferAmount'],
                    'reference_code' => $data['referenceCode'] ?? null,
                    'result' => 'unmatched', 'received_at' => now(),
                ]);
                $result = 'unmatched';
                if ($payment && $order) {
                    if ($order->is_demo) {
                        $result = 'demo_order';
                    } elseif (in_array($payment->status, ['paid', 'refund_pending', 'refunded'], true)
                        || $order->paymentTransactions()->whereIn('status', ['paid', 'refund_pending', 'refunded'])->exists()) {
                        $result = 'duplicate_payment';
                    } elseif (bccomp((string) $data['transferAmount'], (string) $payment->amount, 2) !== 0
                        || bccomp((string) $payment->amount, (string) $order->total_price, 2) !== 0) {
                        $result = 'amount_mismatch';
                    } elseif ($order->status === 'cancelled'
                        || ($order->status === 'pending' && $order->payment_expires_at?->isPast()
                            && ! $order->ghn_order_code && in_array($order->shipping_status, [null, 'pending', 'not_shipped'], true))) {
                        if ($order->status === 'pending') {
                            app(OrderInventoryService::class)->release($order);
                            $order->update(['status' => 'cancelled', 'shipping_status' => 'cancelled']);
                        }
                        $this->recordPayment($payment, $data, 'refund_pending');
                        $result = 'late_payment';
                    } elseif ($this->canPay($order) && in_array($payment->status, ['pending', 'initiated'], true)) {
                        $this->recordPayment($payment, $data, 'paid');
                        $order->update(['status' => 'paid']);
                        app(OrderEmailService::class)->paid($order);
                        // The database queue insert commits/rolls back with this receipt.
                        CreateSePayShipment::dispatch($order->id)->onConnection('database')->onQueue('default')->beforeCommit();
                        $result = 'paid';
                    } else {
                        $result = 'order_not_payable';
                    }
                }
                DB::table('sepay_webhook_receipts')->where('id', $receiptId)->update(['result' => $result]);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            // Concurrent deliveries of one SePay ID may target different order locks.
            if (! DB::table('sepay_webhook_receipts')->where('provider_id', (string) $data['id'])->exists()) {
                throw $exception;
            }
        }
    }

    private function recordPayment(PaymentTransaction $payment, array $data, string $status): void
    {
        $payment->update([
            'transaction_id' => (string) $data['id'], 'status' => $status, 'paid_at' => now(),
            'response_payload' => ['id' => $data['id'], 'referenceCode' => $data['referenceCode'] ?? null],
            'message' => $status === 'paid' ? 'SePay: payment verified' : 'SePay: late payment requires refund review',
        ]);
    }
}
