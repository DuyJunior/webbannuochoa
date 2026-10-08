<?php

namespace App\Support;

use App\Models\Order;

class OrderJourney
{
    public static function for(Order $order): array
    {
        $shipping = $order->shipping_status ?: 'pending';
        if ($order->status === 'cancelled' || $shipping === 'cancelled') {
            return ['step' => null, 'tone' => 'muted', 'label' => __('Đã hủy')];
        }
        $labels = [
            'pending' => 'Chờ xác nhận', 'not_shipped' => 'Chưa giao', 'processing' => 'Đang xử lý',
            'ready_to_pick' => 'Chờ lấy hàng', 'picking' => 'Đang lấy hàng', 'picked' => 'Đã lấy hàng',
            'storing' => 'Đang lưu kho', 'transporting' => 'Đang trung chuyển', 'sorting' => 'Đang phân loại',
            'delivering' => 'Đang giao hàng', 'delivered' => 'Giao thành công',
            'delivery_fail' => 'Giao hàng chưa thành công', 'waiting_to_return' => 'Chờ hoàn hàng',
            'return' => 'Chờ hoàn hàng', 'returning' => 'Đang hoàn hàng', 'returned' => 'Đã hoàn hàng',
            'return_transporting' => 'Đang chuyển hoàn', 'return_sorting' => 'Đang phân loại hoàn',
            'return_fail' => 'Hoàn hàng chưa thành công', 'exception' => 'Vận chuyển cần kiểm tra',
            'lost' => 'Vận chuyển cần kiểm tra', 'damage' => 'Vận chuyển cần kiểm tra',
        ];
        $steps = ['pending'=>1, 'not_shipped'=>1, 'processing'=>1, 'ready_to_pick'=>2, 'picking'=>2,
            'picked'=>2, 'storing'=>2, 'transporting'=>2, 'sorting'=>2, 'delivering'=>3, 'delivered'=>4];
        $step = $steps[$shipping] ?? null;

        return ['step' => $step, 'tone' => $step === 4 ? 'mint' : ($step ? 'rose' : 'muted'),
            'label' => __($labels[$shipping] ?? 'Đang cập nhật vận chuyển')];
    }
}
