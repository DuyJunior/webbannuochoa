<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Services\GHNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutPromotionTest extends TestCase
{
    use RefreshDatabase;
    use \Tests\Concerns\SePayRequests;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->configureSePay();
        $ghn = $this->createMock(GHNService::class);
        $ghn->method('packageParameters')->willReturn(['service_type_id' => 2, 'weight' => 200, 'length' => 15, 'width' => 15, 'height' => 10]);
        $ghn->method('calculateFee')->willReturn(['code' => 200, 'data' => ['total' => 20900]]);
        $this->app->instance(GHNService::class, $ghn);
    }

    public function test_coupon_and_loyalty_points_reduce_order_total_on_server(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create(['name' => 'Hương hồng', 'slug' => 'huong-hong', 'brand' => 'Ha Thu',
            'gender' => 'nu', 'volume_ml' => 100, 'price' => 1200000, 'stock' => 5, 'is_active' => true]);
        Order::create(['user_id' => $user->id, 'customer_name' => 'Khách', 'phone' => '0900000000',
            'address' => 'Hà Nội', 'total_price' => 250000, 'status' => 'completed']);
        Coupon::create(['code' => 'HATHU10', 'type' => 'percent', 'value' => 10,
            'minimum_order' => 500000, 'is_active' => true]);

        $this->actingAs($user)->withSession(['cart' => [$perfume->id => [
            'perfume_id' => $perfume->id, 'quantity' => 1, 'price' => 1200000,
        ]]])->post(route('payment.process'), [
            'name' => 'Khách', 'phone' => '0912345678', 'address' => 'Hà Nội',
            'to_district_id' => 1493, 'to_ward_code' => '1A0706', 'payment_method' => 'sepay',
            'coupon_code' => 'hathu10', 'points_used' => 2,
        ])->assertRedirect();

        $order = Order::latest('id')->first();
        $this->assertSame('HATHU10', $order->coupon_code);
        $this->assertSame(120000, $order->discount_amount);
        $this->assertSame(2, $order->points_used);
        $this->assertEquals(1098900, $order->total_price);
    }

    public function test_checkout_rejects_invalid_coupon_and_unearned_points(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create(['name' => 'Hương hoa', 'slug' => 'huong-hoa',
            'brand' => 'Ha Thu', 'gender' => 'nu', 'volume_ml' => 100,
            'price' => 1200000, 'stock' => 5, 'is_active' => true]);
        $cart = [$perfume->id => ['perfume_id' => $perfume->id, 'quantity' => 1, 'price' => 1200000]];
        $details = ['name' => 'Khách', 'phone' => '0912345678', 'address' => 'Hà Nội',
            'to_district_id' => 1493, 'to_ward_code' => '1A0706', 'payment_method' => 'sepay'];
        $this->actingAs($user)->withSession(['cart' => $cart])
            ->post(route('payment.process'), $details + ['coupon_code' => 'KHONGTONTAI'])
            ->assertSessionHasErrors('coupon_code');
        $this->actingAs($user)->withSession(['cart' => $cart])
            ->post(route('payment.process'), $details + ['points_used' => 10])
            ->assertSessionHasErrors('points_used');
        $this->assertDatabaseCount('orders', 0);
    }
}
