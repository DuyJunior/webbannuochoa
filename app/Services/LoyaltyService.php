<?php

namespace App\Services;

use App\Models\Order;
use App\Support\DemoMode;
use Illuminate\Database\Eloquent\Builder;

class LoyaltyService
{
    private static function orders(int $userId): Builder
    {
        return Order::where('user_id', $userId)->where('is_demo', DemoMode::enabled());
    }

    private static function completedOrders(int $userId): Builder
    {
        return self::orders($userId)->where('status', '!=', 'cancelled')
            ->where(fn ($q) => $q->whereNull('shipping_status')->orWhereNotIn('shipping_status', ['cancelled', 'return', 'returned']))
            ->where(fn ($q) => $q->where('status', 'completed')->orWhere('shipping_status', 'delivered'))
            ->whereDoesntHave('paymentTransactions', fn ($q) => $q->whereIn('status', ['refund_pending', 'refunded']))
            ->where(function ($q) {
                $q->whereHas('paymentTransactions', fn ($p) => $p->where('status', 'paid'))
                    // Imported, completed legacy orders have no transaction history.
                    ->orWhere(fn ($legacy) => $legacy->where('inventory_status', 'legacy')->whereDoesntHave('paymentTransactions'));
            });
    }

    public static function balance(int $userId): int
    {
        $earned = (int) floor(self::totalSpent($userId) / 100000);
        $spent = (int) self::orders($userId)->where('status', '!=', 'cancelled')->sum('points_used');

        return max(0, $earned - $spent);
    }

    public static function totalSpent(int $userId): float
    {
        return (float) self::completedOrders($userId)->sum('total_price');
    }

    public static function tier(int $userId): array
    {
        $spent = self::totalSpent($userId);
        $code = $spent >= 5000000 ? 'premium' : ($spent >= 1500000 ? 'rose' : 'silver');
        $tiers = [
            'silver' => ['Bạc (Silver Member)', 'ribbon', '#718096', '#2d3748', '#f7fafc', 0, 1500000, 'Hoa Hồng (Rose Member)'],
            'rose' => ['Hoa Hồng (Rose Member)', 'flower', '#c2476a', '#4a1525', '#ffe4e6', 1500000, 5000000, 'Hoàng Gia (Premium VIP)'],
            'premium' => ['Hoàng Gia (Premium VIP)', 'crown', '#d4af37', '#1f1b24', '#fef3c7', 5000000, 5000000, null],
        ];
        [$name, $badge, $color, $dark, $text, $lower, $upper, $next] = $tiers[$code];

        return [
            'code' => $code, 'name' => $name, 'badge' => $badge, 'color' => $color,
            'gradient' => "linear-gradient(135deg, {$dark}, {$color})", 'text_color' => $text,
            'point_rate' => '100.000đ tích 1 điểm', 'discount_percent' => 0,
            'progress_percent' => $next ? max(0, min(100, round(($spent - $lower) / ($upper - $lower) * 100))) : 100,
            'next_tier' => $next, 'needed_amount' => max(0, $upper - $spent), 'spent' => $spent,
            'perks' => ['100.000đ chi tiêu hợp lệ tích 1 điểm', '1 điểm giảm 1.000đ khi thanh toán', 'Đổi điểm tối đa 20% tiền hàng sau mã giảm giá'],
        ];
    }
}
