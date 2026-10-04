<?php

namespace Tests\Feature;

use App\Jobs\SendOrderStatusEmail;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\PaymentTransaction;
use App\Models\Perfume;
use App\Models\User;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use Tests\Concerns\SePayRequests;
use App\Services\OrderEmailService;
use App\Services\ShippingUpdateService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Mail\MailManager;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class OrderEmailTest extends TestCase
{
    use DatabaseMigrations;
    use SePayRequests;

    private QueueManager $realQueue;

    private MailManager $realMail;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        $this->realMail = Mail::getFacadeRoot();
        Mail::fake();
        $this->realQueue = Queue::getFacadeRoot();
        Queue::fake();
        config([
            'demo.enabled' => false,
            'order_emails.enabled' => true,
            'order_emails.mailer' => null,
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'queue.default' => 'sync',
            'queue.connections.database.connection' => 'sqlite',
            'queue.connections.database.table' => 'jobs',
        ]);
    }

    private function order(array $attributes = [], ?User $user = null): Order
    {
        $order = Order::create(array_merge([
            'user_id' => ($user ?? User::factory()->create())->id,
            'customer_name' => 'Order Email Customer',
            'phone' => '0987654321',
            'address' => '123 Order Email Street',
            'total_price' => 310000,
            'ghn_total_fee' => 30000,
            'discount_amount' => 10000,
            'points_used' => 10,
            'status' => 'pending',
            'shipping_status' => 'pending',
            'inventory_status' => 'unreserved',
            'is_demo' => false,
        ], $attributes));
        $order->items()->create([
            'perfume_id' => $this->product()->id, 'quantity' => 2,
            'price' => 150000, 'volume_ml' => 100,
        ]);

        return $order;
    }

    private function product(): Perfume
    {
        return Perfume::firstOrCreate(['slug' => 'order-email-perfume'], [
            'name' => 'Order Email Perfume', 'brand' => 'Email Brand',
            'gender' => 'unisex', 'volume_ml' => 100, 'weight' => 200, 'price' => 150000,
            'stock' => 20, 'is_active' => true,
        ]);
    }

    private function payment(Order $order, array $attributes = []): PaymentTransaction
    {
        return $order->paymentTransactions()->create(array_merge([
            'gateway' => 'cod', 'amount' => $order->total_price, 'status' => 'pending',
        ], $attributes));
    }

    private function email(Order $order, string $type = 'placed'): OrderEmail
    {
        return OrderEmail::where('order_id', $order->id)->where('type', $type)->firstOrFail();
    }

    private function deliver(OrderEmail $email): void
    {
        (new SendOrderStatusEmail($email->id))->handle(app(OrderEmailService::class));
    }

    private function recover(OrderEmail $email): void
    {
        $this->travelTo($email->fresh()->available_at->copy()->addSecond());
        Queue::fake();
        $this->assertSame(1, app(OrderEmailService::class)->enqueuePending());
        Queue::assertPushed(SendOrderStatusEmail::class, fn ($job) => $job->emailId === $email->id);
        $this->deliver($email);
    }

    public function test_legacy_cart_checkout_records_placed_email_with_reserved_items(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $this->actingAs($user)->withSession(['cart' => [$product->id => 2]])
            ->post(route('cart.checkout'), [
                'customer_name' => 'Checkout Customer', 'phone' => '0912345678', 'address' => '123 Checkout Street',
            ])->assertRedirect(route('home'))->assertSessionHasNoErrors();

        $order = Order::sole();
        $email = $this->email($order);
        $this->assertSame($user->email, $email->recipient);
        $this->assertSame('reserved', $order->inventory_status);
        $this->assertSame(18, $product->fresh()->stock);
        $this->assertSame(2, $email->details['items'][0]['quantity']);
        $this->assertEquals(300000, $email->details['total']);
        $this->assertDatabaseCount('order_emails', 1);
        Queue::assertPushed(SendOrderStatusEmail::class, 1);
        Mail::assertNothingSent();
    }

    public function test_main_checkout_records_placed_once_when_checkout_key_is_retried(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product();
        $ghn = Mockery::mock(GHNService::class);
        $ghn->shouldReceive('packageParameters')->once()->with(400)
            ->andReturn(['weight' => 400, 'length' => 15, 'width' => 15, 'height' => 10]);
        $ghn->shouldReceive('calculateFee')->once()->andReturn(['code' => 200, 'data' => ['total' => 30000]]);
        $this->app->instance(GHNService::class, $ghn);
        $shipments = Mockery::mock(GHNOrderService::class);
        $shipments->shouldReceive('create')->once()->with(Mockery::type(Order::class))
            ->andReturn(['code' => 200, 'data' => ['order_code' => 'GHN-CHECKOUT-EMAIL']]);
        $this->app->instance(GHNOrderService::class, $shipments);
        $payload = [
            'name' => 'Checkout Customer', 'phone' => '0912345678', 'address' => '123 Checkout Street',
            'to_district_id' => 1493, 'to_ward_code' => '1A0706', 'payment_method' => 'cod',
            'checkout_key' => '22222222-2222-4222-8222-222222222222',
        ];

        $this->withSession(['cart' => [$product->id => 2]])->post(route('payment.process'), $payload)
            ->assertRedirect()->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->post(route('payment.process'), $payload)->assertRedirect(route('orders.show', $order))
            ->assertSessionHasNoErrors();

        $email = $this->email($order);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_emails', 1);
        $this->assertSame(18, $product->fresh()->stock);
        $this->assertSame('ready_to_pick', $order->fresh()->shipping_status);
        $this->assertSame('COD', $email->details['payment_method']);
        $this->assertSame(2, $email->details['items'][0]['quantity']);
        $this->assertEquals(330000, $email->details['total']);
        Queue::assertPushed(SendOrderStatusEmail::class, 1);
        Mail::assertNothingSent();
    }

    public function test_outbox_is_durable_before_commit_but_job_waits_and_uses_database_queue(): void
    {
        $order = $this->order();
        $service = app(OrderEmailService::class);

        DB::transaction(function () use ($order, $service) {
            $service->placed($order);
            $service->placed($order);
            $this->assertDatabaseCount('order_emails', 1);
            Queue::assertNothingPushed();
            Mail::assertNothingSent();
        });

        $service->placed($order);
        $email = $this->email($order);
        $this->assertDatabaseCount('order_emails', 1);
        Queue::assertPushed(SendOrderStatusEmail::class, 1);
        Queue::assertPushed(SendOrderStatusEmail::class, fn ($job) =>
            $job->emailId === $email->id && $job->connection === 'database');
        $this->assertSame($order->user->email, $email->recipient);
        Mail::assertNothingSent();
    }

    public function test_sync_default_still_persists_job_without_running_mail_in_checkout(): void
    {
        Queue::swap($this->realQueue);
        $order = $this->order();
        app(OrderEmailService::class)->placed($order);

        $this->assertDatabaseCount('jobs', 1);
        $this->assertDatabaseHas('jobs', ['queue' => 'default']);
        $this->assertNull($this->email($order)->sent_at);
        Mail::assertNothingSent();
    }

    public function test_rollback_discards_order_outbox_and_after_commit_job(): void
    {
        try {
            DB::transaction(function () {
                $order = $this->order();
                app(OrderEmailService::class)->placed($order);
                $this->assertDatabaseCount('order_emails', 1);
                throw new RuntimeException('Checkout rollback');
            });
            $this->fail('The checkout transaction should roll back.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Checkout rollback', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_emails', 0);
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
    }

    public function test_paid_requires_real_payment_and_deduplicates_repeated_confirmation(): void
    {
        $service = app(OrderEmailService::class);
        $order = $this->order(['status' => 'paid']);
        $service->paid($order);
        $payment = $this->payment($order, ['gateway' => 'momo', 'status' => 'failed']);
        $service->paid($order);
        $this->assertDatabaseCount('order_emails', 0);

        $payment->update(['status' => 'paid', 'paid_at' => now()]);
        $service->paid($order);
        $service->paid($order->fresh());
        $this->assertSame('paid', $this->email($order, 'paid')->type);
        $this->assertDatabaseCount('order_emails', 1);

        foreach (['refund_pending', 'refunded'] as $status) {
            $refunded = $this->order(['status' => 'paid']);
            $this->payment($refunded, ['status' => 'paid']);
            $this->payment($refunded, ['status' => $status]);
            $service->paid($refunded);
            $this->assertDatabaseMissing('order_emails', ['order_id' => $refunded->id, 'type' => 'paid']);
        }
    }

    public function test_queue_failure_after_commit_keeps_order_and_outbox_recoverable(): void
    {
        $connection = Mockery::mock();
        $connection->shouldReceive('pushOn')->once()->with('default', Mockery::type(SendOrderStatusEmail::class))
            ->andThrow(new RuntimeException('Queue temporarily unavailable'));
        Queue::swap($this->realQueue);
        Queue::shouldReceive('connection')->once()->with('database')->andReturn($connection);

        $order = DB::transaction(function () {
            $order = $this->order();
            app(OrderEmailService::class)->placed($order);

            return $order;
        });

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $email = $this->email($order);
        $this->assertNull($email->queued_at);
        $this->assertNull($email->sent_at);
        $this->assertNotEmpty($email->last_error);
        Mail::assertNothingSent();
        Queue::swap($this->realQueue);
        $this->recover($email);
        Mail::assertSent(OrderStatusMail::class, 1);
    }

    public function test_scanner_recovers_stale_claim_but_does_not_duplicate_live_or_sent_jobs(): void
    {
        $order = $this->order();
        $service = app(OrderEmailService::class);
        $service->placed($order);
        $email = $this->email($order);
        Queue::fake();
        $this->assertSame(0, $service->enqueuePending());

        $this->travel(11)->minutes();
        $this->assertSame(1, $service->enqueuePending());
        $this->assertSame(0, $service->enqueuePending());
        Queue::assertPushed(SendOrderStatusEmail::class, 1);
        $this->deliver($email);
        $this->travel(11)->minutes();
        $this->assertSame(0, $service->enqueuePending());
        Mail::assertSent(OrderStatusMail::class, 1);
    }

    public function test_active_delivery_lease_blocks_duplicates_and_stale_lease_can_recover(): void
    {
        $order = $this->order();
        $service = app(OrderEmailService::class);
        $service->placed($order);
        $email = $this->email($order);
        $token = '11111111-1111-4111-8111-111111111111';
        $email->update([
            'queued_at' => null, 'processing_at' => now(), 'processing_token' => $token, 'attempts' => 1,
        ]);
        Queue::fake();

        $this->assertSame(0, $service->enqueuePending());
        $this->deliver($email);
        $this->assertSame($token, $email->fresh()->processing_token);
        $this->assertSame(1, $email->fresh()->attempts);
        Queue::assertNothingPushed();
        Mail::assertNothingSent();

        $this->travel(11)->minutes();
        $this->assertSame(1, $service->enqueuePending());
        $this->deliver($email);
        Mail::assertSent(OrderStatusMail::class, 1);
        $this->assertNotNull($email->fresh()->sent_at);
        $this->assertNull($email->fresh()->processing_at);
        $this->assertNull($email->fresh()->processing_token);
        $this->assertSame(2, $email->fresh()->attempts);
    }

    public function test_demo_orders_and_demo_gateway_never_create_customer_emails(): void
    {
        $service = app(OrderEmailService::class);
        foreach ([true, false] as $markedDemo) {
            $order = $this->order(['is_demo' => $markedDemo, 'status' => 'paid']);
            $this->payment($order, ['gateway' => $markedDemo ? 'momo' : 'demo', 'status' => 'paid']);
            $service->placed($order);
            $service->paid($order);
            foreach (['picked', 'delivered'] as $status) {
                $order->update(['shipping_status' => $status]);
                $service->shipping($order);
            }
        }

        $this->assertDatabaseCount('order_emails', 0);
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
    }

    public function test_shipping_waits_for_carrier_handover_and_emits_each_milestone_once(): void
    {
        $order = $this->order();
        $service = app(OrderEmailService::class);
        foreach (['pending', 'not_shipped', 'processing', 'ready_to_pick', 'picking'] as $status) {
            $order->update(['shipping_status' => $status]);
            $service->shipping($order);
        }
        $this->assertDatabaseCount('order_emails', 0);

        foreach (['picked', 'storing', 'transporting', 'sorting', 'delivering', 'delivery_fail'] as $status) {
            $order->update(['shipping_status' => $status]);
            $service->shipping($order);
        }
        $this->assertSame('dispatched', $this->email($order, 'dispatched')->type);
        $this->assertDatabaseCount('order_emails', 1);

        $order->update(['shipping_status' => 'delivered', 'status' => 'completed']);
        $service->shipping($order);
        $service->shipping($order->fresh());
        $this->assertSame('delivered', $this->email($order, 'delivered')->type);
        $this->assertDatabaseCount('order_emails', 2);
    }

    public function test_carrier_updates_include_cod_payment_and_ignore_duplicate_notifications(): void
    {
        $order = $this->order(['status' => 'cod_ordered', 'shipping_status' => 'ready_to_pick']);
        $payment = $this->payment($order);
        $shipping = app(ShippingUpdateService::class);
        foreach (['picked', 'picked', 'delivered', 'delivered'] as $status) {
            $shipping->apply($order, $status);
        }

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(['delivered', 'dispatched', 'paid'],
            OrderEmail::where('order_id', $order->id)->orderBy('type')->pluck('type')->all());
    }

    public function test_repeated_sepay_webhook_records_one_payment_email_without_dispatch_email(): void
    {
        $this->configureSePay();
        $order = $this->order(['inventory_status' => 'reserved', 'payment_expires_at' => now()->addMinutes(30)]);
        $payment = $this->payment($order, ['gateway' => 'sepay']);
        $payload = $this->sepayPayload($payment);
        $this->sendSePay($payload)->assertOk();
        $this->sendSePay($payload)->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->shipping_status);
        $this->assertSame(['paid'], OrderEmail::where('order_id', $order->id)->pluck('type')->all());
        Queue::assertPushed(\App\Jobs\CreateSePayShipment::class, 1);
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_late_sepay_success_on_cancelled_order_does_not_email_transient_paid_state(): void
    {
        $this->configureSePay();
        $order = $this->order(['status' => 'cancelled', 'shipping_status' => 'cancelled']);
        $payment = $this->payment($order, ['gateway' => 'sepay']);
        $payload = $this->sepayPayload($payment);
        $this->sendSePay($payload)->assertOk();
        $this->sendSePay($payload)->assertOk();

        $this->assertSame('refund_pending', $payment->fresh()->status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertDatabaseCount('order_emails', 0);
        Queue::assertNothingPushed();
        Mail::assertNothingSent();
        Http::assertNothingSent();
    }

    public function test_admin_single_and_bulk_completion_capture_query_builder_cod_payments(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ([false, true] as $bulk) {
            $order = $this->order(['status' => 'cod_ordered']);
            $payment = $this->payment($order);
            $response = $bulk
                ? $this->post(route('admin.orders.bulk_update'), ['order_ids' => [$order->id], 'bulk_shipping_status' => 'delivered'])
                : $this->patch(route('admin.orders.update', $order), ['shipping_status' => 'delivered']);
            $response->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame('paid', $payment->fresh()->status);
            $this->assertDatabaseHas('order_emails', ['order_id' => $order->id, 'type' => 'paid']);
            $this->assertDatabaseHas('order_emails', ['order_id' => $order->id, 'type' => 'delivered']);
        }
    }

    public function test_finance_collection_on_completed_order_still_records_paid_email(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $order = $this->order(['status' => 'completed', 'shipping_status' => 'delivered']);
        $payment = $this->payment($order);
        $input = [
            'payment_status' => 'paid', 'current_payment_status' => 'pending',
            'current_payment_id' => $payment->id, 'current_order_status' => 'completed', 'mode' => 'real',
        ];
        $this->patch(route('admin.finance.update-status', $order), $input)->assertRedirect()->assertSessionHasNoErrors();
        $this->patch(route('admin.finance.update-status', $order), $input)->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(1, OrderEmail::where('order_id', $order->id)->where('type', 'paid')->count());
    }

    public function test_worker_sends_once_even_when_the_same_job_is_retried(): void
    {
        $order = $this->order();
        app(OrderEmailService::class)->placed($order);
        $email = $this->email($order);
        $this->deliver($email);
        $this->deliver($email);

        Mail::assertSent(OrderStatusMail::class, 1);
        Mail::assertSent(OrderStatusMail::class, fn ($mail) => $mail->hasTo($order->user->email));
        $this->assertNotNull($email->fresh()->sent_at);
        $this->assertSame(1, $email->fresh()->attempts);
    }

    public function test_smtp_failure_preserves_order_and_outbox_for_recovery(): void
    {
        $order = $this->order();
        app(OrderEmailService::class)->placed($order);
        $email = $this->email($order);
        $transactionLevelDuringSend = null;
        $pending = Mockery::mock();
        $pending->shouldReceive('send')->once()->with(Mockery::type(OrderStatusMail::class))
            ->andReturnUsing(function () use (&$transactionLevelDuringSend) {
                $transactionLevelDuringSend = DB::transactionLevel();
                throw new RuntimeException('SMTP temporarily unavailable');
            });
        $mailer = Mockery::mock();
        $mailer->shouldReceive('to')->once()->with($email->recipient)->andReturn($pending);
        Mail::swap($this->realMail);
        Mail::shouldReceive('mailer')->once()->with('smtp')->andReturn($mailer);

        $this->deliver($email);

        $this->assertSame(0, $transactionLevelDuringSend, 'SMTP must not hold a database transaction open.');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertSame(1, $order->items()->count());
        $email->refresh();
        $this->assertNull($email->sent_at);
        $this->assertNull($email->queued_at);
        $this->assertSame(1, $email->attempts);
        $this->assertNotEmpty($email->last_error);
        $this->assertTrue($email->available_at->isFuture());

        Mail::swap($this->realMail);
        Mail::fake();
        $this->recover($email);
        Mail::assertSent(OrderStatusMail::class, 1);
        $this->assertNotNull($email->fresh()->sent_at);
        $this->assertSame(2, $email->fresh()->attempts);
    }

    public function test_log_transport_holds_mail_until_a_real_transport_is_configured(): void
    {
        config(['mail.default' => 'log']);
        $order = $this->order();
        app(OrderEmailService::class)->placed($order);
        $email = $this->email($order);
        $this->deliver($email);

        Mail::assertNothingSent();
        $this->assertNull($email->fresh()->sent_at);
        $this->assertNull($email->fresh()->skipped_at);
        $this->assertSame(0, $email->fresh()->attempts);
        config(['mail.default' => 'smtp']);
        $this->recover($email);
        Mail::assertSent(OrderStatusMail::class, 1);
    }

    public function test_unverified_recipient_is_held_and_recovered_after_verification(): void
    {
        $user = User::factory()->unverified()->create();
        $order = $this->order([], $user);
        app(OrderEmailService::class)->placed($order);
        $email = $this->email($order);
        $this->deliver($email);

        Mail::assertNothingSent();
        $this->assertNull($email->fresh()->sent_at);
        $this->assertNull($email->fresh()->skipped_at);
        $this->assertSame(0, $email->fresh()->attempts);
        $user->forceFill(['email_verified_at' => now()])->save();
        $this->recover($email);
        Mail::assertSent(OrderStatusMail::class, 1);
    }

    public function test_worker_rechecks_demo_status_and_current_verified_recipient(): void
    {
        foreach (['demo', 'changed_email'] as $change) {
            $order = $this->order();
            app(OrderEmailService::class)->placed($order);
            $email = $this->email($order);
            if ($change === 'demo') {
                $order->update(['is_demo' => true]);
            } else {
                $order->user->update(['email' => 'changed-'.$order->id.'@example.com']);
            }
            $this->deliver($email);
            $this->assertNull($email->fresh()->sent_at);
        }
        Mail::assertNothingSent();
    }

    public function test_cancellation_or_refund_before_worker_suppresses_obsolete_notifications(): void
    {
        $service = app(OrderEmailService::class);
        foreach (['cancelled', 'refund_pending', 'refunded'] as $status) {
            $order = $this->order(['status' => 'paid']);
            $payment = $this->payment($order, ['status' => 'paid']);
            $service->paid($order);
            $emails = [$this->email($order, 'paid')];
            if ($status === 'cancelled') {
                $service->placed($order);
                $emails[] = $this->email($order);
                $order->update(['status' => 'cancelled', 'shipping_status' => 'cancelled']);
            } else {
                $payment->update(['status' => $status]);
            }
            foreach ($emails as $email) {
                $this->deliver($email);
                $this->assertNull($email->fresh()->sent_at);
                $this->assertNotNull($email->fresh()->skipped_at);
            }
        }
        Mail::assertNothingSent();
    }

    public function test_mailable_preserves_item_snapshot_totals_and_authenticated_tracking_link(): void
    {
        config(['app.url' => 'https://shop.example.test']);
        $order = $this->order(['ghn_order_code' => 'GHN-EMAIL-001']);
        $this->payment($order);
        $order->items()->first()->perfume->update(['name' => 'Renamed catalog product']);
        app(OrderEmailService::class)->placed($order);
        $details = $this->email($order)->details;

        $this->assertSame('Order Email Perfume', $details['items'][0]['name']);
        $this->assertEquals(2, $details['items'][0]['quantity']);
        $this->assertEquals(300000, $details['subtotal']);
        $this->assertEquals(30000, $details['shipping_fee']);
        $this->assertEquals(10000, $details['discount_amount']);
        $this->assertEquals(10000, $details['points_discount']);
        $this->assertEquals(310000, $details['total']);
        $this->assertSame('VND', $details['currency']);
        $this->assertSame('COD', $details['payment_method']);
        $this->assertSame('GHN-EMAIL-001', $details['tracking_code']);
        $this->assertSame('https://shop.example.test/orders/'.$order->id, $details['tracking_url']);

        $html = (new OrderStatusMail($details))->render();
        $this->assertStringContainsString('https://shop.example.test/images/brand/soopi-petal-logo.png', $html);
        $this->assertStringContainsString('Order Email Perfume', $html);
        $this->assertStringContainsString('GHN-EMAIL-001', $html);
        $this->assertStringContainsString(e($details['tracking_url']), $html);
        $this->assertStringNotContainsString('Renamed catalog product', $html);
    }

    public function test_delayed_placed_email_uses_current_payment_and_tracking_without_changing_snapshot(): void
    {
        $order = $this->order();
        $payment = $this->payment($order, ['gateway' => 'momo']);
        app(OrderEmailService::class)->placed($order);
        $email = $this->email($order);
        $snapshot = $email->details;
        $this->assertSame('Chưa thanh toán', $snapshot['payment_status']);
        $this->assertNull($snapshot['tracking_code']);

        $payment->update(['status' => 'paid', 'paid_at' => now()]);
        $order->update(['status' => 'paid', 'shipping_status' => 'ready_to_pick', 'ghn_order_code' => 'GHN-AFTER-CHECKOUT']);
        $this->deliver($email);

        Mail::assertSent(OrderStatusMail::class, fn ($mail) =>
            $mail->details['order_id'] === $order->id
            && $mail->details['payment_status'] === 'Đã thanh toán'
            && $mail->details['tracking_code'] === 'GHN-AFTER-CHECKOUT');
        $this->assertNotNull($email->fresh()->sent_at);
        $this->assertSame($snapshot, $email->fresh()->details);
    }

    public function test_fulfilment_email_uses_current_finance_refund_label_without_changing_snapshot(): void
    {
        foreach (['dispatched' => 'delivering', 'delivered' => 'delivered'] as $type => $shipping) {
            foreach (['refund_pending' => 'Chờ hoàn tiền', 'refunded' => 'Đã hoàn tiền'] as $status => $label) {
                $order = $this->order([
                    'status' => $shipping === 'delivered' ? 'completed' : 'paid', 'shipping_status' => $shipping,
                ]);
                $payment = $this->payment($order, ['status' => 'paid', 'paid_at' => now()]);
                app(OrderEmailService::class)->shipping($order);
                $email = $this->email($order, $type);
                $snapshot = $email->details;
                $this->assertSame('Đã thanh toán', $snapshot['payment_status']);

                $payment->update(['status' => $status]);
                $this->deliver($email);

                Mail::assertSent(OrderStatusMail::class, fn ($mail) =>
                    $mail->details['order_id'] === $order->id && $mail->details['payment_status'] === $label);
                $this->assertNotNull($email->fresh()->sent_at);
                $this->assertSame($snapshot, $email->fresh()->details);
                $this->assertSame($shipping, $order->fresh()->shipping_status);
            }
        }
        Mail::assertSent(OrderStatusMail::class, 4);
    }
}
