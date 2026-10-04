<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Mail\OrderStatusMail;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use App\Services\SePayService;
use Tests\Concerns\SePayRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PurchaseReliabilityTest extends TestCase
{
    use RefreshDatabase;
    use SePayRequests;

    private int $feeRequests = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Mail::fake();
        Queue::fake();
        config(['demo.enabled' => true]);
        $this->actingAs(User::factory()->create());
        $ghn = $this->createMock(GHNService::class);
        $ghn->method('packageParameters')->willReturn(['weight' => 200, 'length' => 10, 'width' => 10, 'height' => 10]);
        $ghn->method('calculateFee')->willReturnCallback(function () {
            $this->feeRequests++;

            return ['code' => 200, 'data' => ['total' => 30000]];
        });
        $this->app->instance(GHNService::class, $ghn);
    }

    private function cart(): Perfume
    {
        $product = Perfume::create(['name' => 'Rose Test', 'slug' => 'rose-test', 'brand' => 'Soopi',
            'gender' => 'nu', 'volume_ml' => 100, 'weight' => 200, 'price' => 1000000,
            'stock' => 10, 'is_active' => true]);
        $this->withSession(['cart' => [$product->id => 1]]);

        return $product;
    }

    private function address(array $extra = []): array
    {
        return array_replace(['name' => 'Khách Soopi', 'phone' => '0912345678', 'address' => 'Hà Nội',
            'to_district_id' => 1493, 'to_ward_code' => 'DEMO', 'payment_method' => 'sepay'], $extra);
    }

    private function order(array $extra = []): Order
    {
        return Order::create(array_replace(['user_id' => auth()->id(), 'customer_name' => 'Khách Soopi',
            'phone' => '0912345678', 'address' => 'Hà Nội', 'total_price' => 1000000,
            'status' => 'pending', 'shipping_status' => 'pending', 'is_demo' => false], $extra));
    }

    public function test_unchecked_gift_options_are_not_added_to_the_order(): void
    {
        $this->cart();
        $this->post(route('payment.process'), $this->address([
            'gift_wrap' => 'Nhung đỏ rượu vang (Wine Velvet)', 'gift_card' => 'Sinh nhật (Happy Birthday)',
            'gift_message' => ['stale invalid data'], 'gift_delivery_date' => 'not-a-date',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $order = Order::sole();
        foreach (['gift_wrap', 'gift_card', 'gift_message', 'gift_delivery_date'] as $field) {
            $this->assertNull($order->$field);
        }
        $this->assertSame(1030000, (int) $order->total_price);
    }

    public function test_checkout_phone_arrays_are_validation_errors_in_both_checkout_routes(): void
    {
        $product = $this->cart();
        foreach (['cart.checkout', 'payment.process'] as $route) {
            $this->postJson(route($route), $this->address(['customer_name' => 'Khách Soopi', 'phone' => ['0912345678']]))
                ->assertUnprocessable()->assertJsonValidationErrors('phone');
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(0, $this->feeRequests);
    }

    public function test_checkout_validation_return_renders_after_nested_input_without_losing_the_cart(): void
    {
        $product = $this->cart();
        foreach (['checkout_key', 'name', 'phone', 'address', 'note', 'coupon_code', 'points_used'] as $field) {
            $this->from(route('payment.index'))->post(route('payment.process'), $this->address([$field => ['invalid']]))
                ->assertSessionHasErrors($field)->assertRedirect(route('payment.index'));
            $this->get(route('payment.index'))->assertOk()->assertSee('Hoàn tất đơn hàng');
        }
        $this->assertSame([$product->id => 1], session('cart'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_enabled_gift_instructions_are_saved_without_changing_the_quoted_price(): void
    {
        $product = $this->cart();
        $gift = ['enable_gift_service' => '1', 'gift_wrap' => 'Lụa hồng phấn kiêu kỳ (Blush Pink)',
            'gift_card' => 'Tri ân & Cảm ơn (Thank You)', 'gift_message' => 'Chúc bạn một ngày dịu dàng.',
            'gift_delivery_date' => now()->addDays(3)->toDateString()];
        $this->post(route('payment.process'), $this->address($gift))->assertRedirect()->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertDatabaseHas('orders', ['id' => $order->id] + array_diff_key($gift, ['enable_gift_service' => true]));
        $this->assertSame(1030000, (int) $order->total_price);
        $this->assertSame(9, $product->fresh()->stock);
    }

    public function test_malformed_gift_data_fails_before_shipping_or_stock_changes(): void
    {
        $product = $this->cart();
        foreach ([
            ['gift_wrap', ['nested']], ['gift_card', ['nested']], ['gift_message', ['nested']],
            ['gift_message', str_repeat('x', 1001)], ['gift_wrap', str_repeat('x', 101)],
            ['gift_delivery_date', ['nested']], ['gift_delivery_date', '2026-02-30'],
            ['gift_delivery_date', now()->subDay()->toDateString()], ['enable_gift_service', ['nested']],
        ] as [$field, $value]) {
            $this->postJson(route('payment.process'), $this->address(array_replace(['enable_gift_service' => '1'], [$field => $value])))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(0, $this->feeRequests);
    }

    public function test_failed_checkout_preserves_gift_options_and_handles_malformed_old_input(): void
    {
        $this->cart();
        $date = now()->addDay()->toDateString();
        $this->from(route('payment.index'))->post(route('payment.process'), $this->address([
            'name' => '', 'enable_gift_service' => '1', 'gift_wrap' => 'Lụa hồng phấn kiêu kỳ (Blush Pink)',
            'gift_card' => 'Tri ân & Cảm ơn (Thank You)', 'gift_message' => 'Lời nhắn được giữ lại.', 'gift_delivery_date' => $date,
        ]))->assertSessionHasErrors('name');
        $response = $this->get(route('payment.index'))->assertOk()->assertSee('Lời nhắn được giữ lại.')->assertSee('value="'.$date.'"', false);
        $this->assertMatchesRegularExpression('/id="giftWrapToggle"[^>]*checked/u', $response->getContent());
        $this->assertMatchesRegularExpression('/value="Lụa hồng phấn kiêu kỳ \(Blush Pink\)"\s+selected/u', $response->getContent());

        $this->from(route('payment.index'))->post(route('payment.process'), $this->address([
            'enable_gift_service' => '1', 'gift_message' => ['invalid'], 'gift_delivery_date' => ['invalid'],
        ]))->assertSessionHasErrors(['gift_message', 'gift_delivery_date']);
        $this->get(route('payment.index'))->assertOk();
    }

    public function test_order_note_is_preserved_in_customer_admin_shipping_and_email_flows(): void
    {
        $this->configureSePay();
        $this->cart();
        $note = "Gọi trước khi giao & tới cổng sau.\n<script>alert('test')</script>";
        $this->post(route('payment.process'), $this->address(['note' => $note]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame($note, $order->note);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Ghi chú đơn hàng:')
            ->assertSee($note)->assertDontSee($note, false);
        $details = $order->emails()->where('type', 'placed')->firstOrFail()->details;
        $this->assertSame($note, $details['note']);
        $mail = new OrderStatusMail($details, 1);
        $mail->assertSeeInHtml($note)->assertDontSeeInHtml($note, false)->assertSeeInText($note);

        $shipping = $this->createMock(GHNService::class);
        $shipping->method('packageParameters')->willReturn(['weight' => 200, 'length' => 10, 'width' => 10, 'height' => 10]);
        $shipping->expects($this->once())->method('createOrder')
            ->with($this->callback(fn ($payload) => str_contains($payload['note'], $note)
                && str_contains($payload['note'], 'Thu tiền COD') && $payload['cod_amount'] === 1030000))
            ->willReturn(['code' => 200, 'data' => ['order_code' => 'TEST-NOTE']]);
        (new GHNOrderService($shipping))->create($order);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin.orders.show', $order))->assertOk()->assertSee('Ghi chú đơn hàng:')
            ->assertSee($note)->assertDontSee($note, false);
        Http::assertNothingSent();
    }

    public function test_legacy_checkout_saves_note_and_note_validation_never_loses_cart(): void
    {
        $product = $this->cart();
        foreach (['cart.checkout', 'payment.process'] as $route) {
            foreach ([['nested'], str_repeat('x', 501)] as $invalid) {
                $this->postJson(route($route), $this->address(['customer_name' => 'Khách Soopi', 'note' => $invalid]))
                    ->assertUnprocessable()->assertJsonValidationErrors('note');
            }
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
        $this->post(route('cart.checkout'), $this->address(['customer_name' => 'Khách Soopi', 'note' => 'Gọi trước 15 phút.']))
            ->assertRedirect(route('home'));
        $this->assertSame('Gọi trước 15 phút.', Order::sole()->note);
    }

    public function test_orders_without_a_note_render_without_an_empty_note_section(): void
    {
        $order = $this->order();
        $this->assertNull($order->note);
        $this->get(route('orders.show', $order))->assertOk()->assertDontSee('Ghi chú đơn hàng:');
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->get(route('admin.orders.show', $order))->assertOk()->assertDontSee('Ghi chú đơn hàng:');
    }

    public function test_removed_momo_and_card_choices_are_rejected_at_checkout(): void
    {
        $this->cart();
        foreach (['momo', 'atm_domestic', 'atm_international', 'qr'] as $method) {
            $this->postJson(route('payment.process'), $this->address(['payment_method' => $method]))
                ->assertUnprocessable()->assertJsonValidationErrors('payment_method');
        }
        $this->assertDatabaseCount('orders', 0);
        Http::assertNothingSent();
    }

    public function test_real_checkout_prepares_one_bank_qr_without_a_gateway_network_call(): void
    {
        $this->configureSePay();
        $this->cart();
        $this->post(route('payment.process'), $this->address())->assertSessionHasNoErrors()
            ->assertRedirect(route('user.orders.sepay.pay', Order::sole()));
        $order = Order::sole();
        $payment = $order->paymentTransactions()->sole();
        $this->assertSame('sepay', $payment->gateway);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('reserved', $order->inventory_status);
        $this->assertMatchesRegularExpression('/^DH[0-9]{8}$/', $payment->gateway_order_id);
        $this->get(route('user.orders.sepay.pay', $order))->assertOk()->assertSee('96247TEST')
            ->assertSee('vietqr.app/img')->assertSee($payment->gateway_order_id);
        Http::assertNothingSent();
    }

    public function test_cod_dispatched_expired_and_paid_orders_cannot_start_another_gateway_payment(): void
    {
        $this->configureSePay();
        $orders = collect([
            $this->order(['status' => 'cod_ordered']),
            $this->order(['ghn_order_code' => 'GHN_EXISTING']),
            $this->order(['shipping_status' => 'delivering']),
            $this->order(['payment_expires_at' => now()->subMinute()]),
            $this->order(['status' => 'paid']),
        ]);
        foreach (['cod' => 'pending', 'momo' => 'refund_pending'] as $gateway => $status) {
            $order = $this->order();
            $order->paymentTransactions()->create(['gateway' => $gateway, 'status' => $status, 'amount' => 1000000]);
            $orders->push($order);
        }
        foreach ($orders as $order) {
            $payment = $order->paymentTransactions()->create(['gateway' => 'sepay', 'amount' => 1000000, 'status' => 'pending']);
            app(SePayService::class)->prepare($payment);
            $count = $order->paymentTransactions()->count();
            foreach (['user.orders.sepay.pay', 'orders.sepay.pay'] as $route) {
                $this->get(route($route, $order))->assertStatus(409);
            }
            $this->assertSame($count, $order->paymentTransactions()->count());
        }
        Http::assertNothingSent();
    }

    public function test_production_checkout_never_simulates_and_uses_compact_bank_qr(): void
    {
        $this->configureSePay();
        $this->app->instance('env', 'production');
        config(['demo.enabled' => true]); // Even an accidentally carried-over flag cannot enable demo in production.
        $this->cart();
        $this->withSession(['_token' => 'production-checkout-test'])
            ->post(route('payment.process'), $this->address(['_token' => 'production-checkout-test']))
            ->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::sole();
        $payment = $order->paymentTransactions()->sole();
        $this->assertFalse($order->is_demo);
        $this->assertSame('sepay', $payment->gateway);
        $this->get(route('user.orders.sepay.pay', $order))->assertOk()
            ->assertSee('template=compact')->assertSee('96247TEST')->assertDontSee('Chạy mô phỏng');
        $this->post(route('user.orders.confirm.payment', $order), ['_token' => 'production-checkout-test', 'scenario' => 'success'])
            ->assertNotFound();
        $this->assertSame('pending', $payment->fresh()->status);
        $this->sendSePay($this->sepayPayload($payment))->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
        Http::assertNothingSent();
    }
}
