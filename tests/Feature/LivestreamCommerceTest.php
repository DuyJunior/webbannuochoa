<?php

namespace Tests\Feature;

use App\Models\Livestream;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Perfume;
use App\Models\User;
use App\Services\LivestreamRevenueService;
use App\Services\GHNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivestreamCommerceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function perfume(string $name, int $price = 300000): Perfume
    {
        return Perfume::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.str()->random(5),
            'brand' => 'Ha Thu',
            'gender' => 'nu',
            'volume_ml' => 100,
            'price' => $price,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    public function test_staff_can_manage_multiple_products_and_customer_sees_live_shelf(): void
    {
        $staff = User::factory()->create(['role' => 'livestream_staff']);
        $first = $this->perfume('Hương hoa');
        $second = $this->perfume('Hương gỗ');
        $third = $this->perfume('Hương biển');

        $this->actingAs($staff)->post(route('admin.livestreams.store'), [
            'title' => 'Chọn hương cùng Ha Thu', 'source' => 'browser', 'status' => 'scheduled',
            'launch_mode' => 'now', 'perfume_ids' => [$first->id, $second->id],
        ])->assertRedirect();

        $stream = Livestream::firstOrFail();
        $this->assertSame([$first->id, $second->id], $stream->products()->pluck('perfumes.id')->all());
        $this->get(route('livestream.show'))->assertOk()->assertSee('Hương hoa')->assertSee('Hương gỗ');

        $added = $this->postJson(route('admin.livestreams.products.store', $stream), ['perfume_id' => $third->id])->assertOk();
        $this->assertStringContainsString('Hương biển', $added->json('html'));
        $shelf = $this->getJson(route('livestream.products', $stream))->assertOk();
        $this->assertStringContainsString('Hương biển', $shelf->json('html'));
        $this->deleteJson(route('admin.livestreams.products.destroy', [$stream, $first]))->assertOk();
        $this->assertDatabaseMissing('livestream_products', ['livestream_id' => $stream->id, 'perfume_id' => $first->id]);

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->postJson(route('admin.livestreams.products.store', $stream), ['perfume_id' => $first->id])
            ->assertForbidden();
    }

    public function test_clicking_live_product_tags_cart_line_and_order_without_tagging_other_products(): void
    {
        $stream = Livestream::create(['title' => 'Trực tiếp', 'source' => 'browser', 'status' => 'live', 'last_heartbeat_at' => now()]);
        $liveProduct = $this->perfume('Hương live');
        $other = $this->perfume('Hương thường');
        $stream->products()->attach($liveProduct->id);
        $this->actingAs(User::factory()->create(['role' => 'user']));
        $this->getJson(route('livestream.state'))
            ->assertOk()
            ->assertJsonPath('viewer_token_url', route('livestream.viewer-token', $stream));
        $this->get(route('perfumes.show', $liveProduct))
            ->assertOk()->assertSee('id="live-follow"', false);

        $this->get(route('livestream.products.open', [$stream, $liveProduct]))
            ->assertRedirect(route('perfumes.show', $liveProduct))
            ->assertSessionHas('live_product.livestream_id', $stream->id);
        $this->post(route('cart.add', $liveProduct), ['quantity' => 1])->assertSessionHas('cart');
        $this->post(route('cart.add', $other), ['quantity' => 1])->assertSessionHas('cart', function ($cart) use ($stream) {
            return count($cart) === 2
                && in_array($stream->id, array_column(array_filter($cart, 'is_array'), 'livestream_id'), true);
        });

        $this->post(route('cart.checkout'), [
            'customer_name' => 'Khách xem live', 'phone' => '0912345678', 'address' => 'Hà Nội',
        ])->assertRedirect(route('home'));
        $order = Order::firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame($stream->id, $order->items()->where('perfume_id', $liveProduct->id)->firstOrFail()->livestream_id);
        $this->assertNull($order->items()->where('perfume_id', $other->id)->firstOrFail()->livestream_id);
        $this->assertSame(0, app(LivestreamRevenueService::class)->summaries()['total']['revenue']);
    }

    public function test_report_counts_paid_live_merchandise_after_discounts_and_excludes_refunds(): void
    {
        $stream = Livestream::create(['title' => 'Bán hàng', 'source' => 'browser', 'status' => 'ended']);
        $liveProduct = $this->perfume('Hương live');
        $other = $this->perfume('Hương khác');
        $order = Order::create([
            'customer_name' => 'Khách', 'phone' => '0912345678', 'address' => 'Hà Nội',
            'total_price' => 500000, 'discount_amount' => 100000, 'status' => 'pending',
        ]);
        $order->items()->create(['perfume_id' => $liveProduct->id, 'quantity' => 1, 'price' => 300000, 'livestream_id' => $stream->id]);
        $order->items()->create(['perfume_id' => $other->id, 'quantity' => 1, 'price' => 300000]);
        $transaction = PaymentTransaction::create(['order_id' => $order->id, 'gateway' => 'momo', 'amount' => 500000, 'status' => 'pending']);

        $service = app(LivestreamRevenueService::class);
        $this->assertSame(250000, $service->summaries()['total']['pending_value']);
        $transaction->update(['status' => 'paid', 'paid_at' => now()]);
        $this->assertSame(250000, $service->summaries()['by_stream'][$stream->id]['revenue']);
        $this->actingAs(User::factory()->create(['role' => 'livestream_staff']))
            ->get(route('admin.livestreams.report', $stream))
            ->assertOk()->assertSee('250.000đ');

        PaymentTransaction::create(['order_id' => $order->id, 'gateway' => 'momo', 'amount' => 500000, 'status' => 'refunded']);
        $this->assertSame(0, $service->summaries()['total']['revenue']);
    }

    public function test_registered_checkout_keeps_live_attribution_on_order_item(): void
    {
        $shipping = $this->createMock(GHNService::class);
        $shipping->method('packageParameters')->willReturn(['service_type_id' => 2, 'weight' => 200, 'length' => 15, 'width' => 15, 'height' => 10]);
        $shipping->method('calculateFee')->willReturn(['code' => 200, 'data' => ['total' => 20900]]);
        $this->app->instance(GHNService::class, $shipping);

        $stream = Livestream::create(['title' => 'Đơn từ live', 'source' => 'browser', 'status' => 'ended']);
        $product = $this->perfume('Hương trong giỏ');
        $customer = User::factory()->create(['role' => 'user']);
        $this->actingAs($customer)->withSession(['cart' => ['live-item' => [
            'perfume_id' => $product->id, 'quantity' => 1, 'unit_price' => 300000,
            'volume_ml' => 100, 'livestream_id' => $stream->id,
        ]]])->post(route('payment.process'), [
            'name' => 'Khách mua', 'phone' => '0912345678', 'address' => 'Hà Nội',
            'to_district_id' => 1493, 'to_ward_code' => '1A0706', 'payment_method' => 'momo',
        ])->assertRedirect();

        $this->assertDatabaseHas('order_items', [
            'order_id' => Order::firstOrFail()->id,
            'perfume_id' => $product->id,
            'livestream_id' => $stream->id,
        ]);
    }
}
