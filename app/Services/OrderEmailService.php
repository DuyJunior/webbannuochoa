<?php

namespace App\Services;

use App\Jobs\SendOrderStatusEmail;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\OrderEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

class OrderEmailService
{
    private const DISPATCHED = ['picked', 'storing', 'transporting', 'sorting', 'delivering', 'delivery_fail'];

    private const STOPPED = ['cancelled', 'return', 'returning', 'return_transporting', 'return_sorting', 'returned'];

    public function placed(Order $order): void
    {
        $this->record($order, 'placed');
    }

    public function paid(Order $order): void
    {
        if ($this->hasPayment($order)) {
            $this->record($order, 'paid');
        }
    }

    public function shipping(Order $order): void
    {
        if ($order->shipping_status === 'delivered') {
            $this->record($order, 'delivered');
        } elseif (in_array($order->shipping_status, self::DISPATCHED, true)) {
            $this->record($order, 'dispatched');
        }
    }

    private function record(Order $order, string $type): void
    {
        if ($this->isDemo($order) || $this->stopped($order)) {
            return;
        }
        $recipient = (string) $order->user()->value('email');
        // Keep invalid/unverified recipients visible in the outbox; delivery rechecks ownership.
        $email = OrderEmail::firstOrCreate(['order_id' => $order->id, 'type' => $type], [
            'recipient' => $recipient,
            'details' => $this->details($order, $type),
            'available_at' => now(),
        ]);

        DB::afterCommit(fn () => $this->enqueue($email->id));
    }

