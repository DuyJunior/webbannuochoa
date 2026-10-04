<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Services\CartQuoteService;
use App\Services\GHNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductSizePricingTest extends TestCase
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

    private function product(array $extra = []): Perfume
    {
        return Perfume::create(array_replace([
            'name' => 'Dior size prices', 'brand' => 'Dior', 'slug' => (string) Str::uuid(),
            'gender' => 'unisex', 'price' => 3390000, 'volume_ml' => 100, 'weight' => 200,
            'stock' => 15, 'stock_5ml' => 0, 'stock_10ml' => 38, 'stock_50ml' => 23, 'is_active' => true,
        ], $extra));
    }

    private function payload(Perfume $product, array $extra = []): array
    {
        return array_replace($product->only(['name', 'brand', 'gender', 'price', 'volume_ml', 'weight',
            'stock', 'stock_5ml', 'stock_10ml', 'stock_50ml', 'is_active']), $extra);
    }

    public function test_size_prices_flow_from_admin_to_store_cart_and_checkout_without_repricing_old_orders(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $product = $this->product();
        $this->put(route('admin.products.update', $product), $this->payload($product, [
            'price_10ml' => 850000, 'price_50ml' => 2500000,
        ]))->assertSessionHasNoErrors();
        $this->assertEquals(3390000, $product->fresh()->price);
        $this->assertSame(850000, $product->fresh()->price_10ml);
        $this->get(route('admin.products.edit', $product))->assertOk()->assertSee('name="price_10ml"', false)->assertSee('name="price_50ml"', false);
        $this->get(route('perfumes.show', $product))->assertOk()->assertSee('850.000₫')->assertSee('2.500.000₫');
        $this->getJson(route('perfumes.quick-view', $product))->assertOk()
            ->assertJsonPath('variants.1.price', 2500000)->assertJsonPath('variants.2.price', 850000);

        config(['demo.enabled' => true]);
        $ghn = $this->createMock(GHNService::class);
        $ghn->method('packageParameters')->willReturn(['weight' => 230, 'length' => 15, 'width' => 15, 'height' => 10]);
        $ghn->method('calculateFee')->willReturn(['code' => 200, 'data' => ['total' => 20900]]);
        $this->app->instance(GHNService::class, $ghn);
        $this->actingAs(User::factory()->create());
        $this->post(route('cart.add', $product), ['volume_ml' => 10, 'quantity' => 2, 'price' => 1])->assertSessionHasNoErrors();
        $this->post(route('cart.add', $product), ['volume_ml' => 50, 'quantity' => 1, 'price' => 1])->assertSessionHasNoErrors();
        $this->assertSame(4200000, app(CartQuoteService::class)->quote(session('cart'))['total']);
        $this->post(route('payment.process'), ['name' => 'Khách thử', 'phone' => '0912345678', 'address' => 'Hà Nội',
            'to_district_id' => 1493, 'to_ward_code' => 'TEST', 'payment_method' => 'cod', 'checkout_key' => (string) Str::uuid()])
            ->assertSessionHasNoErrors()->assertSessionMissing('cart');
        $order = Order::sole();
        $this->assertEquals(4220900, $order->total_price);
        $this->assertEquals(850000, $order->items()->where('volume_ml', 10)->sole()->price);
        $this->assertEquals(2500000, $order->items()->where('volume_ml', 50)->sole()->price);
        $this->assertSame(36, $product->fresh()->stock_10ml);
        $this->assertSame(22, $product->fresh()->stock_50ml);
        $this->assertSame(15, $product->fresh()->stock);

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload($product->fresh(), ['price_10ml' => 900000]))
            ->assertSessionHasNoErrors();
        $this->assertEquals(850000, $order->items()->where('volume_ml', 10)->sole()->price);
        $this->assertSame(900000, app(CartQuoteService::class)->unitPrice($product->fresh(), 10));
        $this->assertSame(2500000, $product->fresh()->price_50ml);

        if (getenv('SOOPI_EXPORT_SIZE_PRICING') === '1') {
            $this->withVite();
            $product->variants()->create(['volume_ml' => 125, 'price' => 4200000, 'stock' => 7, 'weight' => 300, 'is_active' => true]);
            $directory = storage_path('app/size-pricing-review');
            File::ensureDirectoryExists($directory);
            File::put($directory.'/edit.html', $this->get(route('admin.products.edit', $product))->assertOk()->getContent());
            File::put($directory.'/product.html', $this->get(route('perfumes.show', $product))->assertOk()->getContent());
        }
    }

    public function test_automatic_prices_zero_overrides_and_original_bottle_priority(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = $this->product();
        $prices = app(CartQuoteService::class);
        $this->assertSame(750000, $prices->unitPrice($product, 10));
        $this->assertSame(2200000, $prices->unitPrice($product, 50));
        $this->put(route('admin.products.update', $product), $this->payload($product, ['price_10ml' => 0, 'price_50ml' => 999000]))->assertSessionHasNoErrors();
        $this->assertSame(0, $prices->unitPrice($product->fresh(), 10));
        $this->assertSame(50000, $prices->unitPrice($product->fresh(), 10, true));
        $this->put(route('admin.products.update', $product), $this->payload($product, ['price_10ml' => '', 'price_50ml' => '']))->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->price_10ml);
        $this->assertNull($product->fresh()->price_50ml);
        $this->assertSame(750000, $prices->unitPrice($product->fresh(), 10));
        $this->assertSame(2200000, $prices->unitPrice($product->fresh(), 50));
        $native = $this->product(['volume_ml' => 10, 'price' => 700000, 'sale_price' => 650000, 'price_10ml' => 100000]);
        $this->assertSame(650000, $prices->unitPrice($native, 10));
    }

    public function test_invalid_size_prices_do_not_change_catalog_and_legacy_editor_preserves_omitted_prices(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $product = $this->product(['price_10ml' => 800000, 'price_50ml' => 2300000]);
        foreach (['price_10ml', 'price_50ml'] as $field) {
            foreach ([-1, '12.5', ['bad'], 1000000000000] as $invalid) {
                $this->putJson(route('admin.products.update', $product), $this->payload($product, [$field => $invalid]))
                    ->assertUnprocessable()->assertJsonValidationErrors($field);
                $this->assertSame(800000, $product->fresh()->price_10ml);
                $this->assertSame(2300000, $product->fresh()->price_50ml);
            }
        }
        $this->put(route('perfumes.update', $product), $this->payload($product))->assertSessionHasNoErrors();
        $this->assertSame(800000, $product->fresh()->price_10ml);
        $this->put(route('perfumes.update', $product), $this->payload($product, ['price_10ml' => 950000]))->assertSessionHasNoErrors();
        $this->assertSame(950000, $product->fresh()->price_10ml);
        $this->assertSame(2300000, $product->fresh()->price_50ml);
    }
}
