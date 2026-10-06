<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Perfume;
use App\Services\GHNService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class DeliveryLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_location_assistant_renders_google_preview_and_autofill(): void
    {
        $html = view('user.payment.partials.location-assistant')->render();

        $this->assertStringContainsString('data-locate', $html);
        $this->assertStringContainsString('Điền địa chỉ bằng vị trí của bạn.', $html);
        $this->assertStringContainsString('data-location-step="applying"', $html);
        $this->assertStringContainsString('không theo dõi vị trí liên tục', $html);
        $this->assertStringNotContainsString('data-location-map', $html);
        $this->assertStringContainsString('data-delivery-google-map', $html);
        $this->assertStringContainsString('https://maps.google.com/maps?q=Hanoi%2CVietnam', $html);
        $this->assertStringContainsString('Chưa định vị', $html);
        $this->assertStringContainsString('Google để hiển thị bản đồ', $html);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Sleep::fake();
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

    private function ghn(): void
    {
        $ghn = $this->createMock(GHNService::class);
        $ghn->method('getProvinces')->willReturn(['code' => 200, 'data' => [
            ['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội'],
        ]]);
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

    public function test_lookup_returns_address_names_without_shipping_ids_or_a_house_number(): void
    {
        $this->signIn();
        $this->provider();
        $this->ghn();
        $result = $this->lookup()->assertOk()->assertJsonPath('street', 'Đường Hoàng Diệu')
            ->assertJsonPath('regions.province', ['Hà Nội'])
            ->assertJsonPath('regions.district', ['Quận Ba Đình', 'Phường Điện Biên'])
            ->assertJsonPath('regions.ward', ['Phường Điện Biên'])
            ->assertJsonMissingPath('selection')->assertJsonMissingPath('matched');
        $this->assertStringContainsString('no-store', $result->headers->get('Cache-Control'));
        $result->assertDontSee('999')->assertJsonMissingPath('latitude')->assertJsonMissingPath('longitude');
        $this->lookup()->assertOk();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['lat'] === 21.0288 && $request['limit'] === 5);
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
        Http::assertSentCount(2);
    }

    public function test_transient_connection_failure_recovers_without_another_user_click(): void
    {
        $this->signIn();
        $this->ghn();
        Http::fake(['geocoder.test/*' => Http::sequence()->pushFailedConnection()->push(['features' => [[
            'properties' => ['countrycode' => 'VN', 'city' => 'Hà Nội'],
        ]]])]);
        $this->lookup()->assertOk()->assertJsonPath('regions.province', ['Hà Nội']);
        Http::assertSentCount(2);
        $this->lookup()->assertOk();
        Http::assertSentCount(2);
    }

    public function test_transient_server_failure_recovers_but_forbidden_requests_are_not_retried(): void
    {
        $this->signIn();
        $this->ghn();
        Http::fake(['geocoder.test/*' => Http::sequence()->pushStatus(502)->push(['features' => [[
            'properties' => ['countrycode' => 'VN', 'city' => 'Hà Nội'],
        ]]])->pushStatus(403)]);
        $this->lookup()->assertOk();
        Http::assertSentCount(2);
        Cache::forget('delivery-location:provider-limit');
        $this->lookup(['latitude' => 21.03, 'longitude' => 105.85])->assertStatus(503)->assertJsonPath('code', 'unavailable');
        Http::assertSentCount(3);
    }

    public function test_map_outage_empty_coverage_and_busy_provider_have_distinct_safe_errors(): void
    {
        $this->signIn();
        $sequence = Http::sequence();
        foreach ([[503, []], [503, []], [429, []], [200, ['features'=>[]]], [200, ['unexpected'=>'payload']]] as [$status, $payload]) {
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
        Http::assertSentCount(5);
    }

    public function test_deploy_diagnostic_distinguishes_map_outage_from_working_ghn(): void
    {
        $this->ghn();
        Http::fake(['*' => Http::failedConnection('private coordinate-bearing URL')]);
        $this->artisan('delivery-location:check')
            ->expectsOutputToContain('FAIL Map lookup: connection')
            ->expectsOutputToContain('PASS GHN area lookup')
            ->assertFailed();
        Http::assertSentCount(2);
    }

    public function test_map_lookup_never_calls_ghn_even_when_shipping_is_unavailable(): void
    {
        $this->signIn();
        $this->provider();
        $ghn = $this->createMock(GHNService::class);
        foreach (['getProvinces', 'getDistricts', 'getWards', 'calculateFee'] as $method) {
            $ghn->expects($this->never())->method($method);
        }
        $this->app->instance(GHNService::class, $ghn);
        $this->lookup()->assertOk()->assertJsonPath('street', 'Đường Hoàng Diệu')->assertJsonMissingPath('selection')
            ->assertJsonPath('regions.ward', ['Phường Điện Biên'])
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
            ->assertJsonPath('regions.province', ['Hà Nội'])->assertJsonMissingPath('selection');
        $this->lookup()->assertOk()->assertJsonPath('street', '');
        Http::assertSentCount(1);
    }

    public function test_area_only_first_result_uses_a_nearby_road_name_for_the_detail_field(): void
    {
        $this->signIn();
        Http::fake(['geocoder.test/*' => Http::response(['features' => [
            ['properties' => ['countrycode' => 'VN', 'city' => 'Hà Nội', 'district' => 'Phú Diễn']],
            ['properties' => ['countrycode' => 'VN', 'city' => 'Hà Nội', 'district' => 'Phú Diễn',
                'type' => 'street', 'name' => 'Ngõ 205 Đường Phú Diễn'],
                'geometry' => ['coordinates' => [105.8526, 21.0288]]],
        ]] )]);
        $this->lookup()->assertOk()->assertJsonPath('street', 'Ngõ 205 Đường Phú Diễn')
            ->assertJsonPath('regions.ward', ['Phú Diễn']);
        $this->lookup()->assertOk()->assertJsonPath('street', 'Ngõ 205 Đường Phú Diễn');
        Http::assertSentCount(1);
    }

    public function test_closest_compatible_street_is_used_without_copying_a_neighbours_house_number(): void
    {
        $this->signIn();
        $base = ['countrycode' => 'VN', 'city' => 'Hà Nội'];
        Http::fake(['geocoder.test/*' => Http::response(['features' => [
            ['properties' => $base],
            ['properties' => $base + ['type' => 'street', 'name' => 'Further road'],
                'geometry' => ['coordinates' => [105.8535, 21.0288]]],
            ['properties' => $base + ['street' => 'Near alley', 'name' => 'Neighbour shop', 'housenumber' => '123'],
                'geometry' => ['coordinates' => [105.8526, 21.0288]]],
        ]] )]);
        $this->lookup()->assertOk()->assertJsonPath('street', 'Near alley')->assertDontSee('123')->assertDontSee('Neighbour shop');
    }

    public function test_poi_names_distant_roads_and_conflicting_areas_are_not_used_as_detail_addresses(): void
    {
        $this->signIn();
        $base = ['countrycode' => 'VN', 'city' => 'Hà Nội', 'district' => 'Phú Diễn'];
        Http::fake(['geocoder.test/*' => Http::response(['features' => [
            ['properties' => $base],
            ['properties' => $base + ['name' => 'A restaurant', 'type' => 'house'],
                'geometry' => ['coordinates' => [105.8525, 21.0288]]],
            ['properties' => $base + ['type' => 'street', 'name' => 'Distant road'],
                'geometry' => ['coordinates' => [105.86, 21.0288]]],
            ['properties' => array_replace($base, ['district' => 'Another ward', 'type' => 'street', 'name' => 'Wrong area']),
                'geometry' => ['coordinates' => [105.8525, 21.0288]]],
            ['properties' => $base + ['type' => 'street', 'name' => 'Missing geometry']],
        ]] )]);
        $this->lookup()->assertOk()->assertJsonPath('street', '')->assertJsonPath('regions.ward', ['Phú Diễn']);
    }

    public function test_provider_backoff_and_per_user_rate_limit(): void
    {
        $this->signIn();
        Cache::put('delivery-location:provider-limit', true, 60);
        for ($attempt = 0; $attempt < 5; $attempt++) $this->lookup()->assertStatus(503);
        $this->lookup()->assertTooManyRequests();
        Http::assertNothingSent();
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