    private function due(): Builder
    {
        return OrderEmail::query()->whereNull('sent_at')->whereNull('skipped_at')
            ->where(fn (Builder $query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('queued_at')->orWhere('queued_at', '<=', now()->subMinutes(10)))
            ->where(fn (Builder $query) => $query->whereNull('processing_at')->orWhere('processing_at', '<=', now()->subMinutes(10)));
    }

    public function enqueuePending(int $limit = 100): int
    {
        $count = 0;
        foreach ($this->due()->orderBy('id')->limit($limit)->pluck('id') as $id) {
            $count += (int) $this->enqueue($id);
        }

        return $count;
    }

    public function enqueue(int $id): bool
    {
        try {
            // Atomic claim prevents concurrent scanners from making duplicate jobs.
            if (! $this->due()->whereKey($id)->update(['queued_at' => now()])) {
                return false;
            }
            Queue::connection('database')->pushOn('default', new SendOrderStatusEmail($id));

            return true;
        } catch (Throwable $exception) {
            // The business transaction is already committed. Recovery picks this up again.
            Log::warning('Order email could not be queued.', ['email_id' => $id, 'exception_class' => $exception::class]);
            try {
                OrderEmail::whereKey($id)->whereNull('sent_at')->update([
                    'queued_at' => null, 'available_at' => now()->addMinute(), 'last_error' => 'queue_unavailable',
                ]);
            } catch (Throwable) {
                // A database outage is recovered by the stale-claim scan after it returns.
            }

            return false;
        }
    }

    public function deliver(int $id): void
    {
        $delivery = DB::transaction(function () use ($id): ?array {
            // The short lease prevents duplicate workers without holding SQLite/MySQL locks over SMTP.
            $token = (string) Str::uuid();
            $claimed = OrderEmail::whereKey($id)->whereNull('sent_at')->whereNull('skipped_at')
                ->where(fn (Builder $query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
                ->where(fn (Builder $query) => $query->whereNull('processing_at')->orWhere('processing_at', '<=', now()->subMinutes(10)))
                ->update(['processing_at' => now(), 'processing_token' => $token]);
            if (! $claimed) {
                return null;
            }
            $email = OrderEmail::whereKey($id)->lockForUpdate()->first();
            $order = $email->order()->with('user')->first();
            $skip = ! $order ? 'order_missing' : ($this->isDemo($order) ? 'demo_suppressed' : null);
            if ($order && ($this->stopped($order)
                || ($email->type === 'paid' && ! $this->hasPayment($order))
                || ($email->type === 'dispatched' && ! in_array($order->shipping_status, self::DISPATCHED, true))
                || ($email->type === 'delivered' && $order->shipping_status !== 'delivered'))) {
                $skip = 'milestone_no_longer_current';
            }
            if ($order && $order->user?->email !== $email->recipient) {
                $skip = 'recipient_changed';
            }
            if ($skip) {
                $email->update(['skipped_at' => now(), 'queued_at' => null, 'processing_at' => null, 'processing_token' => null, 'last_error' => $skip]);

                return null;
            }
            $blocked = ! config('order_emails.enabled', true) ? 'delivery_disabled' : null;
            if (! $this->realMailer($this->mailer())) {
                $blocked = 'mail_transport_not_live';
            }
            if (! filter_var($email->recipient, FILTER_VALIDATE_EMAIL) || ! $order->user?->hasVerifiedEmail()) {
                $blocked = 'recipient_unverified';
            }
            if ($blocked) {
                $email->update(['queued_at' => null, 'processing_at' => null, 'processing_token' => null,
                    'available_at' => now()->addMinutes(5), 'last_error' => $blocked]);

                return null;
            }

            $email->attempts++;
            $email->save();

            // Prices/items remain the recorded receipt. Status and tracking reflect send time.
            $paymentStatus = $order->paymentTransactions()
                ->orderByRaw("CASE WHEN status IN ('refund_pending', 'refunded') THEN 0 WHEN status = 'paid' THEN 1 ELSE 2 END")
                ->latest('id')->value('status');
            $details = array_replace($email->details, [
                'payment_status' => FinancePaymentPolicy::STATUS_LABELS[$paymentStatus] ?? 'Chưa thanh toán',
                'tracking_code' => $order->ghn_order_code,
            ]);

            return ['email' => $email, 'details' => $details];
        });
        if (! $delivery) {
            return;
        }

        $email = $delivery['email'];
        $result = ['queued_at' => null, 'processing_at' => null, 'processing_token' => null];
        try {
            Mail::mailer($this->mailer())->to($email->recipient)->send(new OrderStatusMail($delivery['details'], $email->id));
            $result += ['sent_at' => now(), 'last_error' => null];
        } catch (Throwable $exception) {
            // Do not store provider responses: they can contain addresses or credentials.
            $result += ['last_error' => 'delivery_failed', 'available_at' => now()->addMinutes(min(60, 2 ** min($email->attempts, 6)))];
            Log::warning('Order email delivery will retry.', ['email_id' => $id, 'exception_class' => $exception::class]);
        }
        OrderEmail::whereKey($id)->where('processing_token', $email->processing_token)->update($result);
    }

    public function mailer(): string
    {
        return (string) (config('order_emails.mailer') ?: config('mail.default'));
    }

    public function realMailer(string $name, array $seen = []): bool
    {
        if (in_array($name, $seen, true)) {
            return false;
        }
        $mailer = config('mail.mailers.'.$name, []);
        $transport = $mailer['transport'] ?? null;
        if (in_array($transport, ['failover', 'roundrobin'], true)) {
            $children = $mailer['mailers'] ?? [];

            return $children !== [] && collect($children)->every(fn ($child) => $this->realMailer($child, [...$seen, $name]));
        }

        return in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'mailgun'], true);
    }

    private function isDemo(Order $order): bool
    {
        return $order->is_demo || $order->paymentTransactions()->where('gateway', 'demo')->exists();
    }

    private function stopped(Order $order): bool
    {
        return in_array($order->status, [...self::STOPPED, 'refund_pending', 'refunded'], true)
            || in_array($order->shipping_status, self::STOPPED, true);
    }

    private function hasPayment(Order $order): bool
    {
        return ! $order->paymentTransactions()->whereIn('status', ['refund_pending', 'refunded'])->exists()
            && $order->paymentTransactions()->where('status', 'paid')->where('gateway', '!=', 'demo')->exists();
    }

    private function details(Order $order, string $type): array
    {
        $order->load('items.perfume');
        $payment = $order->paymentTransactions()->orderByRaw("CASE WHEN status = 'paid' THEN 0 ELSE 1 END")->latest('id')->first();
        $items = $order->items->map(function ($item) {
            return [
                'name' => $item->display_name,
                'quantity' => (int) $item->quantity, 'volume_ml' => (int) $item->volume_ml,
                'volume_label' => $item->volume_label, 'is_gift_bundle' => $item->is_gift_bundle,
                'unit_price' => (int) $item->price, 'line_total' => (int) $item->price * $item->quantity,
                'sample_names' => $item->sample_names,
            ];
        })->all();

        return [
            'type' => $type, 'order_id' => $order->id, 'order_number' => '#DH'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
            'customer_name' => $order->customer_name ?: $order->name,
            'placed_at' => $order->created_at->toIso8601String(), 'occurred_at' => now()->toIso8601String(),
            'items' => $items, 'subtotal' => array_sum(array_column($items, 'line_total')),
            'shipping_fee' => (int) $order->ghn_total_fee, 'discount_amount' => (int) $order->discount_amount,
            'points_discount' => (int) $order->points_used * 1000, 'total' => (int) $order->total_price, 'currency' => 'VND',
            'payment_method' => match ($payment?->gateway) { 'cod' => 'COD', 'momo' => 'MoMo', default => 'Chưa xác định' },
            'payment_status' => $payment?->status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán',
            'shipping_address' => $order->address, 'tracking_code' => $order->ghn_order_code, 'note' => $order->note,
            'tracking_url' => rtrim((string) config('app.url'), '/').route('orders.show', $order, false), 'is_demo' => false,
        ];
    }
}
