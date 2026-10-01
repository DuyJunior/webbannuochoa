<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Services\CartQuoteService;
use App\Services\DiscoveryBoxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscoveryBoxAvailabilityTest extends TestCase
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
            'name' => 'Sample '.uniqid(), 'slug' => 'sample-'.uniqid(), 'brand' => 'Soopi',
            'gender' => 'unisex', 'volume_ml' => 100, 'price' => 1000000,
            'stock' => 10, 'stock_5ml' => 3, 'is_active' => true,
        ], $extra));
    }

    public function test_builder_preserves_pick_order_and_keeps_unavailable_products_visible(): void
    {
        $first = $this->product(['brand' => 'A']);
        $second = $this->product(['brand' => 'B']);
        $soldOut = $this->product(['stock_5ml' => 0]);
        $native = $this->product(['volume_ml' => 5, 'stock' => 2, 'stock_5ml' => 0]);
        $ids = [$second->id, $native->id, $soldOut->id, $first->id];
        $this->get(route('store.discovery-box', ['size' => 5, 'samples' => $ids]))->assertOk()
            ->assertViewHas('initialSamples', fn ($samples) => $samples->pluck('id')->all() === [$second->id, $native->id, $first->id])
            ->assertViewHas('unavailableSelectionCount', 1)
            ->assertViewHas('perfumes', fn ($products) => $products->contains('id', $soldOut->id))
            ->assertSee('Mẫu 5ml đang hết')
            ->assertSee('Nốt hương đang được đối chiếu.')
            ->assertDontSee('Voucher')
            ->assertDontSee('2ml');
    }

    public function test_failed_post_selection_takes_priority_over_homepage_query(): void
    {
        $products = collect([$this->product(), $this->product(), $this->product()]);
        $ordered = $products->reverse()->pluck('id')->values()->all();
        $this->withSession(['_old_input' => ['size' => 3, 'perfume_ids' => $ordered]])
            ->get(route('store.discovery-box', ['size' => 5, 'samples' => [$products[0]->id]]))
            ->assertOk()->assertViewHas('initialSize', 3)
            ->assertViewHas('initialSamples', fn ($samples) => $samples->pluck('id')->all() === $ordered);
    }

    public function test_box_prices_and_order_are_preserved_without_reserving_inventory(): void
    {
        $this->actingAs(User::factory()->create());
        $products = collect([$this->product(), $this->product(), $this->product(), $this->product(), $this->product()]);
        foreach ([3 => 199000, 5 => 299000] as $size => $price) {
            $ordered = $products->take($size)->reverse()->pluck('id')->values()->all();
            $this->withSession(['cart' => []])->post(route('cart.add-discovery-box'), ['size' => $size, 'perfume_ids' => $ordered])
                ->assertRedirect(route('cart.index'))->assertSessionHasNoErrors();
            $cart = session('cart');
            $this->assertCount(1, $cart);
            $box = array_values($cart)[0];
            $this->assertSame($ordered, $box['sample_ids']);
            $this->assertSame($ordered[0], $box['perfume_id']);
            $this->assertSame($price, $box['unit_price']);
            $this->assertSame($price, app(CartQuoteService::class)->quote($cart)['total']);
        }
        foreach ($products as $product) {
            $this->assertSame(3, $product->fresh()->stock_5ml);
        }
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_sold_out_sample_rejects_whole_box_and_retains_input_and_existing_cart(): void
    {
        $this->actingAs(User::factory()->create());
        $first = $this->product();
        $soldOut = $this->product(['stock_5ml' => 0]);
        $third = $this->product();
        $ids = [$third->id, $soldOut->id, $first->id];
        $cart = [$first->id => 1];
        $this->withSession(['cart' => $cart])->from(route('store.discovery-box'))
            ->post(route('cart.add-discovery-box'), ['size' => 3, 'perfume_ids' => $ids])
            ->assertRedirect(route('store.discovery-box'))->assertSessionHasErrors('discovery')
            ->assertSessionHas('cart', $cart)->assertSessionHas('_old_input.perfume_ids', $ids);
        $this->assertSame(3, $first->fresh()->stock_5ml);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_existing_boxes_and_native_five_ml_cart_lines_share_available_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $native = $this->product(['volume_ml' => 5, 'stock' => 3, 'stock_5ml' => 0]);
        $second = $this->product();
        $third = $this->product();
        $ids = [$native->id, $second->id, $third->id];
        $cart = [
            'existing-box' => ['is_discovery_box' => true, 'perfume_id' => $native->id, 'sample_ids' => $ids, 'quantity' => 1, 'volume_ml' => 5],
            $native->id => 1,
            'gift-five' => ['perfume_id' => $native->id, 'quantity' => 1, 'volume_ml' => 5, 'has_gift' => true],
        ];
        $this->assertSame(0, DiscoveryBoxService::remaining($native, $cart));
        $this->withSession(['cart' => $cart])->post(route('cart.add-discovery-box'), ['size' => 3, 'perfume_ids' => $ids])
            ->assertSessionHasErrors('discovery')->assertSessionHas('cart', $cart);
        $this->get(route('store.discovery-box'))->assertOk()->assertSee('Đã đủ trong giỏ')
            ->assertViewHas('sampleAvailability', fn ($available) => $available[$native->id] === 0);
        $this->assertSame(3, $native->fresh()->stock);
    }

    public function test_native_five_ml_stock_is_reserved_and_released_in_its_actual_bucket(): void
    {
        $this->actingAs(User::factory()->create());
        $native = $this->product(['volume_ml' => 5, 'stock' => 2, 'stock_5ml' => 0]);
        $second = $this->product();
        $third = $this->product();
        $ids = [$native->id, $second->id, $third->id];
        $this->post(route('cart.add-discovery-box'), ['size' => 3, 'perfume_ids' => $ids])->assertSessionHasNoErrors();
        $this->post(route('cart.checkout'), ['customer_name' => 'Khách', 'phone' => '0912345678', 'address' => 'Hà Nội'])
            ->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertSame(1, $native->fresh()->stock);
        $this->assertSame(0, $native->fresh()->stock_5ml);
        $this->assertSame(2, $second->fresh()->stock_5ml);
        $this->assertSame('stock', $order->items->first()->stock_components[0]['stock_column']);
        $this->post(route('orders.cancel', $order))->assertSessionHas('success');
        $this->post(route('orders.cancel', $order));
        $this->assertSame(2, $native->fresh()->stock);
        $this->assertSame(0, $native->fresh()->stock_5ml);
        $this->assertSame(3, $second->fresh()->stock_5ml);
    }

    public function test_size_mismatch_and_inactive_samples_do_not_change_cart(): void
    {
        $this->actingAs(User::factory()->create());
        $products = collect([$this->product(), $this->product(), $this->product(), $this->product()]);
        $cart = [$products[0]->id => 1];
        $this->withSession(['cart' => $cart])->post(route('cart.add-discovery-box'), ['size' => 3, 'perfume_ids' => $products->pluck('id')->all()])
            ->assertSessionHasErrors('discovery')->assertSessionHas('cart', $cart);
        $products[0]->update(['is_active' => false]);
        $this->post(route('cart.add-discovery-box'), ['size' => 3, 'perfume_ids' => $products->take(3)->pluck('id')->all()])
            ->assertSessionHasErrors('discovery')->assertSessionHas('cart', $cart);
    }
}
