<?php

namespace Tests\Feature;

use App\Services\GHNService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliveryWardDirectoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.ghn.base_url' => 'https://shipping.test']);
    }

    private function fakeDirectory(bool $duplicate = false, bool $failure = false): void
    {
        Http::fake(function ($request) use ($duplicate, $failure) {
            if (str_contains($request->url(), '/master-data/district')) {
                return Http::response(['code' => 200, 'data' => [
                    ['ProvinceID' => 201, 'DistrictID' => 1482, 'DistrictName' => 'Quận Bắc Từ Liêm'],
                    ['ProvinceID' => 201, 'DistrictID' => 1485, 'DistrictName' => 'Quận Cầu Giấy'],
                ]]);
            }
            if ((int) $request['district_id'] === 1482) {
                return Http::response(['code' => 200, 'data' => [
                    ['WardCode' => 'PD', 'WardName' => 'Phường Phú Diễn', 'DistrictID' => 1482],
                ]]);
            }
            if ($failure) return Http::response([], 503);
            return Http::response(['code' => 200, 'data' => [
                ['WardCode' => 'OTHER', 'WardName' => $duplicate ? 'Phú Diễn' : 'Dịch Vọng', 'DistrictID' => 1485],
            ]]);
        });
    }

    public function test_complete_directory_retains_parent_ids_and_is_cached_without_map_requests(): void
    {
        $this->fakeDirectory();
        $ghn = app(GHNService::class);
        $result = $ghn->getWardDirectory(201);
        $this->assertSame(200, $result['code']);
        $this->assertSame(1482, $result['data'][0]['DistrictID']);
        $this->assertSame('Phường Phú Diễn', $result['data'][0]['WardName']);
        $this->assertSame($result, $ghn->getWardDirectory(201));
        Http::assertSentCount(3);
        Http::assertNotSent(fn ($request) => !str_starts_with($request->url(), 'https://shipping.test/')
            || isset($request['latitude']) || isset($request['longitude']));
    }

    public function test_incomplete_directory_is_not_returned_or_cached_as_a_unique_match(): void
    {
        $this->fakeDirectory(failure: true);
        $ghn = app(GHNService::class);
        $this->assertSame(['code' => 503, 'data' => []], $ghn->getWardDirectory(201));
        $this->assertSame(['code' => 503, 'data' => []], $ghn->getWardDirectory(201));
        Http::assertSentCount(6);
    }

    public function test_duplicate_ward_names_are_preserved_for_ambiguity_detection(): void
    {
        $this->fakeDirectory(duplicate: true);
        $result = app(GHNService::class)->getWardDirectory(201);
        $this->assertCount(2, $result['data']);
        $this->assertSame([1482, 1485], array_column($result['data'], 'DistrictID'));
    }

    public function test_district_list_failure_never_scans_wards(): void
    {
        Http::fake(['*' => Http::response([], 503)]);
        $this->assertSame(['code' => 503, 'data' => []], app(GHNService::class)->getWardDirectory(201));
        Http::assertSentCount(1);
    }
}
