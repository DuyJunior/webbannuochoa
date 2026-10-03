<?php

namespace Tests\Feature;

use App\Mail\BackInStockMail;
use App\Models\Perfume;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StoreAuditFollowupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Mail::fake();
    }

    private function product(): Perfume
    {
        return Perfume::create(['name' => 'Dior Sauvage', 'slug' => 'audit-dior', 'brand' => 'Dior', 'gender' => 'nam',
            'volume_ml' => 100, 'price' => 3780000, 'weight' => 200, 'stock' => 0, 'stock_10ml' => 0,
            'stock_50ml' => 0, 'stock_5ml' => 5, 'is_active' => true]);
    }

    private function variant(): array
    {
        return ['volume_ml' => 200, 'price' => 5200000, 'stock' => 7, 'weight' => 450, 'is_active' => true];
    }

    public function test_wishlist_shows_available_200ml_with_its_price_instead_of_sold_out_primary(): void
    {
        $product = $this->product();
        $product->variants()->create($this->variant());
        $this->actingAs(User::factory()->create())->post(route('store.wishlist.toggle', $product))->assertRedirect();
        $this->get(route('store.wishlist'))->assertOk()->assertSee('Có sẵn · 200 ml')->assertSee('5.200.000₫')
            ->assertSee('data-quick-view=', false)->assertDontSee('Báo tôi khi có hàng');
        $this->post(route('store.stock-alert', $product))->assertSessionHas('success', 'Sản phẩm đang có hàng, bạn có thể đặt ngay.');
        $this->assertDatabaseCount('stock_alerts', 0);
    }

    public function test_hidden_extra_size_is_not_advertised_as_available(): void
    {
        $product = $this->product();
        $product->variants()->create([...$this->variant(), 'is_active' => false]);
        $this->actingAs(User::factory()->create())->post(route('store.wishlist.toggle', $product));
        $this->get(route('store.wishlist'))->assertOk()->assertSee('Tạm hết các dung tích')
            ->assertSee('Báo tôi khi có hàng')->assertDontSee('5.200.000₫');
        $this->post(route('store.stock-alert', $product))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('stock_alerts', 1);
    }

    public function test_adding_200ml_stock_sends_one_restock_notice_with_available_volume(): void
    {
        $product = $this->product();
        $customer = User::factory()->create();
        $this->actingAs($customer)->post(route('store.stock-alert', $product));
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $payload = [...$product->only(['name', 'brand', 'gender', 'volume_ml', 'price', 'stock', 'stock_5ml', 'stock_10ml', 'stock_50ml', 'weight', 'is_active']), 'variants' => [$this->variant()]];
        $this->put(route('admin.products.update', $product), $payload)->assertSessionHasNoErrors();
        Mail::assertSent(BackInStockMail::class, fn ($mail) => $mail->hasTo($customer->email) && $mail->availableVolumes === [200]);
        $this->assertNotNull(DB::table('stock_alerts')->value('notified_at'));
        $variant = $product->variants()->sole();
        $payload['variants'][0]['id'] = $variant->id;
        $this->put(route('admin.products.update', $product), $payload)->assertSessionHasNoErrors();
        Mail::assertSentCount(1);
    }

    public function test_legacy_product_url_uses_current_storefront_and_hides_inactive_products(): void
    {
        $product = $this->product();
        $this->actingAs(User::factory()->create())->get(route('products.show', $product))->assertRedirect(route('perfumes.show', $product));
        $product->update(['is_active' => false]);
        $this->get(route('products.show', $product))->assertNotFound()->assertDontSee('Dior Sauvage');
    }

    public function test_expired_admin_form_renders_recovery_in_vietnamese_without_processing_request(): void
    {
        Route::post('/admin/audit-expired-form', fn () => throw new TokenMismatchException);
        $this->post('/admin/audit-expired-form')->assertStatus(419)->assertSee('Mình kết nối lại nhé.')
            ->assertSee('Yêu cầu vừa gửi chưa được xử lý.')->assertSee(route('admin.login'))->assertSee('noindex, nofollow');
        $this->assertDatabaseCount('perfumes', 0);
    }

    public function test_expired_store_request_keeps_419_and_json_contract(): void
    {
        Route::post('/audit-expired-form', fn () => throw new TokenMismatchException);
        $this->post('/audit-expired-form')->assertStatus(419)->assertSee(route('login'))->assertSee('Đăng nhập lại');
        $this->postJson('/audit-expired-form')->assertStatus(419)->assertJsonStructure(['message']);
    }
}
