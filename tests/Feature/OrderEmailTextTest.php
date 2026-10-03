<?php

namespace Tests\Feature;

use App\Mail\OrderStatusMail;
use Tests\TestCase;

class OrderEmailTextTest extends TestCase
{
    public function test_plain_text_preserves_literal_customer_product_address_and_url_characters(): void
    {
        $details = $this->details();
        $mail = new OrderStatusMail($details, 1);

        foreach ([$details['customer_name'], $details['items'][0]['name'], $details['shipping_address'], $details['tracking_url']] as $value) {
            $mail->assertSeeInText($value)->assertDontSeeInText(e($value));
        }
    }

    public function test_html_still_escapes_customer_product_address_and_url_characters(): void
    {
        $details = $this->details();
        $mail = new OrderStatusMail($details, 1);

        foreach ([$details['customer_name'], $details['items'][0]['name'], $details['shipping_address'], $details['tracking_url']] as $value) {
            $mail->assertSeeInHtml($value)->assertDontSeeInHtml($value, false);
        }
    }

    private function details(): array
    {
        return [
            'type' => 'placed',
            'order_number' => '#DH00001',
            'customer_name' => "An & O'Neil <người nhận>",
            'placed_at' => '2026-10-03T10:00:00+07:00',
            'items' => [[
                'name' => "D&G L'Impératrice <100 ml>",
                'volume_ml' => 100, 'quantity' => 1, 'line_total' => 1500000,
            ]],
            'subtotal' => 1500000, 'shipping_fee' => 30000,
            'discount_amount' => 0, 'points_discount' => 0, 'total' => 1530000,
            'payment_method' => 'COD', 'payment_status' => 'Chưa thanh toán',
            'shipping_address' => "12 A&B, cổng O'Neil <tầng 2>",
            'tracking_code' => 'GHN-TEST-001',
            'tracking_url' => 'https://shop.example.test/orders/1?from=email&source=confirmation',
            'is_demo' => false,
        ];
    }
}
