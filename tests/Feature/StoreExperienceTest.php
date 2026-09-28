<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Services\LoyaltyService;
use App\Services\StockAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StoreExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function perfume(array $attributes = []): Perfume
    {
        return Perfume::create(array_merge([
            'name' => 'Hoa hồng tháng Năm', 'slug' => 'hoa-hong-thang-nam',
            'brand' => 'Ha Thu', 'gender' => 'nu', 'concentration' => 'EDP',
            'volume_ml' => 100, 'price' => 1200000, 'stock' => 3,
            'description' => 'Hương hoa hồng dịu dàng dành cho buổi hẹn hò.',
            'is_active' => true,
        ], $attributes));
    }

    public function test_finder_and_comparison_use_existing_products(): void
    {
        $perfume = $this->perfume();
        $this->get(route('store.finder', ['style' => 'hoa', 'occasion' => 'hen-ho', 'gender' => 'nu']))
            ->assertOk()->assertSee($perfume->name);
        $this->get(route('store.compare', ['ids' => (string) $perfume->id]))
            ->assertOk()->assertSee($perfume->name);
        $this->get(route('home', ['note' => 'hoa hồng']))->assertOk()->assertSee($perfume->name);
    }

    public function test_customer_can_save_review_and_request_stock_alert(): void
    {
        $user = User::factory()->create();
        $perfume = $this->perfume(['stock' => 0]);
        $this->actingAs($user)->post(route('store.wishlist.toggle', $perfume))->assertRedirect();
        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'perfume_id' => $perfume->id]);
        $purchase = Order::create(['user_id' => $user->id, 'customer_name' => 'Khách',
            'phone' => '0900000000', 'address' => 'Hà Nội', 'total_price' => 1200000, 'status' => 'completed']);
        $purchase->items()->create(['perfume_id' => $perfume->id, 'quantity' => 1, 'price' => 1200000]);
        $this->actingAs($user)->post(route('store.review', $perfume), [
            'rating' => 5, 'body' => 'Mùi hoa hồng nhẹ nhàng và dễ dùng mỗi ngày.',
        ])->assertRedirect();
        $this->assertDatabaseHas('perfume_reviews', ['user_id' => $user->id, 'perfume_id' => $perfume->id, 'rating' => 5]);
        $this->actingAs($user)->post(route('store.stock-alert', $perfume))->assertRedirect();
        $this->assertDatabaseHas('stock_alerts', ['user_id' => $user->id, 'perfume_id' => $perfume->id]);

        Mail::fake();
        $perfume->update(['stock' => 5]);
        StockAlertService::notifyIfRestocked($perfume, 0);
        Mail::assertSentCount(1);
        $this->assertNotNull(DB::table('stock_alerts')->value('notified_at'));
    }

    public function test_coupon_and_points_have_server_side_limits(): void
    {
        $user = User::factory()->create();
        Order::create(['user_id' => $user->id, 'customer_name' => 'Khách hàng',
            'phone' => '0900000000', 'address' => 'Hà Nội', 'total_price' => 250000,
            'status' => 'completed']);
        $this->assertSame(2, LoyaltyService::balance($user->id));
        $this->actingAs($user)->get(route('store.member'))->assertOk()->assertSee('Điểm thành viên');

        $coupon = Coupon::create(['code' => 'HATHU10', 'type' => 'percent', 'value' => 10,
            'minimum_order' => 500000, 'is_active' => true]);
        $this->assertFalse($coupon->isAvailableFor(400000));
        $this->assertTrue($coupon->isAvailableFor(1200000));
        $this->assertSame(120000, $coupon->discountFor(1200000));
    }

    public function test_admin_can_create_and_disable_coupon(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.coupons.index'))
            ->assertOk()->assertSee('Mã ưu đãi');
        $this->actingAs($admin)->post(route('admin.coupons.store'), [
            'code' => 'haThu50', 'type' => 'fixed', 'value' => 50000,
            'minimum_order' => 500000,
        ])->assertRedirect();
        $coupon = Coupon::where('code', 'HATHU50')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.coupons.toggle', $coupon))->assertRedirect();
        $this->assertFalse($coupon->fresh()->is_active);
    }
}
