<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Perfume;
use App\Models\User;
use App\Services\GHNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GHNTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Mock GHNService for automated tests
        $mockGhn = $this->createMock(GHNService::class);
        $mockGhn->method('getProvinces')->willReturn([
            'code' => 200,
            'message' => 'Success',
            'data' => [
                ['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội'],
                ['ProvinceID' => 202, 'ProvinceName' => 'TP Hồ Chí Minh'],
            ],
        ]);
        $mockGhn->method('getDistricts')->willReturn([
            'code' => 200,
            'message' => 'Success',
            'data' => [
                ['DistrictID' => 1493, 'DistrictName' => 'Quận Thanh Xuân'],
            ],
        ]);
        $mockGhn->method('getWards')->willReturn([
            'code' => 200,
            'message' => 'Success',
            'data' => [
                ['WardCode' => '1A0706', 'WardName' => 'Phường Nhân Chính'],
            ],
        ]);
        $mockGhn->method('packageParameters')->willReturn([
            'service_type_id' => 2,
            'weight' => 200,
            'length' => 15,
            'width' => 15,
            'height' => 10,
        ]);
        $mockGhn->method('calculateFee')->willReturn([
            'code' => 200,
            'message' => 'Success',
            'data' => [
                'total' => 20900,
            ],
        ]);
        $mockGhn->method('createOrder')->willReturn([
            'code' => 200,
            'message' => 'Success',
            'data' => [
                'order_code' => 'GHNTEST123',
            ],
        ]);
        $mockGhn->method('cancelOrder')->willReturn([
            'code' => 200,
            'message' => 'Success',
        ]);

        $this->app->instance(GHNService::class, $mockGhn);
    }

    private function perfumeData(array $overrides = []): array
    {
        return array_merge([
            'category_id' => null,
            'name' => 'Bleu de Chanel',
            'slug' => 'bleu-de-chanel',
            'brand' => 'Chanel',
            'gender' => 'nam',
            'concentration' => 'EDP',
            'volume_ml' => 100,
            'weight' => 200,
            'price' => 3900000,
            'sale_price' => 3500000,
            'stock' => 12,
            'image_url' => 'https://example.com/bleu-de-chanel.jpg',
            'description' => 'Hương gỗ thơm nam tính và thanh lịch.',
            'is_active' => true,
        ], $overrides);
    }

    public function test_can_fetch_provinces(): void
    {
        $response = $this->getJson(route('locations.provinces'));
        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_fetch_districts(): void
    {
        $response = $this->getJson(route('locations.districts', ['provinceId' => 201]));
        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.0.DistrictName', 'Quận Thanh Xuân');
    }

    public function test_can_fetch_wards(): void
    {
        $response = $this->getJson(route('locations.wards', ['districtId' => 1493]));
        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.0.WardName', 'Phường Nhân Chính');
    }

    public function test_can_calculate_shipping_fee(): void
    {
        $geocoder = $this->createMock(\App\Services\PhotonGeocoder::class);
        $geocoder->expects($this->never())->method('reverse');
        $this->app->instance(\App\Services\PhotonGeocoder::class, $geocoder);
        $response = $this->postJson(route('locations.fee'), [
            'to_district_id' => 1493,
            'to_ward_code' => '1A0706',
        ]);

        $response->assertOk()
            ->assertJsonPath('code', 200)
            ->assertJsonPath('data.total', 20900);
    }

    public function test_ward_directory_requires_login_and_returns_shipping_names_without_map_lookup(): void
    {
        $url = route('locations.ward-directory', ['provinceId' => 201]);
        $this->getJson($url)->assertUnauthorized();
        $geocoder = $this->createMock(\App\Services\PhotonGeocoder::class);
        $geocoder->expects($this->never())->method('reverse');
        $this->app->instance(\App\Services\PhotonGeocoder::class, $geocoder);
        $ghn = $this->createMock(GHNService::class);
        $ghn->expects($this->once())->method('getWardDirectory')->with(201)->willReturn([
            'code' => 200, 'data' => [['DistrictID' => 1482, 'WardCode' => '11007', 'WardName' => 'Phú Diễn']],
        ]);
        $this->app->instance(GHNService::class, $ghn);
        $this->actingAs(User::factory()->create())->getJson($url)->assertOk()
            ->assertJsonPath('data.0.DistrictID', 1482)->assertJsonPath('data.0.WardCode', '11007');
    }

    public function test_user_can_view_payment_page_with_cart(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create($this->perfumeData());

        $response = $this->actingAs($user)
            ->withSession([
                'cart' => [
                    $perfume->id => [
                        'perfume_id' => $perfume->id,
                        'quantity' => 1,
                        'price' => 3500000,
                    ],
                ],
            ])
            ->get(route('payment.index'));

        $response->assertOk()
            ->assertSee('Hoàn tất đơn hàng')
            ->assertSee('Giao Hàng Nhanh (GHN Express)')
            ->assertSee('3.500.000');
    }

    public function test_user_can_process_order_with_ghn(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create($this->perfumeData());

        $response = $this->actingAs($user)
            ->withSession([
                'cart' => [
                    $perfume->id => [
                        'perfume_id' => $perfume->id,
                        'quantity' => 1,
                        'price' => 3500000,
                    ],
                ],
            ])
            ->post(route('payment.process'), [
                'name' => 'Nguyễn Thị Thu Hà',
                'phone' => '0912345678',
                'address' => 'Số 123 Nguyễn Trãi',
                'to_district_id' => 1493,
                'to_ward_code' => '1A0706',
            ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals('GHNTEST123', $order->ghn_order_code);
        $this->assertEquals('ready_to_pick', $order->shipping_status);
        $this->assertEquals(20900, $order->ghn_total_fee);

        $response->assertRedirect(route('orders.show', $order->id));
    }

    public function test_payment_rejects_phone_number_not_strictly_10_digits(): void
    {
        $user = User::factory()->create();
        $perfume = Perfume::create($this->perfumeData());

        $invalidPhones = ['091234567', '09123456789', '1234567890', '091234abcd'];

        foreach ($invalidPhones as $invalidPhone) {
            $response = $this->actingAs($user)
                ->withSession([
                    'cart' => [
                        $perfume->id => [
                            'perfume_id' => $perfume->id,
                            'quantity' => 1,
                            'price' => 3500000,
                        ],
                    ],
                ])
                ->post(route('payment.process'), [
                    'name' => 'Nguyễn Thị Thu Hà',
                    'phone' => $invalidPhone,
                    'address' => 'Số 123 Nguyễn Trãi',
                    'to_district_id' => 1493,
                    'to_ward_code' => '1A0706',
                ]);

            $response->assertSessionHasErrors(['phone']);
        }
    }

    public function test_user_can_view_order_history_and_details(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'name' => 'Nguyễn Thị Thu Hà',
            'phone' => '0912345678',
            'address' => 'Số 123 Nguyễn Trãi',
            'total_price' => 520900,
            'status' => 'pending',
            'shipping_status' => 'ready_to_pick',
            'ghn_order_code' => 'GHNTEST123',
            'ghn_total_fee' => 20900,
            'to_district_id' => 1493,
            'to_ward_code' => '1A0706',
        ]);

        $responseHistory = $this->actingAs($user)->get(route('orders.index'));
        $responseHistory->assertOk()
            ->assertSee('GHNTEST123')
            ->assertSee('Tuyển chọn của bạn')
            ->assertSee(route('orders.show', $order->id));

        $responseShow = $this->actingAs($user)->get(route('orders.show', $order->id));
        $responseShow->assertOk()
            ->assertSee('GHNTEST123')
            ->assertSee('Chi tiết đơn hàng');
    }

    public function test_user_can_cancel_order(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'name' => 'Nguyễn Thị Thu Hà',
            'phone' => '0912345678',
            'address' => 'Số 123 Nguyễn Trãi',
            'total_price' => 520900,
            'status' => 'pending',
            'shipping_status' => 'ready_to_pick',
            'ghn_order_code' => 'GHNTEST123',
            'ghn_total_fee' => 20900,
            'to_district_id' => 1493,
            'to_ward_code' => '1A0706',
        ]);

        $response = $this->actingAs($user)->post(route('orders.cancel', $order->id));
        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals('cancelled', $order->shipping_status);
        $this->assertEquals('cancelled', $order->status);
    }

    public function test_guest_tracking_redirects_to_login(): void
    {
        $response = $this->get(route('orders.tracking'));
        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_search_order_by_code_or_phone(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $order = Order::create([
            'user_id' => $user->id,
            'name' => 'Khách Vãng Lai',
            'phone' => '0988776655',
            'address' => '456 Cầu Giấy, Hà Nội',
            'total_price' => 750000,
            'status' => 'pending',
            'shipping_status' => 'ready_to_pick',
            'ghn_order_code' => 'GHNTRACK999',
            'ghn_total_fee' => 22000,
            'to_district_id' => 1493,
            'to_ward_code' => '1A0706',
        ]);

        // Search by phone
        $responsePhone = $this->post(route('orders.tracking.search'), [
            'phone' => '0988776655',
        ]);
        $responsePhone->assertOk()
            ->assertSee('GHNTRACK999')
            ->assertSee('Khách Vãng Lai');

        // Search by GHN code
        $responseCode = $this->post(route('orders.tracking.search'), [
            'keyword' => 'GHNTRACK999',
        ]);
        $responseCode->assertOk()
            ->assertSee('GHNTRACK999')
            ->assertSee('Khách Vãng Lai');
    }
}
