<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDetailPresentationTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\SePayRequests;

    private function purchase(): Order
    {
        if (!getenv('SOOPI_ORDER_EXPORT')) $this->withoutVite();
        $user = User::factory()->create(['name' => 'Khách trải nghiệm']);
        $this->actingAs($user);
        $product = Perfume::create(['name' => 'Miss Dior Blooming Bouquet EDT', 'slug' => 'receipt-test',
            'brand' => 'Dior', 'gender' => 'nu', 'volume_ml' => 100, 'price' => 750000,
            'stock' => 10, 'is_active' => true, 'image_url' => 'images/products/miss-dior-blooming.jpg']);
        $order = Order::create(['user_id' => $user->id, 'name' => 'Khách trải nghiệm', 'phone' => '0900000000',
            'address' => 'Địa chỉ minh họa, phường Điện Biên, quận Ba Đình, Hà Nội',
            'note' => 'Vui lòng gọi trước khi giao.', 'status' => 'cod_ordered', 'shipping_status' => 'ready_to_pick',
            'ghn_order_code' => 'SOOPI-TEST-42', 'total_price' => 711500, 'ghn_total_fee' => 71500,
            'discount_amount' => 100000, 'coupon_code' => 'WELCOME', 'points_used' => 10]);
        $order->items()->create(['perfume_id' => $product->id, 'price' => 750000, 'quantity' => 1, 'volume_ml' => 10]);
        return $order;
    }

    public function test_receipt_explains_discounts_and_preserves_order_actions_in_both_languages(): void
    {
        $order = $this->purchase();
        foreach (['vi' => 'Chờ lấy hàng', 'en' => 'Awaiting pickup'] as $locale => $label) {
            $response = $this->withSession(['locale' => $locale])->get(route('orders.show', $order))->assertOk()
                ->assertSee('750.000')->assertSee('71.500')->assertSee('−100.000')->assertSee('−10.000')->assertSee('711.500')
                ->assertSee('WELCOME')->assertSee($label)->assertSee(route('orders.cancel', $order), false)
                ->assertSee(route('gifts.create', ['order_id'=>$order->id, 'perfume_id'=>$order->items->first()->perfume_id]))
                ->assertSee('data-order-cancel', false)->assertDontSee('class="order-review-form"', false)
                ->assertSee('aria-current="step"', false);
            $this->export($response, $locale, 'ready');
        }
    }

    public function test_returned_cancelled_and_unknown_shipping_never_claim_normal_delivery_progress(): void
    {
        $order = $this->purchase();
        foreach (['returned', 'cancelled', 'exception', 'unknown'] as $shipping) {
            $order->update(['shipping_status' => $shipping]);
            $response = $this->get(route('orders.show', $order))->assertOk()
                ->assertDontSee('class="of-progress"', false)->assertDontSee('data-order-cancel', false)
                ->assertDontSee('class="order-review-form"', false);
            $this->export($response, 'vi', $shipping);
        }
        $order->update(['status' => 'cancelled', 'shipping_status' => 'pending']);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Đơn hàng đã hủy')
            ->assertDontSee('class="of-progress"', false)->assertDontSee('data-order-cancel', false);
    }

    public function test_delivered_order_keeps_review_forms_gifts_and_escaped_customer_content(): void
    {
        $order = $this->purchase();
        $order->update(['status'=>'completed', 'shipping_status'=>'delivered', 'gift_wrap'=>'Ivory',
            'gift_card'=>'Soopi', 'gift_message'=>'Một món quà dành riêng cho bạn. <script>alert(1)</script>',
            'gift_delivery_date'=>'2026-10-10']);
        foreach (['vi', 'en'] as $locale) {
            $response = $this->withSession(['locale'=>$locale])->get(route('orders.show', $order))->assertOk()
                ->assertSee('class="order-review-form"', false)->assertSee('10/10/2026')
                ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
                ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('data-order-cancel', false);
            $this->export($response, $locale, 'delivered');
        }
    }

    public function test_payment_action_is_visible_only_while_the_transfer_can_still_be_paid(): void
    {
        $this->configureSePay();
        $order = $this->purchase();
        $order->update(['status'=>'pending', 'shipping_status'=>'pending', 'ghn_order_code'=>null,
            'inventory_status'=>'reserved', 'payment_expires_at'=>now()->addMinutes(30)]);
        $payment = $order->paymentTransactions()->create(['gateway'=>'sepay', 'status'=>'pending', 'amount'=>$order->total_price]);
        $url = route('user.orders.sepay.pay', $order);
        $this->get(route('orders.show', $order))->assertOk()->assertSee($url, false)->assertSee('Hạn thanh toán:');
        $order->update(['payment_expires_at'=>now()->subMinute()]);
        $this->get(route('orders.show', $order))->assertOk()->assertDontSee($url, false);
        $order->update(['payment_expires_at'=>now()->addMinutes(30), 'status'=>'paid']);
        $payment->update(['status'=>'paid']);
        $this->get(route('orders.show', $order))->assertOk()->assertDontSee($url, false)->assertSee('Đã thanh toán');
    }

    private function export($response, string $locale, string $state): void
    {
        if (getenv('SOOPI_ORDER_EXPORT')) {
            file_put_contents(storage_path('app/order-folio-'.$locale.'-'.$state.'.html'), $response->getContent());
        }
    }
}
