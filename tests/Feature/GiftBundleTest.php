<?php

namespace Tests\Feature;

use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\Perfume;
use App\Models\User;
use App\Services\CartQuoteService;
use App\Services\CartStockService;
use App\Services\DiscoveryBoxService;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use App\Services\GiftBundleService;
use App\Services\OrderInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GiftBundleTest extends TestCase
{
    use RefreshDatabase;

    private array $quotedWeights = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        Mail::fake();
        Queue::fake();
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
        return Perfume::create(array_replace([
            'name' => 'Parfum '.Str::uuid(), 'slug' => (string) Str::uuid(), 'brand' => 'Soopi',
            'gender' => 'unisex', 'volume_ml' => 100, 'weight' => 400, 'price' => 1000000,
            'sale_price' => 800000, 'stock' => 10, 'stock_5ml' => 4, 'is_active' => true,
        ], $extra));
    }

    private function products(array $main = [], array $first = [], array $second = []): array
    {
        return [$this->product($main), $this->product($first), $this->product($second)];
    }

    private function addBundle(Perfume $main, Perfume $first, Perfume $second, int $quantity = 1): string
    {
        $this->post(route('cart.add-gift-bundle', $main), [
            'quantity' => $quantity, 'sample_ids' => [$second->id, $first->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        return collect(session('cart'))->keys()->first(fn ($key) => str_starts_with((string) $key, 'gift_bundle_'));
    }

    private function address(array $extra = []): array
    {
        return array_replace(['name' => 'Khách thử combo', 'phone' => '0912345678', 'address' => 'Hà Nội',
            'to_district_id' => 1493, 'to_ward_code' => 'TEST', 'payment_method' => 'cod'], $extra);
    }

    private function legacyAddress(): array
    {
        return ['customer_name' => 'Khách thử combo', 'phone' => '0912345678', 'address' => 'Hà Nội'];
    }

    public function test_guest_cannot_add_bundle_without_authentication(): void
    {
        auth()->logout();
        [$main, $first, $second] = $this->products();
        $this->post(route('cart.add-gift-bundle', $main), ['quantity' => 1, 'sample_ids' => [$first->id, $second->id]])
            ->assertRedirect(route('login'));
        $this->assertEmpty(session('cart', []));
    }

    public function test_bundle_has_exact_catalog_price_and_real_components_without_engraving(): void
    {
        [$main, $first, $second] = $this->products();
        $key = $this->addBundle($main, $first, $second, 2);
        $line = session('cart')[$key];
        $this->assertTrue($line['is_gift_bundle']);
        $this->assertTrue($line['has_gift']);
        $this->assertFalse($line['has_engrave']);
        $this->assertNull($line['engrave_text']);
        $this->assertSame([$first->id, $second->id], $line['sample_ids']);
        $this->assertSame(890000, $line['unit_price']);
        $quote = app(CartQuoteService::class)->quote(session('cart'));
        $this->assertSame(1780000, $quote['total']);
        $this->assertSame(1000, $quote['weight']);
        $this->assertSame(['main', 'sample', 'sample'], array_column($quote['items'][0]['stock_components'], 'role'));
        $this->assertSame([$main->id, $first->id, $second->id], array_column($quote['items'][0]['stock_components'], 'perfume_id'));
        $this->assertSame([100, 5, 5], array_column($quote['items'][0]['stock_components'], 'volume_ml'));
        $this->assertSame([$first->name, $second->name], $quote['items'][0]['sample_names']);
        $this->assertSame(10, $main->fresh()->stock);
        $this->assertSame(4, $first->fresh()->stock_5ml);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_reversed_sample_order_merges_same_bundle_and_current_catalog_price_wins(): void
    {
        [$main, $first, $second] = $this->products();
        $key = $this->addBundle($main, $first, $second);
        $this->post(route('cart.add-gift-bundle', $main), ['quantity' => 1, 'sample_ids' => [$first->id, $second->id],
            'unit_price' => 1, 'price' => 1, 'addon_gift' => 0, 'engrave_text' => 'do not trust'])
            ->assertSessionHasNoErrors();
        $this->assertCount(1, session('cart'));
        $this->assertSame(2, session('cart')[$key]['quantity']);
        $main->update(['sale_price' => null, 'price' => 1200000]);
        $cart = session('cart');
        $cart[$key]['unit_price'] = 1;
        $cart[$key]['has_gift'] = false;
        $cart[$key]['engrave_text'] = 'forged inscription';
        $quote = app(CartQuoteService::class)->quote($cart);
        $this->assertSame(2580000, $quote['total']);
        $this->assertTrue($quote['items'][0]['addon_gift']);
        $this->assertNull($quote['items'][0]['engrave_text']);
    }

    public function test_missing_duplicate_nested_and_main_product_samples_never_change_cart(): void
    {
        [$main, $first, $second] = $this->products();
        $cart = [$main->id => 1];
        foreach ([null, [], [$first->id], [$first->id, $second->id, $main->id], [$first->id, $first->id],
            [$main->id, $first->id], [[$first->id], $second->id], ['invalid', $second->id], [0, $second->id],
            ['first' => $first->id, 'second' => $second->id]] as $ids) {
            $this->withSession(['cart' => $cart])->postJson(route('cart.add-gift-bundle', $main), [
                'quantity' => 1, 'sample_ids' => $ids,
            ])->assertUnprocessable()->assertSessionHas('cart', $cart);
        }
        $this->assertSame(10, $main->fresh()->stock);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_invalid_quantity_is_rejected_before_changing_cart(): void
    {
        [$main, $first, $second] = $this->products();
        foreach ([null, 0, -1, 1000, 1.5, 'invalid', [1]] as $quantity) {
            $this->withSession(['cart' => []])->postJson(route('cart.add-gift-bundle', $main), [
                'quantity' => $quantity, 'sample_ids' => [$first->id, $second->id],
            ])->assertUnprocessable();
            $this->assertEmpty(session('cart', []));
        }
    }

    public function test_nested_sample_input_redirects_to_a_renderable_product_page_with_validation_errors(): void
    {
        [$main, $first, $second] = $this->products();
        $url = route('perfumes.show', $main);
        $cart = [$main->id => 1];
        foreach ([0 => [[$first->id], $second->id], 1 => [$first->id, [$second->id]]] as $slot => $ids) {
            $errorKey = 'sample_ids.'.$slot;
            $this->withSession(['cart' => $cart])->from($url)
                ->post(route('cart.add-gift-bundle', $main), ['quantity' => 1, 'sample_ids' => $ids])
                ->assertRedirect($url)->assertSessionHasErrors($errorKey)
                ->assertSessionHas('_old_input.sample_ids', $ids);
            $message = session('errors')->first($errorKey);

            $this->get($url)->assertOk()->assertSee($message)
                ->assertSee('gift-bundle-error', false);
            $this->assertSame($cart, session('cart'));
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_unavailable_deleted_and_inactive_samples_reject_entire_bundle(): void
    {
        [$main, $first, $second] = $this->products();
        $cart = [$main->id => 1];
        foreach ([['stock_5ml' => 0], ['stock_5ml' => 4, 'is_active' => false], ['is_active' => true, 'deleted_at' => now()]] as $change) {
            $first->forceFill($change)->save();
            $this->withSession(['cart' => $cart])->postJson(route('cart.add-gift-bundle', $main), [
                'quantity' => 1, 'sample_ids' => [$first->id, $second->id],
            ])->assertUnprocessable()->assertSessionHas('cart', $cart);
        }
        $this->assertSame(10, $main->fresh()->stock);
        $this->assertSame(4, $second->fresh()->stock_5ml);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_unavailable_main_bottle_cannot_be_bought_as_bundle(): void
    {
        [$main, $first, $second] = $this->products(['stock' => 0]);
        foreach ([['stock' => 0], ['stock' => 4, 'is_active' => false]] as $change) {
            $main->update($change);
            $this->withSession(['cart' => []])->postJson(route('cart.add-gift-bundle', $main), [
                'quantity' => 1, 'sample_ids' => [$first->id, $second->id],
            ])->assertUnprocessable();
            $this->assertEmpty(session('cart', []));
        }
    }

    public function test_bundle_selection_accounts_for_discovery_boxes_and_regular_native_samples(): void
    {
        [$main, $native, $second] = $this->products([], ['volume_ml' => 5, 'stock' => 2, 'stock_5ml' => 0]);
        $third = $this->product();
        $cart = [$native->id => 1, 'box' => ['is_discovery_box' => true, 'perfume_id' => $native->id,
            'sample_ids' => [$native->id, $second->id, $third->id], 'quantity' => 1, 'volume_ml' => 5]];
        $this->assertSame(0, CartStockService::remaining($native, 5, $cart));
        $choices = app(GiftBundleService::class)->availableSamples($main, $cart);
        $this->assertFalse($choices->contains('id', $main->id));
        $this->assertFalse($choices->contains('id', $native->id));
        $this->withSession(['cart' => $cart])->postJson(route('cart.add-gift-bundle', $main), [
            'quantity' => 1, 'sample_ids' => [$native->id, $second->id],
        ])->assertUnprocessable()->assertSessionHas('cart', $cart);
    }

    public function test_bundle_and_other_lines_share_main_and_sample_limits_when_updating(): void
    {
        [$main, $first, $second] = $this->products(['stock' => 4], ['stock_5ml' => 3]);
        $key = $this->addBundle($main, $first, $second);
        $cart = session('cart');
        $cart[$main->id] = 2;
        $cart['box'] = ['is_discovery_box' => true, 'perfume_id' => $first->id,
            'sample_ids' => [$first->id, $second->id, $this->product()->id], 'quantity' => 1, 'volume_ml' => 5];
        $this->withSession(['cart' => $cart])->patch(route('cart.update', $key), ['quantity' => 3])
            ->assertSessionHasErrors('quantity')->assertSessionHas('cart', $cart);
        $this->patch(route('cart.update', $key), ['quantity' => 2])->assertSessionHas('success');
        $this->assertSame(2, session('cart')[$key]['quantity']);
        $this->post(route('cart.add', $main), ['quantity' => 1])->assertSessionHasErrors('quantity');
        $this->assertSame(0, DiscoveryBoxService::remaining($first, session('cart')));
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_native_five_ml_main_bottle_uses_same_bucket_as_other_sample_allocations(): void
    {
        [$main, $first, $second] = $this->products(['volume_ml' => 5, 'stock' => 2, 'stock_5ml' => 0]);
        $third = $this->product();
        $cart = ['box' => ['is_discovery_box' => true, 'perfume_id' => $main->id,
            'sample_ids' => [$main->id, $first->id, $third->id], 'quantity' => 1, 'volume_ml' => 5]];
        $this->withSession(['cart' => $cart]);
        $key = $this->addBundle($main, $first, $second);
        $this->patch(route('cart.update', $key), ['quantity' => 2])->assertSessionHasErrors('quantity');
        $this->assertSame(0, DiscoveryBoxService::remaining($main, session('cart')));
        $this->assertSame(2, $main->fresh()->stock);
    }

    public function test_legacy_fake_combo_markers_are_rejected_instead_of_selling_a_wrong_bundle(): void
    {
        $main = $this->product();
        $this->post(route('cart.add', $main), ['quantity' => 1, 'addon_gift' => 1,
            'engrave_text' => 'Combo Trọn Vẹn + 2 Sample'])->assertSessionHasErrors();
        $this->assertEmpty(session('cart', []));
        $cart = ['legacy' => ['perfume_id' => $main->id, 'quantity' => 1, 'volume_ml' => 100,
            'has_gift' => true, 'engrave_text' => 'Combo Trọn Vẹn + 2 Sample', 'unit_price' => 850000]];
        $this->withSession(['cart' => $cart])->postJson(route('cart.checkout'), $this->legacyAddress())
            ->assertUnprocessable()->assertSessionHas('cart', $cart);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_reserves_all_components_and_cancel_restores_them_once(): void
    {
        [$main, $first, $native] = $this->products([], [], ['volume_ml' => 5, 'stock' => 5, 'stock_5ml' => 0]);
        $this->addBundle($main, $first, $native, 2);
        $this->post(route('cart.checkout'), $this->legacyAddress())->assertSessionHasNoErrors()->assertSessionMissing('cart');
        $order = Order::firstOrFail();
        $item = $order->items->first();
        $this->assertEquals(1780000, $order->total_price);
        $this->assertEquals(890000, $item->price);
        $this->assertTrue($item->is_gift_bundle);
        $this->assertFalse($item->is_discovery_box);
        $this->assertNull($item->engrave_text);
        $this->assertSame(['stock', 'stock_5ml', 'stock'], array_column($item->stock_components, 'stock_column'));
        $this->assertSame(8, $main->fresh()->stock);
        $this->assertSame(2, $first->fresh()->stock_5ml);
        $this->assertSame(3, $native->fresh()->stock);
        $this->assertSame(0, $native->fresh()->stock_5ml);
        app(OrderInventoryService::class)->reserve($order);
        $this->assertDatabaseCount('inventory_movements', 3);
        $this->post(route('orders.cancel', $order))->assertSessionHas('success');
        $this->post(route('orders.cancel', $order));
        app(OrderInventoryService::class)->release($order);
        $this->assertSame(10, $main->fresh()->stock);
        $this->assertSame(4, $first->fresh()->stock_5ml);
        $this->assertSame(5, $native->fresh()->stock);
        $this->assertSame(0, $native->fresh()->stock_5ml);
        $this->assertDatabaseCount('inventory_movements', 6);
    }

    public function test_combined_stock_shortage_rolls_back_every_component_and_keeps_cart(): void
    {
        [$main, $first, $second] = $this->products();
        $key = $this->addBundle($main, $first, $second, 2);
        $cart = session('cart');
        $cart['another-box'] = ['is_discovery_box' => true, 'perfume_id' => $first->id,
            'sample_ids' => [$first->id, $second->id, $this->product()->id], 'quantity' => 1, 'volume_ml' => 5];
        $first->update(['stock_5ml' => 2]);
        $this->withSession(['cart' => $cart])->postJson(route('cart.checkout'), $this->legacyAddress())
            ->assertUnprocessable()->assertSessionHas('cart', $cart);
        $this->assertSame(2, session('cart')[$key]['quantity']);
        $this->assertSame(10, $main->fresh()->stock);
        $this->assertSame(2, $first->fresh()->stock_5ml);
        $this->assertSame(4, $second->fresh()->stock_5ml);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('order_emails', 0);
    }

    public function test_selected_checkout_uses_bundle_weight_price_and_keeps_unselected_cart(): void
    {
        config(['demo.enabled' => true]);
        [$main, $first, $second] = $this->products();
        $key = $this->addBundle($main, $first, $second, 2);
        $other = $this->product(['weight' => 9000]);
        $cart = session('cart');
        $cart[$other->id] = 1;
        $this->withSession(['cart' => $cart])->get(route('payment.index', ['selection' => 1, 'selected_items' => [$key]]))
            ->assertOk()->assertViewHas('totalWeight', 1000)->assertViewHas('totalPrice', 1780000)
            ->assertSee('Combo Trọn Vẹn')->assertSee($first->name)->assertSee($second->name)
            ->assertDontSee('Hộp thử mùi · 3 mẫu')->assertDontSee($other->name);
        $payload = $this->address(['selection' => 1, 'selected_items' => [$key], 'checkout_key' => (string) Str::uuid()]);
        $this->post(route('payment.process'), $payload)->assertSessionHasNoErrors()
            ->assertSessionHas('cart', [$other->id => 1]);
        $order = Order::firstOrFail();
        $this->assertEquals(1800900, $order->total_price);
        $this->assertTrue($order->items->first()->is_gift_bundle);
        $this->assertSame([1000], $this->quotedWeights);
        $this->assertSame(10, $other->fresh()->stock);
        $this->post(route('payment.process'), $payload)->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(8, $main->fresh()->stock);
    }

    public function test_reservation_rolls_back_earlier_component_writes_if_a_later_component_is_short(): void
    {
        [$main, $first, $second] = $this->products();
        $key = $this->addBundle($main, $first, $second, 2);
        $quote = app(CartQuoteService::class)->quote(session('cart'));
        $order = Order::create(['user_id' => auth()->id(), 'customer_name' => 'Khách', 'phone' => '0912345678',
            'address' => 'Hà Nội', 'total_price' => $quote['total'], 'status' => 'pending', 'inventory_status' => 'unreserved']);
        $order->items()->create($quote['items'][0]);
        // Another order consumes one sample between quoting and the inventory transaction.
        $second->update(['stock_5ml' => 1]);
        try {
            app(OrderInventoryService::class)->reserve($order);
            $this->fail('Reservation must reject the unavailable sample.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('cart', $exception->errors());
        }
        $this->assertSame(10, $main->fresh()->stock);
        $this->assertSame(4, $first->fresh()->stock_5ml);
        $this->assertSame(1, $second->fresh()->stock_5ml);
        $this->assertSame('unreserved', $order->fresh()->inventory_status);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertSame(2, session('cart')[$key]['quantity']);
    }

    public function test_shipping_quote_and_dispatch_preserve_complete_bundle_weight(): void
    {
        [$main, $first, $second] = $this->products(['weight' => 700]);
        $key = $this->addBundle($main, $first, $second, 2);
        $this->postJson(route('locations.fee'), [
            'to_district_id' => 1493, 'to_ward_code' => 'TEST', 'selection' => 1, 'selected_items' => [$key],
        ])->assertOk()->assertJsonPath('data.total', 20900);
        $this->assertSame([1600], $this->quotedWeights);
        $this->post(route('cart.checkout'), $this->legacyAddress())->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $main->update(['weight' => 9999]);
        $ghn = $this->createMock(GHNService::class);
        $ghn->expects($this->once())->method('packageParameters')->with(1600)
            ->willReturn(['weight' => 1600, 'length' => 15, 'width' => 15, 'height' => 10]);
        $ghn->expects($this->once())->method('createOrder')->with($this->callback(fn ($payload) =>
            $payload['weight'] === 1600 && $payload['items'][0]['weight'] === 800
            && $payload['items'][0]['quantity'] === 2 && str_contains($payload['items'][0]['name'], 'Combo Trọn Vẹn')
        ))->willReturn(['code' => 200]);
        $this->assertSame(['code' => 200], (new GHNOrderService($ghn))->create($order));
    }

    public function test_cart_order_and_email_show_bundle_sample_names_without_discovery_mislabel(): void
    {
        [$main, $first, $second] = $this->products(['name' => 'Chai chính'], ['name' => 'Mẫu hoa nhài'], ['name' => 'Mẫu gỗ ấm']);
        $this->addBundle($main, $first, $second);
        $this->get(route('cart.index'))->assertOk()->assertSee('Combo Trọn Vẹn')->assertSee($first->name)->assertSee($second->name)
            ->assertDontSee('Hộp thử mùi · 3 mẫu')->assertDontSee('Gói quà Luxury &amp; Thiệp (+50.000₫)', false);
        $this->post(route('cart.checkout'), $this->legacyAddress())->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $item = $order->items->first();
        $this->assertSame('100ml + 2 × 5ml', $item->volume_label);
        $this->assertSame([$first->name, $second->name], $item->sample_names);
        $first->update(['name' => 'Tên mới sau khi đặt']);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Combo Trọn Vẹn')
            ->assertSee('Mẫu hoa nhài')->assertSee('Mẫu gỗ ấm')->assertDontSee('Hộp thử mùi · 3 mẫu');
        $email = OrderEmail::where('order_id', $order->id)->where('type', 'placed')->firstOrFail();
        $this->assertSame('Combo Trọn Vẹn · Chai chính', $email->details['items'][0]['name']);
        $this->assertSame(['Mẫu hoa nhài', 'Mẫu gỗ ấm'], $email->details['items'][0]['sample_names']);
        $mail = new OrderStatusMail($email->details, $email->id);
        $mail->assertSeeInHtml('Combo Trọn Vẹn')->assertSeeInHtml('Mẫu hoa nhài')->assertSeeInHtml('Mẫu gỗ ấm')
            ->assertSeeInText('Combo Trọn Vẹn')->assertSeeInText('Mẫu hoa nhài')->assertSeeInText('Mẫu gỗ ấm');
        Mail::assertNothingSent();
    }
}
