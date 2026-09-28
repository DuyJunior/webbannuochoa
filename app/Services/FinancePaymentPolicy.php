<?php

namespace App\Services;

use App\Models\Order;

class FinancePaymentPolicy
{
    public const STATUS_LABELS = [
        'pending' => 'Chờ thanh toán',
        'initiated' => 'Đang xử lý',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thanh toán thất bại',
        'cancelled' => 'Đã hủy',
        'refund_pending' => 'Chờ hoàn tiền',
        'refunded' => 'Đã hoàn tiền',
    ];

    public const GATEWAY_LABELS = ['cod' => 'COD', 'momo' => 'MoMo', 'demo' => 'Mô phỏng', 'unknown' => 'Chưa xác định'];

    private const TRANSITIONS = [
        'pending' => ['pending', 'paid', 'failed'],
        'failed' => ['failed', 'pending', 'paid'],
        'paid' => ['paid', 'refund_pending'],
        'refund_pending' => ['refund_pending', 'refunded'],
        'refunded' => ['refunded'],
        'cancelled' => ['cancelled'],
    ];

    public static function gateway(Order $order): string
    {
        return match ($order->status) {
            'cod_ordered', 'cod_paid' => 'cod',
            'paid', 'paid_momo' => 'momo',
            default => 'unknown',
        };
    }

    public static function status(Order $order): string
    {
        return match ($order->status) {
            'paid', 'paid_momo', 'cod_paid', 'completed' => 'paid',
            'cancelled', 'failed', 'refund_pending', 'refunded', 'initiated' => $order->status,
            default => 'pending',
        };
    }

    /** The form and the write endpoint share this policy. No shipping/inventory changes here. */
    public static function allowedTransitions(Order $order, string $paymentStatus, string $gateway): array
    {
        if ($gateway !== 'cod') {
            return [];
        }

        $blockedStatuses = ['cancelled', 'return', 'returning', 'returned', 'return_transporting', 'return_sorting'];
        $cannotCollect = in_array($order->status, $blockedStatuses, true)
            || in_array($order->shipping_status, $blockedStatuses, true);

        return array_values(array_filter(self::TRANSITIONS[$paymentStatus] ?? [],
            fn (string $next) => ! in_array($next, ['pending', 'paid'], true) || $next === $paymentStatus || ! $cannotCollect));
    }
}
