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
        $address = array_replace([
            'countrycode' => 'VN', 'state' => 'Hà Nội', 'county' => 'Quận Ba Đình',
            'district' => 'Phường Điện Biên', 'street' => 'Đường Hoàng Diệu', 'housenumber' => '999',
        ], $properties);
        Http::fake(['geocoder.test/*' => Http::response(['features' => [[
            'properties' => $address,
            'geometry' => ['coordinates' => [105.8525, 21.0288]],
        ]]])]);
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

    public function test_canonical_region_name_wins_over_a_shared_legacy_alias(): void
    {
        $this->signIn();
        $this->provider();
        $ghn = $this->createMock(GHNService::class);
        $ghn->method('getProvinces')->willReturn(['code' => 200, 'data' => [
            ['ProvinceID' => 2002, 'ProvinceName' => 'Hà Nội 02', 'NameExtension' => ['Hà Nội']],
            ['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội', 'NameExtension' => ['Hà Nội']],
        ]]);
        $ghn->expects($this->once())->method('getDistricts')->with(201)->willReturn(['code' => 200, 'data' => []]);
        $ghn->expects($this->never())->method('getWards');
        $this->app->instance(GHNService::class, $ghn);
        $this->lookup()->assertOk()->assertJsonPath('selection', ['province' => '201', 'district' => '', 'ward' => '']);
    }

    public function test_shared_alias_without_a_canonical_match_stays_unselected(): void
    {
        $this->signIn();
        $this->provider();
        $ghn = $this->createMock(GHNService::class);
        $ghn->method('getProvinces')->willReturn(['code' => 200, 'data' => [
            ['ProvinceID' => 2002, 'ProvinceName' => 'Hà Nội 02', 'NameExtension' => ['Hà Nội']],
            ['ProvinceID' => 2003, 'ProvinceName' => 'Hà Nội 03', 'NameExtension' => ['Hà Nội']],
        ]]);
        $ghn->expects($this->never())->method('getDistricts');
        $this->app->instance(GHNService::class, $ghn);
        $this->lookup()->assertOk()->assertJsonPath('selection', ['province' => '', 'district' => '', 'ward' => '']);
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
        $this->lookup()->assertStatus(503)->assertJsonPath('code', 'connection')
            ->assertDontSee('21.0288')->assertDontSee('geocoder.test');
    }

    public function test_map_outage_empty_coverage_and_busy_provider_have_distinct_safe_errors(): void
    {
        $this->signIn();
        $sequence = Http::sequence();
        foreach ([[503, []], [429, []], [200, ['features'=>[]]], [200, ['unexpected'=>'payload']]] as [$status, $payload]) {
            $sequence->push($payload, $status);
        }
        Http::fake(['geocoder.test/*'=>$sequence]);
        foreach ([
            [503, [], 'unavailable'], [429, [], 'busy'],
            [200, ['features'=>[]], 'not_found'], [200, ['unexpected'=>'payload'], 'unavailable'],
        ] as [$status, $payload, $code]) {
            Cache::forget('delivery-location:provider-limit');
            $this->lookup()->assertStatus(503)->assertJsonPath('code', $code)->assertJsonMissingPath('selection');
        }
    }

    public function test_ghn_failure_keeps_the_map_suggestion_without_inventing_region_ids(): void
    {
        $this->signIn();
        $this->provider();
        $ghn = $this->createMock(GHNService::class);
        $ghn->method('getProvinces')->willThrowException(new \RuntimeException('upstream unavailable'));
        $this->app->instance(GHNService::class, $ghn);
        $this->lookup()->assertOk()->assertJsonPath('street', 'Đường Hoàng Diệu')->assertJsonPath('matched', false)
            ->assertJsonPath('selection', ['province'=>'', 'district'=>'', 'ward'=>''])
            ->assertDontSee('upstream unavailable');
        Http::assertSentCount(1);
    }

    public function test_distant_result_can_suggest_an_area_but_never_fill_a_nearby_streets_name(): void
    {
        $this->signIn();
        $this->ghn();
        Http::fake(['geocoder.test/*'=>Http::response(['features'=>[[
            'properties'=>['countrycode'=>'VN', 'city'=>'Hà Nội', 'street'=>'Đường ở xa'],
            'geometry'=>['coordinates'=>[105.8575, 21.0338]],
        ]]])]);
        $this->lookup()->assertOk()->assertJsonPath('street', '')->assertJsonPath('label', 'Hà Nội')
            ->assertJsonPath('selection.province', '201')->assertJsonPath('matched', false);
        $this->lookup()->assertOk()->assertJsonPath('street', '');
        Http::assertSentCount(1);
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
