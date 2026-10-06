<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class DeliveryLocationService
{
    public function __construct(private GHNService $ghn) {}

    public function suggest(float $latitude, float $longitude): array
    {
        // No coordinates in cache keys, application logs or customer records.
        $coordinates = ['lat' => round($latitude, 5), 'lon' => round($longitude, 5)];
        $key = 'delivery-location:'.hash('sha256', json_encode($coordinates));
        $properties = Cache::get($key);
        if (!is_array($properties)) {
            if (!Cache::add('delivery-location:provider-limit', true, 1)) {
                throw new RuntimeException('busy');
            }
            $response = Http::acceptJson()->withUserAgent('Soopi delivery address assistant')
                ->connectTimeout(3)->timeout(8)->withoutRedirecting()
                ->get(rtrim(config('services.photon.url'), '/').'/reverse', $coordinates + ['limit' => 1, 'radius' => 0.1]);
            if (!$response->successful()) {
                throw new RuntimeException('unavailable');
            }
            $properties = $response->json('features.0.properties');
            if (!is_array($properties) || strtoupper($properties['countrycode'] ?? '') !== 'VN') {
                throw new RuntimeException('not_found');
            }
            // Only retain address labels, never provider object IDs or coordinates.
            $properties = array_filter(array_intersect_key($properties, array_flip([
                'street', 'locality', 'district', 'city', 'county', 'state',
            ])), fn ($value) => is_string($value) && mb_strlen($value) <= 200);
            Cache::put($key, $properties, now()->addMinutes(15));
        }

        $labels = array_values(array_unique(array_filter($properties)));
        if (!$labels) {
            throw new RuntimeException('not_found');
        }
        $selection = ['province' => '', 'district' => '', 'ward' => ''];
        // A missing/ambiguous GHN match leaves the choice to the customer.
        $province = $this->match($this->ghn->getProvinces(), 'ProvinceName', [$properties['state'] ?? '', $properties['city'] ?? '']);
        if ($province) {
            $selection['province'] = (string) $province['ProvinceID'];
            $district = $this->match($this->ghn->getDistricts($province['ProvinceID']), 'DistrictName', [
                $properties['county'] ?? '', $properties['city'] ?? '', $properties['district'] ?? '',
            ]);
            if ($district) {
                $selection['district'] = (string) $district['DistrictID'];
                $ward = $this->match($this->ghn->getWards($district['DistrictID']), 'WardName', [
                    $properties['locality'] ?? '', $properties['district'] ?? '',
                ]);
                if ($ward) {
                    $selection['ward'] = (string) $ward['WardCode'];
                }
            }
        }

        return [
            'label' => implode(', ', $labels),
            // Nearby building numbers are not proof of the customer's house number.
            'street' => $properties['street'] ?? '',
            'selection' => $selection,
        ];
    }

    private function match(array $response, string $nameKey, array $candidates): ?array
    {
        if (($response['code'] ?? null) != 200 || !is_array($response['data'] ?? null)) {
            return null;
        }
        $normalize = static function (string $name): string {
            $name = Str::lower(Str::ascii(trim($name)));
            $name = preg_replace('/^(thanh pho|tinh|quan|huyen|thi xa|phuong|xa|thi tran|tp\.?|q\.?|p\.?)\s+/u', '', $name);
            return preg_replace('/\s+/', ' ', $name);
        };
        $candidates = array_filter(array_map($normalize, $candidates));
        $matches = array_values(array_filter($response['data'], static function ($row) use ($nameKey, $normalize, $candidates) {
            $names = array_merge([$row[$nameKey] ?? ''], is_array($row['NameExtension'] ?? null) ? $row['NameExtension'] : []);
            return count(array_intersect($candidates, array_filter(array_map($normalize, $names)))) > 0;
        }));

        return count($matches) === 1 ? $matches[0] : null;
    }
}
