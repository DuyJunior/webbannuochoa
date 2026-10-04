<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutSelectionTest extends TestCase
{
    use RefreshDatabase;

    private array $quotedWeights = [];

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('SOOPI_EXPORT_CHECKOUT_AUDIT') !== '1') {
            $this->withoutVite();
        }
        Http::preventStrayRequests();
        config(['demo.enabled' => true]);
        $this->actingAs(User::factory()->create());
        $ghn = $this->createMock(GHNService::class);
        $ghn->method('packageParameters')->willReturnCallback(function ($weight) {
            $this->quotedWeights[] = $weight;

            return ['weight' => $weight, 'length' => 15, 'width' => 15, 'height' => 10];
        });
        $ghn->method('calculateFee')->willReturn(['code' => 200, 'data' => ['total' => 20900]]);
        $this->app->instance(GHNService::class, $ghn);
    }

    private function product(array $extra = []): Perfume
    {
        return Perfume::create(array_merge([
            'name' => 'Fragrance '.Str::uuid(), 'slug' => (string) Str::uuid(), 'brand' => 'Soopi',
            'gender' => 'unisex', 'volume_ml' => 100, 'weight' => 400, 'price' => 1000000,
            'stock' => 10, 'stock_5ml' => 10, 'is_active' => true,
        ], $extra));
    }

    private function address(array $extra = []): array
    {
        return array_replace(['name' => 'Khách', 'phone' => '0912345678', 'address' => 'Hà Nội',
            'to_district_id' => 1493, 'to_ward_code' => 'DEMO', 'payment_method' => 'sepay'], $extra);
    }

    private function box(array $ids, int $quantity = 1): array
    {
        return ['perfume_id' => $ids[0], 'sample_ids' => $ids, 'quantity' => $quantity,
            'is_discovery_box' => true, 'volume_ml' => 5, 'has_gift' => true,
            'title' => 'Hộp thử mùi 3 mẫu', 'sample_names' => 'Mẫu A, Mẫu B, Mẫu C'];
    }

    public function test_cart_form_and_checkout_summary_only_include_selected_lines(): void
    {
        $first = $this->product(['name' => 'Miss Dior Blooming Bouquet EDT', 'brand' => 'Dior',
            'image_url' => 'images/products/miss-dior-blooming.jpg']);
        $second = $this->product(['name' => 'Chanel Chance Eau Tendre EDP', 'brand' => 'Chanel',
            'image_url' => 'images/products/chanel-chance-tendre.jpg', 'weight' => 2000]);
        $cart = [$first->id => 2, $second->id => 3];
        $cartResponse = $this->withSession(['cart' => $cart])->get(route('cart.index'))->assertOk()
            ->assertSee('form="checkoutSelectionForm"', false)
            ->assertSee('id="checkoutSelectionForm"', false)
            ->assertSee('name="selection" value="1"', false);
        $checkoutResponse = $this->get(route('payment.index', ['selection' => 1, 'selected_items' => [(string) $first->id]]))
            ->assertOk()->assertSee($first->name)->assertDontSee($second->name)
            ->assertViewHas('totalPrice', 2000000)->assertViewHas('totalWeight', 800)
            ->assertViewHas('selectedKeys', [(string) $first->id]);
        $this->assertSame($cart, session('cart'));
        if (getenv('SOOPI_EXPORT_CHECKOUT_AUDIT') === '1') {
            $directory = storage_path('app/backups/checkout-audit');
            File::ensureDirectoryExists($directory);
            File::put($directory.'/cart.html', $cartResponse->getContent());
            File::put($directory.'/checkout.html', $checkoutResponse->getContent());
        }
    }

    public function test_payment_only_orders_selected_items_and_keeps_other_items_and_stock(): void
    {
        $first = $this->product();
        $second = $this->product();
        $key = (string) Str::uuid();
        $payload = $this->address(['selection' => 1, 'selected_items' => [(string) $first->id], 'checkout_key' => $key]);
        $this->withSession(['cart' => [$first->id => 2, $second->id => 3]])
            ->post(route('payment.process'), $payload)->assertSessionHasNoErrors()
            ->assertSessionHas('cart', [$second->id => 3]);
        $order = Order::firstOrFail();
        $this->assertEquals(2020900, $order->total_price);
        $this->assertSame([$first->id], $order->items->pluck('perfume_id')->all());
        $this->assertSame(8, $first->fresh()->stock);
        $this->assertSame(10, $second->fresh()->stock);
        $this->assertSame([800], $this->quotedWeights);
        $this->post(route('payment.process'), $payload)->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame([$second->id => 3], session('cart'));
    }

    public function test_empty_stale_duplicate_and_nested_selections_never_order_the_whole_cart(): void
    {
        $product = $this->product();
        $cart = [$product->id => 1];
        foreach ([[], ['removed-item'], [(string) $product->id, 'removed-item'], [(string) $product->id, (string) $product->id], [['invalid']]] as $keys) {
            $this->withSession(['cart' => $cart])->postJson(route('payment.process'), $this->address(['selection' => 1, 'selected_items' => $keys]))
                ->assertUnprocessable();
            $this->assertSame($cart, session('cart'));
        }
        $this->postJson(route('payment.process'), $this->address(['selection' => 1]))->assertUnprocessable();
        $this->get(route('payment.index', ['selection' => 1]))->assertRedirect(route('cart.index'))->assertSessionHasErrors('selected_items');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame([], $this->quotedWeights);
    }

    public function test_invalid_address_preserves_explicit_selection_on_return_to_payment(): void
    {
        $first = $this->product();
        $second = $this->product();
        $this->withSession(['cart' => [$first->id => 1, $second->id => 1]])
            ->from(route('payment.index'))->post(route('payment.process'), $this->address([
                'name' => '', 'selection' => 1, 'selected_items' => [(string) $second->id],
            ]))->assertRedirect(route('payment.index'))->assertSessionHasErrors('name');
        $this->get(route('payment.index'))->assertOk()->assertViewHas('selectedKeys', [(string) $second->id])
            ->assertSee($second->name)->assertDontSee($first->name);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_shipping_quote_uses_selected_discovery_box_components(): void
    {
        $products = collect([$this->product(), $this->product(), $this->product()]);
        $other = $this->product(['weight' => 5000]);
        $cart = ['box' => $this->box($products->pluck('id')->all(), 2), $other->id => 4];
        $this->withSession(['cart' => $cart])->postJson(route('locations.fee'), [
            'to_district_id' => 1493, 'to_ward_code' => 'DEMO', 'selection' => 1, 'selected_items' => ['box'],
        ])->assertOk()->assertJsonPath('data.total', 20900);
        $this->assertSame([300], $this->quotedWeights);
        $this->get(route('payment.index', ['selection' => 1, 'selected_items' => ['box']]))
            ->assertOk()->assertSee('Hộp thử mùi · 3 mẫu')->assertSee('3 × 5ml')->assertViewHas('totalWeight', 300);
    }

    public function test_shipping_order_uses_same_discovery_box_weight_as_checkout(): void
    {
        $products = collect([$this->product(), $this->product(), $this->product()]);
        $order = Order::create(['user_id' => auth()->id(), 'customer_name' => 'Khách', 'phone' => '0912345678',
            'address' => 'Hà Nội', 'total_price' => 398000, 'status' => 'pending', 'shipping_status' => 'pending',
            'is_demo' => false, 'to_district_id' => 1493, 'to_ward_code' => 'DEMO']);
        $order->items()->create(['perfume_id' => $products[0]->id, 'quantity' => 2, 'price' => 199000, 'volume_ml' => 5,
            'stock_components' => $products->map(fn ($p) => ['perfume_id' => $p->id, 'volume_ml' => 5])->all()]);
        $ghn = $this->createMock(GHNService::class);
        $ghn->expects($this->once())->method('packageParameters')->with(300)->willReturn(['weight' => 300, 'length' => 15, 'width' => 15, 'height' => 10]);
        $ghn->expects($this->once())->method('createOrder')->with($this->callback(fn ($payload) => $payload['weight'] === 300
            && $payload['items'][0]['weight'] === 150 && $payload['items'][0]['quantity'] === 2
            && str_contains($payload['items'][0]['name'], 'Hộp thử mùi')))->willReturn(['code' => 200]);
        $this->assertSame(['code' => 200], (new GHNOrderService($ghn))->create($order));
    }

    public function test_box_quantity_uses_scarcest_sample_and_other_cart_allocations(): void
    {
        $first = $this->product(['stock_5ml' => 10]);
        $scarce = $this->product(['stock_5ml' => 3]);
        $third = $this->product();
        $box = $this->box([$first->id, $scarce->id, $third->id]);
        $cart = ['one' => $box, 'two' => $box];
        $this->withSession(['cart' => $cart])->patch(route('cart.update', 'one'), ['quantity' => 3])
            ->assertSessionHasErrors('quantity')->assertSessionHas('cart', $cart);
        $this->get(route('cart.index'))->assertOk()->assertViewHas('items', fn ($items) => $items[0]['max_quantity'] === 2)
            ->assertSee('Giá trọn hộp mẫu thử')->assertDontSee('Gói quà Luxury &amp; Thiệp (+50.000₫)', false);
        $this->patch(route('cart.update', 'one'), ['quantity' => 2])->assertSessionHas('success')
            ->assertSessionHas('cart.one.quantity', 2);
        $this->assertSame(3, $scarce->fresh()->stock_5ml);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_regular_variant_quantity_counts_other_gift_lines_using_same_stock(): void
    {
        $product = $this->product(['stock' => 3]);
        $cart = [$product->id => 2, 'gift' => ['perfume_id' => $product->id, 'quantity' => 1, 'volume_ml' => 100, 'has_gift' => true]];
        $this->withSession(['cart' => $cart])->patch(route('cart.update', 'gift'), ['quantity' => 2])
            ->assertSessionHasErrors('quantity')->assertSessionHas('cart', $cart);
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_add_and_buy_now_respect_gift_and_livestream_allocations(): void
    {
        $product = $this->product(['stock' => 3]);
        $cart = ['gift' => ['perfume_id' => $product->id, 'volume_ml' => 100, 'quantity' => 1, 'has_gift' => true],
            'live' => ['perfume_id' => $product->id, 'volume_ml' => 100, 'quantity' => 1, 'livestream_id' => 1]];
        $this->withSession(['cart' => $cart])->getJson(route('perfumes.quick-view', $product))
            ->assertOk()->assertJsonPath('variants.0.max_quantity', 1)->assertJsonPath('variants.0.in_cart', 2);
        foreach ([false, true] as $buyNow) {
            $this->post(route('cart.add', $product), ['volume_ml' => 100, 'quantity' => 2, 'buy_now' => $buyNow])
                ->assertSessionHasErrors('quantity')->assertSessionHas('cart', $cart);
        }
        $this->post(route('cart.add', $product), ['volume_ml' => 100, 'quantity' => 1])->assertSessionHas('success');
        $this->assertSame(1, session('cart')[$product->id]);
        $this->post(route('cart.add', $product), ['volume_ml' => 100, 'quantity' => 1, 'buy_now' => true])
            ->assertRedirect(route('cart.index'));
        $this->assertSame(1, session('cart')[$product->id]);
    }

    public function test_native_five_ml_quick_view_and_add_count_discovery_box_allocation(): void
    {
        $native = $this->product(['volume_ml' => 5, 'stock' => 2, 'stock_5ml' => 0]);
        $second = $this->product();
        $third = $this->product();
        $cart = ['box' => $this->box([$native->id, $second->id, $third->id])];
        $this->withSession(['cart' => $cart])->getJson(route('perfumes.quick-view', $native))
            ->assertOk()->assertJsonPath('variants.0.max_quantity', 1)->assertJsonPath('variants.0.volume', 5);
        $this->post(route('cart.add', $native), ['quantity' => 2])->assertSessionHasErrors('quantity')
            ->assertSessionHas('cart', $cart);
        $this->post(route('cart.add', $native), ['quantity' => 1])->assertSessionHas('success');
        $this->assertSame(2, $native->fresh()->stock);
    }
}
