<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\Product;
use App\Models\User;
use App\Services\CartQuoteService;
use App\Services\CartStockService;
use App\Services\GHNService;
use App\Services\OrderInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductVariantsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Mail::fake();
        Queue::fake();
    }

    private function product(): Perfume
    {
        return Perfume::create(['name' => 'Dior Sauvage', 'slug' => (string) Str::uuid(), 'brand' => 'Dior',
            'gender' => 'nam', 'volume_ml' => 100, 'price' => 3780000, 'stock' => 16,
            'stock_5ml' => 5, 'stock_10ml' => 40, 'stock_50ml' => 24, 'weight' => 200,
            'image_url' => 'images/products/dior-sauvage.jpg', 'is_active' => true]);
    }

    private function variant(array $overrides = []): array
    {
        return array_replace(['volume_ml' => 200, 'price' => 5200000, 'stock' => 7, 'weight' => 450, 'is_active' => 1], $overrides);
    }

    private function payload(Perfume $product, array $variants): array
    {
        return [...$product->only(['name', 'brand', 'gender', 'volume_ml', 'price', 'stock', 'stock_5ml', 'stock_10ml', 'stock_50ml', 'weight', 'is_active']), 'variants' => $variants];
    }

    private function order(Perfume $product, array $quantities): Order
    {
        $order = Order::create(['user_id' => User::factory()->create()->id, 'customer_name' => 'Khách thử', 'phone' => '0912345678',
            'address' => 'Hà Nội', 'total_price' => 10400000, 'status' => 'pending', 'inventory_status' => 'unreserved']);
        foreach ($quantities as [$volume, $quantity]) {
            $order->items()->create(['perfume_id' => $product->id, 'volume_ml' => $volume, 'quantity' => $quantity, 'price' => 5200000]);
        }

        return $order;
    }

    public function test_admin_adds_updates_and_hides_200ml_without_replacing_existing_bottle(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = $this->product();
        $this->put(route('admin.products.update', $product), $this->payload($product, [$this->variant()]))->assertSessionHasNoErrors();
        $variant = $product->variants()->sole();
        $this->assertSame(200, $variant->volume_ml);
        $this->assertSame(7, $variant->stock);
        $this->assertEquals(100, $product->fresh()->volume_ml);
        $this->assertSame(16, $product->fresh()->stock);
        $this->assertEquals(3780000, $product->fresh()->price);
        $this->get(route('admin.products.index'))->assertOk()->assertSee('200');
        $this->get(route('admin.products.show', $product))->assertOk()->assertSee('200 ml')->assertSee('5.200.000₫');
        $this->get(route('admin.products.edit', $product))->assertOk()->assertSee('Thêm dung tích')->assertSee('Chai 200 ml');
        $this->put(route('admin.products.update', $product), $this->payload($product, [
            $this->variant(['id' => $variant->id, 'stock' => 3, 'price' => 4900000, 'is_active' => 0]),
        ]))->assertSessionHasNoErrors();
        $this->assertSame(4900000, $variant->fresh()->price);
        $this->assertFalse($variant->fresh()->is_active);
        $this->assertNotContains(200, $product->fresh()->saleVolumes());
        $this->assertDatabaseCount('perfume_variants', 1);
    }

    public function test_admin_can_create_product_with_two_additional_sizes(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $source = $this->product();
        $this->post(route('admin.products.store'), $this->payload($source, [$this->variant(), $this->variant(['volume_ml' => 300])]))
            ->assertRedirect(route('admin.products.index'))->assertSessionHasNoErrors();
        $new = Product::latest('id')->firstOrFail();
        $this->assertCount(2, $new->variants);
        $this->assertNotEquals($source->id, $new->id);
    }

    public function test_invalid_variants_are_rejected_atomically_and_saved_identity_is_protected(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = $this->product();
        $saved = $product->variants()->create($this->variant());
        $foreign = $this->product()->variants()->create($this->variant(['volume_ml' => 300]));
        foreach ([
            [$this->variant(['volume_ml' => 100])], [$this->variant(['volume_ml' => 5])],
            [$this->variant(['volume_ml' => 10])], [$this->variant(['volume_ml' => 50])],
            [$this->variant()], [$this->variant(['volume_ml' => 300]), $this->variant(['volume_ml' => 300])],
            [$this->variant(['id' => $foreign->id, 'volume_ml' => 300])],
            [$this->variant(['id' => $saved->id, 'volume_ml' => 250])],
            [$this->variant(['volume_ml' => 300, 'stock' => -1])],
            [$this->variant(['volume_ml' => 300, 'price' => -1])],
            [$this->variant(['volume_ml' => 300, 'weight' => 0])],
        ] as $rows) {
            $this->putJson(route('admin.products.update', $product), array_replace($this->payload($product, $rows), ['name' => 'Should roll back']))
                ->assertUnprocessable();
            $this->assertSame('Dior Sauvage', $product->fresh()->name);
            $this->assertDatabaseCount('perfume_variants', 2);
        }
        $this->putJson(route('admin.products.update', $product), array_replace($this->payload($product, []), ['volume_ml' => 200]))
            ->assertUnprocessable()->assertJsonValidationErrors('volume_ml');
        $this->assertEquals(100, $product->fresh()->volume_ml);
    }

    public function test_detail_quick_view_and_cart_use_actual_variant_price_weight_and_available_stock(): void
    {
        $product = $this->product();
        $product->variants()->create($this->variant());
        $this->get(route('perfumes.show', $product))->assertOk()->assertSee('data-volume="200"', false)->assertSee('5.200.000₫');
        $this->getJson(route('perfumes.quick-view', $product))->assertOk()->assertJsonPath('variants.3.volume', 200)
            ->assertJsonPath('variants.3.stock', 7)->assertJsonPath('variants.3.price', 5200000);
        $this->actingAs(User::factory()->create());
        $this->post(route('cart.add', $product), ['quantity' => 2, 'volume_ml' => 200, 'price' => 1])->assertSessionHasNoErrors();
        $this->post(route('cart.add', $product), ['quantity' => 1, 'volume_ml' => 100])->assertSessionHasNoErrors();
        $cart = session('cart');
        $this->assertCount(2, $cart);
        $quote = app(CartQuoteService::class)->quote($cart);
        $this->assertSame(14180000, $quote['total']);
        $this->assertSame(1100, $quote['weight']);
        $this->assertSame(5, CartStockService::remaining($product->fresh(), 200, $cart));
        $this->assertSame(15, CartStockService::remaining($product->fresh(), 100, $cart));
        $this->get(route('cart.index'))->assertOk()->assertSee('200');
        $this->post(route('cart.add', $product), ['quantity' => 6, 'volume_ml' => 200, 'addon_gift' => 1])->assertSessionHasErrors('quantity');
        $this->postJson(route('cart.add', $product), ['quantity' => 1, 'volume_ml' => 250])->assertUnprocessable();
        $this->assertSame($cart, session('cart'));
    }

    public function test_reservation_and_cancellation_restore_exact_variant_even_if_hidden_and_product_archived(): void
    {
        $product = $this->product();
        $variant = $product->variants()->create($this->variant());
        $order = $this->order($product, [[200, 2], [200, 1], [100, 1]]);
        $inventory = app(OrderInventoryService::class);
        $inventory->reserve($order);
        $inventory->reserve($order);
        $this->assertSame(4, $variant->fresh()->stock);
        $this->assertSame(15, $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', ['stock_column' => 'variant:'.$variant->id, 'volume_ml' => 200, 'quantity_change' => -3]);
        $variant->update(['is_active' => false, 'price' => 1]);
        $product->delete();
        $inventory->release($order);
        $inventory->release($order);
        $this->assertSame(7, $variant->fresh()->stock);
        $this->assertSame(16, Perfume::withTrashed()->findOrFail($product->id)->stock);
        $this->assertDatabaseCount('inventory_movements', 4);
        $this->assertEquals(5200000, $order->items()->first()->price);
    }

    public function test_insufficient_or_hidden_variant_rolls_back_all_reservations(): void
    {
        $product = $this->product();
        $variant = $product->variants()->create($this->variant(['stock' => 2]));
        foreach ([true, false] as $active) {
            $variant->update(['is_active' => $active]);
            $order = $this->order($product, [[100, 1], [200, 2], [200, 1]]);
            try {
                app(OrderInventoryService::class)->reserve($order);
                $this->fail('Unavailable variant must reject the reservation.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('cart', $exception->errors());
            }
            $this->assertSame(16, $product->fresh()->stock);
            $this->assertSame(2, $variant->fresh()->stock);
            $this->assertSame('unreserved', $order->fresh()->inventory_status);
            $this->assertDatabaseCount('inventory_movements', 0);
        }
    }

    public function test_checkout_creates_200ml_order_and_reserves_its_stock(): void
    {
        config(['demo.enabled' => true]);
        $this->actingAs(User::factory()->create());
        $ghn = $this->createMock(GHNService::class);
        $ghn->expects($this->once())->method('packageParameters')->with(900)->willReturn(['weight' => 900, 'length' => 15, 'width' => 15, 'height' => 10]);
        $ghn->method('calculateFee')->willReturn(['code' => 200, 'data' => ['total' => 20900]]);
        $ghn->method('cancelOrder')->willReturn(['code' => 200]);
        $this->app->instance(GHNService::class, $ghn);
        $product = $this->product();
        $variant = $product->variants()->create($this->variant());
        $this->post(route('cart.add', $product), ['quantity' => 2, 'volume_ml' => 200])->assertSessionHasNoErrors();
        $this->post(route('payment.process'), ['name' => 'Khách thử', 'phone' => '0912345678', 'address' => 'Hà Nội',
            'to_district_id' => 1493, 'to_ward_code' => 'TEST', 'payment_method' => 'cod', 'checkout_key' => (string) Str::uuid()])
            ->assertSessionHasNoErrors()->assertSessionMissing('cart');
        $order = Order::sole();
        $this->assertEquals(10420900, $order->total_price);
        $this->assertEquals(200, $order->items->sole()->volume_ml);
        $this->assertEquals(5200000, $order->items->sole()->price);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertSame(16, $product->fresh()->stock);
        $this->post(route('orders.cancel', $order))->assertSessionHasNoErrors();
        $this->assertSame(7, $variant->fresh()->stock);
    }

    public function test_render_variant_forms_after_malformed_input_and_optionally_export_browser_fixtures(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = $this->product();
        $product->variants()->create($this->variant());
        $url = route('admin.products.edit', $product);
        $this->from($url)->put(route('admin.products.update', $product), $this->payload($product, [['volume_ml' => [200], 'price' => 'invalid']]))
            ->assertSessionHasErrors();
        $this->get($url)->assertOk()->assertSee('Thêm dung tích');
        session()->forget(['_old_input', 'errors']);
        if (getenv('SOOPI_EXPORT_VARIANTS') === '1') {
            $this->withVite();
            $dir = storage_path('app/variant-review');
            File::ensureDirectoryExists($dir);
            File::put($dir.'/edit.html', $this->get($url)->assertOk()->getContent());
            File::put($dir.'/product.html', $this->get(route('perfumes.show', $product))->assertOk()->getContent());
            File::put($dir.'/quick.json', $this->getJson(route('perfumes.quick-view', $product))->assertOk()->getContent());
        }
    }
}
