<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Perfume;
use App\Services\GHNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliveryLocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.photon.url' => 'https://geocoder.test']);
    }

    private function signIn(): void
    {
        $this->actingAs(User::factory()->create());
    }

    private function provider(array $properties = []): void
    {
        Http::fake(['geocoder.test/*' => Http::response(['features' => [['properties' => array_replace([
            'countrycode' => 'VN', 'state' => 'Hà Nội', 'county' => 'Quận Ba Đình',
            'district' => 'Phường Điện Biên', 'street' => 'Đường Hoàng Diệu', 'housenumber' => '999',
        ], $properties)]]])]);
    }

    private function ghn(bool $ambiguous = false): void
    {
        $ghn = $this->createMock(GHNService::class);
        $ghn->method('getProvinces')->willReturn(['code' => 200, 'data' => [
            ['ProvinceID' => 201, 'ProvinceName' => 'Thành phố Hà Nội'],
        ]]);
        $districts = [['DistrictID' => 1, 'DistrictName' => 'Quận Ba Đình']];
        if ($ambiguous) $districts[] = ['DistrictID' => 2, 'DistrictName' => 'Ba Đình'];
        $ghn->method('getDistricts')->with(201)->willReturn(['code' => 200, 'data' => $districts]);
        if ($ambiguous) {
            $ghn->expects($this->never())->method('getWards');
        } else {
            $ghn->method('getWards')->with(1)->willReturn(['code' => 200, 'data' => [
                ['WardCode' => '001', 'WardName' => 'Điện Biên'],
            ]]);
        }
        $this->app->instance(GHNService::class, $ghn);
    }

    private function lookup(array $data = [])
    {
        return $this->postJson(route('locations.current-address'), $data ?: ['latitude' => 21.0288, 'longitude' => 105.8525]);
    }

    public function test_lookup_requires_login_and_valid_coordinates_before_contacting_provider(): void
    {
        $this->lookup()->assertUnauthorized();
        $this->signIn();
        $this->lookup(['latitude' => 91, 'longitude' => 181])->assertUnprocessable()->assertJsonValidationErrors(['latitude', 'longitude']);
        $this->lookup(['latitude' => [21], 'longitude' => 'invalid'])->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_suggestion_matches_scoped_ghn_ids_and_does_not_invent_a_house_number(): void
    {
        $this->signIn();
        $this->provider();
        $this->ghn();
        $result = $this->lookup()->assertOk()->assertJsonPath('street', 'Đường Hoàng Diệu')
            ->assertJsonPath('selection', ['province' => '201', 'district' => '1', 'ward' => '001']);
        $this->assertStringContainsString('no-store', $result->headers->get('Cache-Control'));
        $result->assertDontSee('999')->assertJsonMissingPath('latitude')->assertJsonMissingPath('longitude');
        $this->lookup()->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['lat'] === 21.0288 && $request['limit'] === 1);
    }

    public function test_ambiguous_ghn_names_remain_unselected(): void
    {
        $this->signIn();
        $this->provider();
        $this->ghn(true);
        $this->lookup()->assertOk()->assertJsonPath('selection', ['province' => '201', 'district' => '', 'ward' => '']);
    }

    public function test_missing_or_foreign_results_never_supply_a_delivery_address(): void
    {
        $this->signIn();
        $this->provider(['countrycode' => 'US']);
        $this->lookup()->assertStatus(503)->assertJsonMissingPath('selection');
    }

    public function test_network_failure_is_safe_and_does_not_echo_coordinates_or_exception_url(): void
    {
        $this->signIn();
        Http::fake(['*' => Http::failedConnection()]);
        $this->lookup()->assertStatus(503)->assertDontSee('21.0288')->assertDontSee('geocoder.test');
    }

    public function test_provider_backoff_and_per_user_rate_limit(): void
    {
        $this->signIn();
        Cache::put('delivery-location:provider-limit', true, 60);
        for ($attempt = 0; $attempt < 5; $attempt++) $this->lookup()->assertStatus(503);
        $this->lookup()->assertTooManyRequests();
        Http::assertNothingSent();
    }

    public function test_unmatched_province_preserves_address_suggestion_without_guessing_ids(): void
    {
        $this->signIn();
        $this->provider(['state' => 'Tên tỉnh mới', 'city' => '']);
        $ghn = $this->createMock(GHNService::class);
        $ghn->method('getProvinces')->willReturn(['code' => 200, 'data' => [['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội']]]);
        $ghn->expects($this->never())->method('getDistricts');
        $this->app->instance(GHNService::class, $ghn);
        $this->lookup()->assertOk()->assertJsonPath('selection', ['province' => '', 'district' => '', 'ward' => '']);
    }

    public function test_checkout_location_controls_render_in_both_languages(): void
    {
        $this->signIn();
        if (!getenv('SOOPI_LOCATION_EXPORT')) $this->withoutVite();
        $product = Perfume::create([
            'name' => 'Soopi location test', 'slug' => 'location-test', 'brand' => 'Soopi',
            'gender' => 'unisex', 'volume_ml' => 100, 'weight' => 400,
            'price' => 1000000, 'stock' => 10, 'is_active' => true,
        ]);
        foreach (['vi' => 'Dùng vị trí hiện tại', 'en' => 'Use my current location'] as $locale => $label) {
            $response = $this->withSession(['cart' => [$product->id => 1], 'locale' => $locale])
                ->get(route('payment.index'))->assertOk()->assertSee($label)
                ->assertSee('data-location-result hidden', false);
            if (getenv('SOOPI_LOCATION_EXPORT')) {
                file_put_contents(storage_path('app/location-checkout-'.$locale.'.html'), $response->getContent());
            }
        }
        Http::assertNothingSent();
    }
}
