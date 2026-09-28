<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Services\OrderInventoryService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommerceUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
    }

    private function product(array $extra = []): Perfume
    {
        return Perfume::create(array_merge([
            'name' => 'Hạ Thu Rose', 'slug' => 'rose-'.uniqid(), 'brand' => 'Hạ Thu',
            'gender' => 'nu', 'volume_ml' => 100, 'price' => 1000000,
            'stock' => 10, 'stock_10ml' => 4, 'stock_50ml' => 3, 'stock_5ml' => 5, 'is_active' => true,
        ], $extra));
    }

    private function customer(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function address(): array
    {
        return ['customer_name' => 'Khách demo', 'name' => 'Khách demo', 'phone' => '0912345678',
            'address' => 'Địa chỉ demo', 'to_district_id' => 1493, 'to_ward_code' => 'DEMO'];
    }

    private function order(User $user, array $extra = []): Order
    {
        return Order::create(array_merge(['user_id' => $user->id, 'customer_name' => 'Khách',
            'phone' => '0912345678', 'address' => 'Hà Nội', 'total_price' => 1000000,
            'status' => 'pending', 'shipping_status' => 'pending'], $extra));
    }

    public function test_guest_and_customer_cannot_write_catalog(): void
    {
        $product = $this->product();
        $this->get(route('perfumes.index'))->assertOk();
        $this->delete(route('perfumes.destroy', $product))->assertRedirect(route('login'));
        $this->customer();
        $this->delete(route('perfumes.destroy', $product))->assertRedirect(route('home'));
        $this->post(route('categories.store'), ['name' => 'Forbidden'])->assertRedirect(route('home'));
        $this->assertModelExists($product);
    }

    public function test_buy_now_cannot_exceed_stock_or_invent_volume(): void
    {
        $this->customer();
        $product = $this->product();
        $this->post(route('cart.add', $product), ['quantity' => 5, 'volume_ml' => 10, 'buy_now' => 1])
            ->assertSessionHasErrors('quantity');
        $this->post(route('cart.add', $product), ['quantity' => 1, 'volume_ml' => 999, 'buy_now' => 1])
            ->assertSessionHasErrors('cart');
    }

    public function test_variant_checkout_reprices_and_cancel_restores_exactly_once(): void
    {
        $this->customer();
        $product = $this->product();
        $this->withSession(['cart' => ['variant' => ['perfume_id' => $product->id,
            'quantity' => 2, 'volume_ml' => 10, 'unit_price' => 1]]])
            ->post(route('cart.checkout'), $this->address())->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertEquals(440000, $order->total_price);
        $this->assertSame('reserved', $order->inventory_status);
        $this->assertSame(2, $product->fresh()->stock_10ml);
        $this->assertSame(10, $product->fresh()->stock);
        $this->post(route('orders.cancel', $order))->assertSessionHas('success');
        $this->post(route('orders.cancel', $order));
        $this->assertSame(4, $product->fresh()->stock_10ml);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame('released', $order->fresh()->inventory_status);
        $this->assertSame(2, $order->events()->count());
    }

    public function test_aggregate_stock_failure_rolls_back_all_items(): void
    {
        $this->customer();
        $product = $this->product();
        $cart = [
            'plain' => ['perfume_id' => $product->id, 'quantity' => 3, 'volume_ml' => 10],
            'gift' => ['perfume_id' => $product->id, 'quantity' => 2, 'volume_ml' => 10, 'has_gift' => true],
        ];
        $this->withSession(['cart' => $cart])->post(route('cart.checkout'), $this->address())
            ->assertSessionHasErrors('cart')->assertSessionHas('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(4, $product->fresh()->stock_10ml);
        $this->assertDatabaseCount('order_events', 0);
    }

    public function test_discovery_box_reserves_every_sample_and_releases_every_sample(): void
    {
        $this->customer();
        $products = collect([$this->product(), $this->product(), $this->product()]);
        $this->withSession(['cart' => ['box' => ['perfume_id' => $products[0]->id, 'quantity' => 2,
            'is_discovery_box' => true, 'sample_ids' => $products->pluck('id')->all(), 'unit_price' => 1]]])
            ->post(route('cart.checkout'), $this->address())->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertEquals(398000, $order->total_price);
        foreach ($products as $product) {
            $this->assertSame(3, $product->fresh()->stock_5ml);
            $this->assertSame(10, $product->fresh()->stock);
        }
        $this->post(route('orders.cancel', $order))->assertSessionHas('success');
        foreach ($products as $product) {
            $this->assertSame(5, $product->fresh()->stock_5ml);
        }
    }

    public function test_legacy_order_cancellation_never_invents_stock(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $order = $this->order($user);
        $order->items()->create(['perfume_id' => $product->id, 'quantity' => 2, 'price' => 1, 'volume_ml' => 100]);
        $this->post(route('orders.cancel', $order))->assertSessionHas('success');
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_demo_payment_is_idempotent_and_makes_no_network_calls(): void
    {
        config(['demo.enabled' => true]);
        $this->customer();
        $product = $this->product();
        $this->withSession(['cart' => [$product->id => 1]])
            ->post(route('payment.process'), $this->address() + ['payment_method' => 'momo'])
            ->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertTrue($order->is_demo);
        $this->assertSame(9, $product->fresh()->stock);
        $this->get(route('user.orders.payment.pending', $order))->assertOk()->assertSee('KHÔNG THU TIỀN THẬT');
        $this->post(route('user.orders.confirm.payment', $order), ['scenario' => 'declined'])->assertSessionHas('error');
        $this->assertSame('pending', $order->fresh()->status);
        $this->post(route('user.orders.confirm.payment', $order), ['scenario' => 'success'])->assertSessionHas('success');
        $this->post(route('user.orders.confirm.payment', $order), ['scenario' => 'success'])->assertSessionHas('success');
        $this->assertSame(1, $order->paymentTransactions()->where('status', 'paid')->count());
        $this->assertSame(9, $product->fresh()->stock);
        Http::assertNothingSent();
    }

    public function test_demo_confirm_rejects_real_cancelled_and_other_users_orders(): void
    {
        config(['demo.enabled' => true]);
        $user = $this->customer();
        $real = $this->order($user);
        $this->post(route('user.orders.confirm.payment', $real), ['scenario' => 'success'])->assertNotFound();
        $cancelled = $this->order($user, ['is_demo' => true, 'status' => 'cancelled']);
        $this->post(route('user.orders.confirm.payment', $cancelled), ['scenario' => 'success'])->assertSessionHasErrors('payment');
        $other = $this->order(User::factory()->create(), ['is_demo' => true]);
        $this->post(route('user.orders.confirm.payment', $other), ['scenario' => 'success'])->assertForbidden();
    }

    public function test_disabled_demo_rejects_manual_payment_and_email_preview(): void
    {
        $user = $this->customer();
        $order = $this->order($user, ['is_demo' => true]);
        $this->post(route('user.orders.confirm.payment', $order), ['scenario' => 'success'])->assertNotFound();
        $this->post(route('verification.demo'))->assertNotFound();
    }

    public function test_demo_switch_cannot_enable_shortcuts_in_production(): void
    {
        config(['demo.enabled' => true, 'mail.default' => 'log']);
        $this->app->instance('env', 'production');
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $user = $this->customer();
        $order = $this->order($user, ['is_demo' => true]);
        $this->post(route('user.orders.confirm.payment', $order), ['scenario' => 'success'])->assertNotFound();
        $this->post(route('verification.demo'))->assertNotFound();
    }

    public function test_demo_email_uses_a_signed_link_for_current_user_only(): void
    {
        config(['demo.enabled' => true, 'mail.default' => 'log']);
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);
        $response = $this->post(route('verification.demo'), ['id' => 999]);
        $this->assertStringContainsString('/email/verify/'.$user->id.'/', $response->headers->get('Location'));
        $this->get($response->headers->get('Location'))->assertRedirect(route('login'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_review_requires_delivered_purchase(): void
    {
        $this->customer();
        $product = $this->product();
        $this->post(route('store.review', $product), ['rating' => 5, 'body' => 'Mùi hương rất dễ chịu.'])
            ->assertSessionHasErrors('review');
        $this->assertDatabaseCount('perfume_reviews', 0);
    }

    public function test_admin_cancel_is_idempotent_and_terminal_order_cannot_reopen(): void
    {
        $user = $this->customer();
        $product = $this->product();
        $order = $this->order($user, ['inventory_status' => 'unreserved']);
        $order->items()->create(['perfume_id' => $product->id, 'quantity' => 1, 'price' => 650000, 'volume_ml' => 50]);
        app(OrderInventoryService::class)->reserve($order);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patch(route('admin.orders.update', $order), ['status' => 'cancelled'])->assertSessionHas('success');
        $this->patch(route('admin.orders.update', $order), ['status' => 'cancelled'])->assertSessionHas('success');
        $this->assertSame(3, $product->fresh()->stock_50ml);
        $this->patch(route('admin.orders.update', $order), ['status' => 'confirmed'])->assertSessionHas('error');
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_marking_delivered_does_not_forge_online_payment(): void
    {
        $user = $this->customer();
        $order = $this->order($user);
        $tx = $order->paymentTransactions()->create(['gateway' => 'momo', 'amount' => 1000000, 'status' => 'pending']);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patch(route('admin.orders.update', $order), ['status' => 'completed'])->assertSessionHas('success');
        $this->assertSame('pending', $tx->fresh()->status);
    }

    public function test_report_csv_separates_demo_from_real_and_requires_admin(): void
    {
        $user = $this->customer();
        $real = $this->order($user, ['status' => 'completed', 'total_price' => 123456]);
        $demo = $this->order($user, ['status' => 'completed', 'is_demo' => true, 'total_price' => 987654]);
        $this->get(route('admin.reports.export'))->assertRedirect(route('home'));
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->get(route('admin.reports.export'))->assertOk();
        $this->assertStringContainsString('123456', $response->streamedContent());
        $this->assertStringNotContainsString('987654', $response->streamedContent());
        $response = $this->get(route('admin.reports.export', ['mode' => 'demo']))->assertOk();
        $this->assertStringContainsString('987654', $response->streamedContent());
        $this->assertStringNotContainsString('123456', $response->streamedContent());
    }

    public function test_ai_cards_use_public_catalog_fields_not_model_supplied_links(): void
    {
        $user = $this->customer();
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['image_url' => 'images/products/rose.jpg']);
        Message::create(['sender_id' => $admin->id, 'receiver_id' => $user->id,
            'content' => 'Bạn có thể xem Hạ Thu Rose. https://example.invalid', 'is_ai' => true]);
        $this->getJson(route('user.chat.messages'))->assertOk()
            ->assertJsonPath('0.products.0.url', route('perfumes.show', $product))
            ->assertJsonPath('0.products.0.price', 1000000);
        $product->update(['is_active' => false]);
        $this->getJson(route('user.chat.messages'))->assertJsonPath('0.products', []);
    }

    public function test_unconfigured_shipping_webhook_is_rejected(): void
    {
        $this->postJson(route('ghn.webhook'), ['OrderCode' => 'anything', 'Status' => 'delivered'])->assertForbidden();
    }

    public function test_unstocked_variant_never_derives_inventory_from_full_bottles(): void
    {
        $product = $this->product(['stock_10ml' => null, 'stock_50ml' => null]);
        $this->assertSame(0, $product->getStockForVolume(10));
        $this->assertSame(0, $product->getStockForVolume(50));
        $this->assertSame(10, $product->getStockForVolume(100));
    }

    public function test_base_fifty_ml_product_uses_its_own_base_price_and_stock(): void
    {
        $this->customer();
        $product = $this->product(['volume_ml' => 50]);
        $this->withSession(['cart' => [$product->id => 2]])->post(route('cart.checkout'), $this->address())
            ->assertSessionHasNoErrors();
        $this->assertEquals(2000000, Order::firstOrFail()->total_price);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(3, $product->fresh()->stock_50ml);
    }
}
