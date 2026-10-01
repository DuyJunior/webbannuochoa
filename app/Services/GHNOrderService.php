<?php

namespace App\Services;

use App\Models\Order;

class GHNOrderService
{
    public function __construct(private GHNService $ghn) {}

    public function create(Order $order, bool $isPaid = false): array
    {
        if ($order->is_demo) {
            return ['code' => 200, 'data' => ['order_code' => 'DEMO-'.$order->id]];
        }
        // Tự động nhận diện nếu đơn hàng đã được thanh toán (MoMo / Online)
        $isPaid = $isPaid || $order->status === 'paid';

        $items = [];
        $weight = 0;

        foreach ($order->items as $item) {
            $product = $item->product ?? $item->perfume;
            $isDiscovery = count($item->stock_components ?? []) > 1;
            $itemWeight = $isDiscovery ? 50 * count($item->stock_components)
                : (($product && method_exists($product, 'getWeightForVolume'))
                    ? $product->getWeightForVolume($item->volume_ml)
                    : (int) ($product?->weight ?? 200));
            if ($itemWeight <= 0) {
                $itemWeight = 200;
            }
            $weight += $itemWeight * (int) $item->quantity;
            $items[] = [
                'name' => $isDiscovery ? 'Hộp thử mùi · '.count($item->stock_components).' mẫu 5ml' : ($item->product_name ?? $product->name ?? 'Sản phẩm'),
                'quantity' => (int) $item->quantity,
                'price' => (int) $item->price,
                'weight' => $itemWeight,
            ];
        }

        $pkg = $this->ghn->packageParameters($weight);

        return $this->ghn->createOrder([
            // 1: Người gửi trả cước (Shop trả phí ship).
            // Khi đã thanh toán Online/MoMo: payment_type_id = 1 và cod_amount = 0 => Shipper KHÔNG thu bất kỳ tiền nào của người nhận (Tổng thu = 0đ)
            'payment_type_id' => 1,
            'note' => 'Đơn hàng #'.$order->id.($isPaid ? ' (ĐÃ THANH TOÁN ONLINE MOMO - KHÔNG THU TIỀN KHÁCH)' : ' (Thu tiền COD khi nhận hàng)'),
            'required_note' => 'KHONGCHOXEMHANG',
            'to_name' => $order->name ?? $order->customer_name,
            'to_phone' => $order->phone,
            'to_address' => $order->address,
            'to_ward_code' => (string) $order->to_ward_code,
            'to_district_id' => (int) $order->to_district_id,
            'cod_amount' => $isPaid ? 0 : (int) $order->total_price,
            'weight' => $pkg['weight'],
            'length' => $pkg['length'],
            'width' => $pkg['width'],
            'height' => $pkg['height'],
            'service_type_id' => 2,
            'items' => $items,
        ]);
    }
}
