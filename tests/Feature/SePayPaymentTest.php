<?php

namespace Tests\Feature;

use App\Jobs\CreateSePayShipment;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use App\Services\OrderInventoryService;
use App\Services\SePayService;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\Concerns\SePayRequests;
use Tests\TestCase;

class SePayPaymentTest extends TestCase
{
    use DatabaseMigrations;
    use SePayRequests;

    private QueueManager $realQueue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Mail::fake();
        $this->realQueue = Queue::getFacadeRoot();
        Queue::fake();
        $this->configureSePay();
    }

    private function pending(array $attributes = []): array
    {
        $user = User::factory()->create();
        $order = Order::create(array_replace([
            'user_id' => $user->id, 'customer_name' => 'SePay Customer', 'name' => 'SePay Customer',
            'phone' => '0912345678', 'address' => 'Test address', 'total_price' => 120000,
            'status' => 'pending', 'shipping_status' => 'pending', 'is_demo' => false,
            'inventory_status' => 'unreserved', 'payment_expires_at' => now()->addMinutes(30),
        ], $attributes));
        $product = Perfume::create([
            'name' => 'SePay Rose', 'slug' => 'sepay-rose-'.$order->id, 'brand' => 'Test',
            'gender' => 'unisex', 'price' => 120000, 'volume_ml' => 100, 'weight' => 200,
            'stock' => 10, 'is_active' => true,
        ]);
        $order->items()->create(['perfume_id' => $product->id, 'quantity' => 1, 'price' => 120000, 'volume_ml' => 100]);
        app(OrderInventoryService::class)->reserve($order);
        $payment = $order->paymentTransactions()->create(['gateway' => 'sepay', 'amount' => 120000, 'status' => 'pending']);
        $payload = $this->sepayPayload($payment);

        return [$order, $payment, $payload, $product, $user];
    }

    public function test_exact_signed_transfer_pays_once_and_enqueues_shipping_without_network_io(): void
    {
        [$order, $payment, $payload, $product, $user] = $this->pending();
        $this->sendSePay($payload)->assertOk()->assertExactJson(['success' => true]);
        $this->sendSePay($payload)->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('pending', $order->fresh()->shipping_status);
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertDatabaseCount('sepay_webhook_receipts', 1);
        Queue::assertPushed(CreateSePayShipment::class, 1);
        Http::assertNothingSent();
        $this->actingAs($user)->getJson(route('user.orders.sepay.status', $order))->assertOk()
            ->assertJson(['status' => 'paid', 'can_pay' => false]);
        $this->get(route('user.orders.sepay.pay', $order))->assertRedirect(route('orders.show', $order));
    }

    public function test_missing_wrong_tampered_stale_and_future_signatures_cannot_change_payment(): void
    {
        [$order, $payment, $payload] = $this->pending();
        $this->postJson(route('payment.sepay.webhook'), $payload)->assertUnauthorized();
        $this->sendSePay($payload, secret: 'wrong-secret')->assertUnauthorized();
        $this->sendSePay($payload, time() - 301)->assertUnauthorized();
        $this->sendSePay($payload, time() + 301)->assertUnauthorized();
        $time = (string) time();
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $time.'.'.$body, config('sepay.webhook_secret'));
        $this->call('POST', route('payment.sepay.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_X_SEPAY_TIMESTAMP' => $time,
            'HTTP_X_SEPAY_SIGNATURE' => $signature,
        ], str_replace('120000', '120001', $body))->assertUnauthorized();
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('sepay_webhook_receipts', 0);
        Queue::assertNothingPushed();
    }

    public function test_disabled_or_demo_service_never_accepts_real_money_notifications(): void
    {
        [$order, $payment, $payload, $product, $user] = $this->pending();
        config(['sepay.enabled' => false]);
        $this->sendSePay($payload)->assertStatus(503);
        $this->actingAs($user)->get(route('user.orders.sepay.pay', $order))->assertStatus(503);
        config(['sepay.enabled' => true, 'demo.enabled' => true]);
        $this->sendSePay($payload)->assertStatus(503);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('sepay_webhook_receipts', 0);
    }

    public function test_wrong_bank_account_va_or_outgoing_transfer_is_ignored(): void
    {
        [$order, $payment, $payload] = $this->pending();
        foreach ([['gateway' => 'VCB'], ['accountNumber' => '999'], ['subAccount' => 'OTHER'], ['subAccount' => null], ['transferType' => 'out']] as $override) {
            $this->sendSePay(array_replace($payload, $override))->assertOk();
        }
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('sepay_webhook_receipts', 0);
    }

    public function test_invalid_payload_and_oversized_json_are_rejected(): void
    {
        [$order, $payment, $payload] = $this->pending();
        foreach ([['id' => -1], ['transferAmount' => 0], ['transferAmount' => 120000.5], ['code' => ['DH00000001']], ['transferType' => 'other']] as $override) {
            $this->sendSePay(array_replace($payload, $override))->assertUnprocessable();
        }
        $this->sendSePay($payload + ['content' => str_repeat('x', 17000)])->assertStatus(413);
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('sepay_webhook_receipts', 0);
    }

    public function test_wrong_amount_and_unmatched_code_are_saved_for_review_without_marking_paid(): void
    {
        [$order, $payment, $payload] = $this->pending();
        foreach ([119999, 120001] as $index => $amount) {
            $this->sendSePay(array_replace($payload, ['id' => 2000 + $index, 'transferAmount' => $amount]))->assertOk();
        }
        $this->sendSePay(array_replace($payload, ['id' => 2002, 'code' => 'DH99999999']))->assertOk();
        $this->sendSePay(array_replace($payload, ['id' => 2003, 'code' => null]))->assertOk();
        $this->assertDatabaseCount('sepay_webhook_receipts', 4);
        $this->assertSame(2, DB::table('sepay_webhook_receipts')->where('result', 'amount_mismatch')->count());
        $this->assertSame(2, DB::table('sepay_webhook_receipts')->where('result', 'unmatched')->count());
        $this->assertSame('pending', $payment->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_distinct_second_transfer_is_reviewed_without_overwriting_the_first(): void
    {
        [$order, $payment, $payload] = $this->pending();
        $this->sendSePay($payload)->assertOk();
        $this->sendSePay(array_replace($payload, ['id' => 1002]))->assertOk();
        $this->assertSame('1001', $payment->fresh()->transaction_id);
        $this->assertDatabaseHas('sepay_webhook_receipts', ['provider_id' => '1002', 'result' => 'duplicate_payment']);
        Queue::assertPushed(CreateSePayShipment::class, 1);
    }

    public function test_same_provider_id_cannot_pay_a_different_order(): void
    {
        [, , $first] = $this->pending();
        [, $secondPayment, $second] = $this->pending();
        $this->sendSePay($first)->assertOk();
        $this->sendSePay($second)->assertOk();
        $this->assertSame('pending', $secondPayment->fresh()->status);
        $this->assertDatabaseCount('sepay_webhook_receipts', 1);
        Queue::assertPushed(CreateSePayShipment::class, 1);
    }

    public function test_expired_transfer_requires_refund_and_releases_inventory_exactly_once(): void
    {
        [$order, $payment, $payload, $product] = $this->pending(['payment_expires_at' => now()->subMinute()]);
        $this->sendSePay($payload)->assertOk();
        $this->sendSePay($payload)->assertOk();
        $this->assertSame('refund_pending', $payment->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('released', $order->fresh()->inventory_status);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseHas('sepay_webhook_receipts', ['result' => 'late_payment']);
        $this->assertDatabaseCount('order_emails', 0);
        Queue::assertNothingPushed();
    }

    public function test_unpayable_orders_are_never_resurrected_by_a_valid_transfer(): void
    {
        foreach ([['shipping_status' => 'delivering'], ['ghn_order_code' => 'GHN-EXISTING'], ['is_demo' => true], ['status' => 'completed']] as $index => $attributes) {
            [$order, $payment, $payload] = $this->pending($attributes);
            $this->sendSePay(array_replace($payload, ['id' => 3000 + $index]))->assertOk();
            $this->assertSame('pending', $payment->fresh()->status);
            $this->assertSame($attributes['status'] ?? 'pending', $order->fresh()->status);
        }
        Queue::assertNothingPushed();
    }

    public function test_qr_and_status_are_private_and_account_snapshot_survives_config_changes(): void
    {
        [$order, $payment, $payload, $product, $user] = $this->pending();
        $this->getJson(route('user.orders.sepay.status', $order))->assertUnauthorized();
        $this->actingAs(User::factory()->create())->get(route('user.orders.sepay.pay', $order))->assertForbidden();
        $this->getJson(route('user.orders.sepay.status', $order))->assertForbidden();
        config(['sepay.account_number' => '555555', 'sepay.sub_account' => 'NEWVA']);
        $this->actingAs($user)->get(route('user.orders.sepay.pay', $order))->assertOk()->assertSee('96247TEST')->assertDontSee('NEWVA')
            ->assertDontSee(config('sepay.webhook_secret'))->assertHeader('Cache-Control', 'no-store, private');
        parse_str(parse_url(app(SePayService::class)->qrUrl($payment), PHP_URL_QUERY), $query);
        $this->assertSame(['acc' => '96247TEST', 'bank' => 'BIDV', 'amount' => '120000', 'des' => $payment->gateway_order_id, 'template' => 'compact'], $query);
        $this->sendSePay($payload)->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_database_queue_is_durable_and_commits_with_payment_even_when_default_is_sync(): void
    {
        Queue::swap($this->realQueue);
        config(['queue.default' => 'sync']);
        [$order, $payment, $payload] = $this->pending();
        $this->sendSePay($payload)->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertDatabaseHas('jobs', ['queue' => 'default']);
        $this->assertSame(1, DB::table('jobs')->where('payload', 'like', '%CreateSePayShipment%')->count());
        Http::assertNothingSent();
    }

    public function test_queue_failure_rolls_back_receipt_payment_and_email_for_provider_retry(): void
    {
        [$order, $payment, $payload] = $this->pending();
        $this->mock(Dispatcher::class, function ($mock) {
            $mock->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('queue unavailable'));
        });
        $this->sendSePay($payload)->assertServerError();
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertDatabaseCount('sepay_webhook_receipts', 0);
        $this->assertDatabaseCount('order_emails', 0);
    }

    public function test_shipping_job_creates_one_prepaid_shipment_and_duplicate_job_is_harmless(): void
    {
        [$order, , $payload] = $this->pending();
        $this->sendSePay($payload)->assertOk();
        $shipping = $this->createMock(GHNOrderService::class);
        $shipping->expects($this->once())->method('create')->with($this->callback(fn ($value) => $value->id === $order->id), true)
            ->willReturn(['code' => 200, 'data' => ['order_code' => 'GHN-SEPAY']]);
        $job = new CreateSePayShipment($order->id);
        $job->handle($shipping);
        $job->handle($shipping);
        $this->assertSame('GHN-SEPAY', $order->fresh()->ghn_order_code);
        $this->assertSame('ready_to_pick', $order->fresh()->shipping_status);
    }

    public function test_uncertain_shipping_failure_is_not_retried_as_a_second_shipment(): void
    {
        [$order, , $payload, $product, $user] = $this->pending();
        $this->sendSePay($payload)->assertOk();
        $shipping = $this->createMock(GHNOrderService::class);
        $shipping->expects($this->once())->method('create')->willThrowException(new RuntimeException('timeout'));
        $job = new CreateSePayShipment($order->id);
        $job->handle($shipping);
        $job->handle($shipping);
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertNull($order->fresh()->ghn_order_code);
        $this->assertNotNull(DB::table('sepay_webhook_receipts')->value('shipment_attempted_at'));
        $this->actingAs($user)->post(route('orders.cancel', $order))->assertSessionHasErrors('order');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(9, $product->fresh()->stock);
    }

    public function test_shipping_job_skips_an_order_cancelled_after_webhook(): void
    {
        [$order, $payment, $payload] = $this->pending();
        $this->sendSePay($payload)->assertOk();
        $order->update(['status' => 'cancelled', 'shipping_status' => 'cancelled']);
        $payment->update(['status' => 'refund_pending']);
        $shipping = $this->createMock(GHNOrderService::class);
        $shipping->expects($this->never())->method('create');
        (new CreateSePayShipment($order->id))->handle($shipping);
    }

    public function test_receipts_and_sepay_finance_filter_are_admin_only_and_render_review_reasons(): void
    {
        [$order, , $payload, , $user] = $this->pending();
        $this->sendSePay(array_replace($payload, ['transferAmount' => 100000]))->assertOk();
        $this->actingAs($user)->get(route('admin.finance.sepay'))->assertRedirect(route('home'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.finance.sepay', ['result' => 'amount_mismatch']))->assertOk()->assertSee('Sai số tiền')->assertSee($payload['code']);
        $this->get(route('admin.finance.transactions', ['gateway' => 'sepay']))->assertOk()->assertSee('SePay Customer')->assertSee('SePay');
    }

    public function test_admin_cannot_fake_sepay_payment_but_can_record_external_refund_with_proof(): void
    {
        [$order, $payment, $payload] = $this->pending(['payment_expires_at' => now()->subMinute()]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $input = ['current_payment_status' => 'pending', 'current_payment_id' => $payment->id, 'current_order_status' => 'pending', 'payment_status' => 'paid', 'mode' => 'real'];
        $this->patch(route('admin.finance.update-status', $order), $input)->assertSessionHasErrors('payment_status');
        $this->sendSePay($payload)->assertOk();
        $input = array_replace($input, ['current_payment_status' => 'refund_pending', 'current_order_status' => 'cancelled', 'payment_status' => 'refunded']);
        $this->patch(route('admin.finance.update-status', $order), $input)->assertSessionHasErrors('manual_refund_reference');
        $this->patch(route('admin.finance.update-status', $order), $input + ['manual_refund_reference' => 'BANK-REFUND-123'])->assertSessionHasNoErrors();
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertDatabaseHas('payment_status_events', ['payment_id' => $payment->id, 'manual_refund_reference' => 'BANK-REFUND-123']);
    }

    public function test_admin_order_editor_cannot_mark_an_unpaid_sepay_order_as_paid_or_delivered(): void
    {
        [$order, $payment] = $this->pending();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([['status' => 'paid'], ['shipping_status' => 'delivered'], ['status' => 'cod_ordered']] as $input) {
            $this->patch(route('admin.orders.update', $order), $input)->assertSessionHas('error');
            $this->assertSame('pending', $order->fresh()->status);
            $this->assertSame('pending', $payment->fresh()->status);
        }
    }

    public function test_customer_cancellation_cannot_release_stock_if_shipment_changed_during_cancellation(): void
    {
        [$order, $payment, $payload, $product, $user] = $this->pending();
        $this->sendSePay($payload)->assertOk();
        $order->update(['ghn_order_code' => 'OLD-SHIPMENT', 'shipping_status' => 'ready_to_pick']);
        $shipping = $this->createMock(GHNService::class);
        $shipping->method('cancelOrder')->willReturnCallback(function () use ($order) {
            $order->update(['ghn_order_code' => 'NEW-SHIPMENT']);

            return ['code' => 200];
        });
        $this->app->instance(GHNService::class, $shipping);
        $this->actingAs($user)->post(route('orders.cancel', $order))->assertSessionHasErrors('order');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(9, $product->fresh()->stock);
    }
}
