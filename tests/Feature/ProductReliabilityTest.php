<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\Product;
use App\Models\User;
use App\Services\LoyaltyService;
use App\Services\OrderInventoryService;
use App\Services\ShippingUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductReliabilityTest extends TestCase
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
        return Perfume::create(array_merge(['name' => 'Original perfume', 'slug' => (string) Str::uuid(),
            'brand' => 'Demo', 'gender' => 'unisex', 'volume_ml' => 100, 'price' => 1000000,
            'stock' => 10, 'stock_50ml' => 4, 'is_active' => true], $extra));
    }

    private function order(User $user, array $extra = []): Order
    {
        return Order::create(array_merge(['user_id' => $user->id, 'customer_name' => 'Private Customer',
            'phone' => '0912345678', 'address' => 'Private address', 'total_price' => 1000000,
            'status' => 'pending', 'shipping_status' => 'pending', 'inventory_status' => 'unreserved'], $extra));
    }

    public function test_tracking_requires_login_and_never_discloses_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $order = $this->order($owner);
        $this->post(route('orders.tracking.search'), ['keyword' => $order->id])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post(route('orders.tracking.search'), ['keyword' => (string) $order->id])
            ->assertOk()->assertViewHas('orders', fn ($orders) => $orders->isEmpty())->assertDontSee('Private address');
        $this->actingAs($owner)->post(route('orders.tracking.search'), ['keyword' => (string) $order->id])
            ->assertOk()->assertViewHas('orders', fn ($orders) => $orders->count() === 1);
    }

    public function test_archiving_product_keeps_order_and_name_snapshot(): void
    {
        $product = $this->product();
        $order = $this->order(User::factory()->create());
        $item = $order->items()->create(['perfume_id' => $product->id, 'quantity' => 1, 'price' => 1000000, 'volume_ml' => 100]);
        $product->update(['name' => 'Renamed perfume']);
        Product::findOrFail($product->id)->delete();
        $this->assertNull(Perfume::find($product->id));
        $this->assertSame('Original perfume', $item->fresh()->product_name);
        $this->assertTrue($item->fresh()->product->trashed());
        $this->assertDatabaseHas('order_items', ['id' => $item->id]);
        $this->actingAs($order->user)->get(route('orders.show', $order))->assertOk()->assertSee('Original perfume');
    }

    public function test_release_uses_reserved_bucket_even_after_base_volume_change_and_archive(): void
    {
        $product = $this->product();
        $order = $this->order(User::factory()->create());
        $order->items()->create(['perfume_id' => $product->id, 'quantity' => 2, 'price' => 650000, 'volume_ml' => 50]);
        $inventory = app(OrderInventoryService::class);
        $inventory->reserve($order);
        $this->assertSame(2, $product->fresh()->stock_50ml);
        $product->update(['volume_ml' => 50]);
        $product->delete();
        $inventory->release($order);
        $inventory->release($order);
        $restored = Perfume::withTrashed()->findOrFail($product->id);
        $this->assertSame(10, $restored->stock);
        $this->assertSame(4, $restored->stock_50ml);
        $this->assertSame(2, DB::table('inventory_movements')->count());
        $this->assertSame('stock_50ml', $order->items()->first()->stock_components[0]['stock_column']);
    }

    public function test_unavailable_cart_items_render_removal_instead_of_error_loop(): void
    {
        $user = User::factory()->create();
        $inactive = $this->product(['is_active' => false]);
        $empty = $this->product(['stock' => 0]);
        $archived = $this->product();
        $archived->delete();
        $this->actingAs($user)->withSession(['cart' => [$inactive->id => 1, $empty->id => 1, $archived->id => 1]])
            ->get(route('cart.index'))->assertOk()->assertSee('Xóa sản phẩm không khả dụng')
            ->assertViewHas('unavailableItems', fn ($items) => count($items) === 3)->assertViewHas('subtotal', 0);
        $this->delete(route('cart.remove', $inactive->id))->assertRedirect();
        $this->assertArrayNotHasKey($inactive->id, session('cart'));
    }

    public function test_loyalty_excludes_pending_unpaid_returns_and_demo_orders(): void
    {
        config(['demo.enabled' => false]);
        $user = User::factory()->create();
        $this->order($user, ['total_price' => 6000000]);
        $this->order($user, ['total_price' => 6000000, 'status' => 'completed']);
        $demo = $this->order($user, ['total_price' => 6000000, 'status' => 'completed', 'is_demo' => true]);
        $demo->paymentTransactions()->create(['gateway' => 'demo', 'amount' => 6000000, 'status' => 'paid']);
        $real = $this->order($user, ['total_price' => 250000, 'status' => 'completed', 'shipping_status' => 'delivered']);
        $real->paymentTransactions()->create(['gateway' => 'cod', 'amount' => 250000, 'status' => 'paid']);
        $this->assertSame(250000.0, LoyaltyService::totalSpent($user->id));
        $this->assertSame(2, LoyaltyService::balance($user->id));
        $this->assertSame('silver', LoyaltyService::tier($user->id)['code']);
        config(['demo.enabled' => true]);
        $this->assertSame(60, LoyaltyService::balance($user->id));
        config(['demo.enabled' => false]);
        $real->update(['shipping_status' => 'returned']);
        $this->assertSame(0, LoyaltyService::balance($user->id));
    }

    public function test_repeated_checkout_key_creates_one_order_payment_and_stock_reservation(): void
    {
        config(['demo.enabled' => true]);
        $product = $this->product();
        $user = User::factory()->create();
        $payload = ['checkout_key' => (string) Str::uuid(), 'name' => 'Demo buyer', 'phone' => '0912345678',
            'address' => 'Demo address', 'to_district_id' => 1493, 'to_ward_code' => 'DEMO', 'payment_method' => 'sepay'];
        $this->actingAs($user)->withSession(['cart' => [$product->id => 1]])->post(route('payment.process'), $payload)->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->withSession(['cart' => [$product->id => 2]])->post(route('payment.process'), $payload)->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payment_transactions', 1);
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertEquals(2, session('cart')[$product->id]);
        $this->assertNotNull($order->payment_expires_at);
    }

    public function test_expired_unpaid_online_order_releases_once_but_paid_and_cod_are_untouched(): void
    {
        $product = $this->product();
        $user = User::factory()->create();
        $orders = [];
        foreach (['unpaid', 'paid', 'cod'] as $type) {
            $order = $this->order($user, ['payment_expires_at' => now()->subMinute()]);
            $order->items()->create(['perfume_id' => $product->id, 'quantity' => 1, 'price' => 1000000, 'volume_ml' => 100]);
            app(OrderInventoryService::class)->reserve($order);
            $order->paymentTransactions()->create(['gateway' => $type === 'cod' ? 'cod' : 'momo', 'amount' => 1000000,
                'status' => $type === 'paid' ? 'paid' : 'pending']);
            $orders[$type] = $order;
        }
        $this->artisan('orders:expire-unpaid')->assertSuccessful();
        $this->artisan('orders:expire-unpaid')->assertSuccessful();
        $this->assertSame('cancelled', $orders['unpaid']->fresh()->status);
        $this->assertSame('pending', $orders['paid']->fresh()->status);
        $this->assertSame('pending', $orders['cod']->fresh()->status);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_profile_updates_only_own_name_and_password_requires_current_password(): void
    {
        $this->get(route('account.edit'))->assertRedirect(route('login'));
        $user = User::factory()->create(['password' => 'OldPassword123']);
        $this->actingAs($user)->get(route('account.edit'))->assertOk()->assertSee('Thông tin của tôi');
        $this->patch(route('account.update'), ['name' => 'New name', 'role' => 'admin', 'email' => 'other@example.test'])
            ->assertSessionHasNoErrors();
        $this->assertSame('New name', $user->fresh()->name);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertNotSame('admin', $user->fresh()->role);
        $this->put(route('account.password'), ['current_password' => 'wrong', 'password' => 'NewPassword456', 'password_confirmation' => 'NewPassword456'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('account.password'), ['current_password' => 'OldPassword123', 'password' => 'NewPassword456', 'password_confirmation' => 'NewPassword456'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('NewPassword456', $user->fresh()->password));
    }

    public function test_shipping_delivery_is_idempotent_and_does_not_fake_online_payment(): void
    {
        $user = User::factory()->create();
        $service = app(ShippingUpdateService::class);
        foreach (['cod', 'momo'] as $gateway) {
            $order = $this->order($user, ['shipping_status' => 'delivering']);
            $payment = $order->paymentTransactions()->create(['gateway' => $gateway, 'amount' => 1000000, 'status' => 'pending']);
            $service->apply($order, 'delivered');
            $service->apply($order, 'delivered');
            $service->apply($order, 'ready_to_pick');
            $this->assertSame('completed', $order->fresh()->status);
            $this->assertSame('delivered', $order->fresh()->shipping_status);
            $this->assertSame($gateway === 'cod' ? 'paid' : 'pending', $payment->fresh()->status);
            $this->assertSame(2, $order->events()->count());
        }
    }

    public function test_shipping_cancel_restores_inventory_once_and_return_does_not_prematurely_restock(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        $service = app(ShippingUpdateService::class);
        $cancel = $this->order($user, ['shipping_status' => 'ready_to_pick']);
        $cancel->items()->create(['perfume_id' => $product->id, 'quantity' => 1, 'volume_ml' => 100, 'price' => 1000000]);
        app(OrderInventoryService::class)->reserve($cancel);
        $service->apply($cancel, 'cancel');
        $service->apply($cancel, 'cancel');
        $this->assertSame(10, $product->fresh()->stock);
        $return = $this->order($user, ['shipping_status' => 'delivering']);
        $return->items()->create(['perfume_id' => $product->id, 'quantity' => 1, 'volume_ml' => 100, 'price' => 1000000]);
        app(OrderInventoryService::class)->reserve($return);
        $service->apply($return, 'return');
        $this->assertSame('return', $return->fresh()->shipping_status);
        $service->apply($return, 'ready_to_pick');
        $service->apply($return, 'cancel');
        $this->assertSame('return', $return->fresh()->shipping_status);
        $this->assertSame(9, $product->fresh()->stock);
    }

    public function test_admin_cannot_rewind_shipping_to_bypass_cancellation_guard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = $this->order(User::factory()->create(), ['shipping_status' => 'delivering']);
        $this->actingAs($admin)->patch(route('admin.orders.update', $order), ['shipping_status' => 'ready_to_pick'])->assertSessionHas('error');
        $this->assertSame('delivering', $order->fresh()->shipping_status);
    }
}
