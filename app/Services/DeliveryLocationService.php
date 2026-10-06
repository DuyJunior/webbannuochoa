<?php

namespace App\Services;

use App\Exceptions\DeliveryLocationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class DeliveryLocationService
{
    public function __construct(private GHNService $ghn) {}

    public function suggest(float $latitude, float $longitude): array
    {
        // No coordinates in cache keys, application logs or customer records.
        $coordinates = ['lat' => round($latitude, 5), 'lon' => round($longitude, 5)];
        $key = 'delivery-location:v2:'.hash('sha256', json_encode($coordinates));
        $properties = Cache::get($key);
        if (!is_array($properties)) {
            if (!Cache::add('delivery-location:provider-limit', true, 1)) {
                throw new DeliveryLocationException('busy');
            }
            try {
                $response = Http::acceptJson()->withUserAgent('Soopi/1.0 (+https://soopi.site)')
                    ->connectTimeout(8)->timeout(15)->withoutRedirecting()
                    ->get(rtrim(config('services.photon.url'), '/').'/reverse', $coordinates + ['limit' => 1, 'radius' => 1]);
            } catch (ConnectionException) {
                // Never retain the exception: its URL contains the submitted coordinates.
                throw new DeliveryLocationException('connection');
            }
            if ($response->status() === 429) {
                throw new DeliveryLocationException('busy');
            }
            if (!$response->successful()) {
                throw new DeliveryLocationException('unavailable');
            }
            $properties = $response->json('features.0.properties');
            if (!is_array($properties)) {
                throw new DeliveryLocationException(is_array($response->json('features')) ? 'not_found' : 'unavailable');
            }
            if (!is_string($properties['countrycode'] ?? null) || strtoupper($properties['countrycode']) !== 'VN') {
                throw new DeliveryLocationException('not_found');
            }
            // Wider coverage can supply the surrounding area in sparsely mapped places.
            // Never suggest a street from a distant result as the customer's street.
            if (!$this->isNearby($response->json('features.0.geometry.coordinates'), $latitude, $longitude)) {
                unset($properties['street']);
            }
            // Only retain address labels, never provider object IDs or coordinates.
            $properties = array_filter(array_intersect_key($properties, array_flip([
                'street', 'locality', 'district', 'city', 'county', 'state',
            ])), fn ($value) => is_string($value) && mb_strlen($value) <= 200);
            Cache::put($key, $properties, now()->addMinutes(15));
        }

        $labels = array_values(array_unique(array_filter($properties)));
        if (!$labels) {
            throw new DeliveryLocationException('not_found');
        }
        $selection = ['province' => '', 'district' => '', 'ward' => ''];
        // A missing/ambiguous GHN match leaves the choice to the customer.
        try {
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
        } catch (Throwable) {
            // GHN may be temporarily unavailable. The map suggestion is still useful;
            // keep any matched parent region and let checkout's existing cascade retry the rest.
        }

        return [
            'label' => implode(', ', $labels),
            // Nearby building numbers are not proof of the customer's house number.
            'street' => $properties['street'] ?? '',
            'selection' => $selection,
            'matched' => $selection['ward'] !== '',
        ];
    }

    private function isNearby(mixed $point, float $latitude, float $longitude): bool
    {
        if (!is_array($point) || count($point) < 2 || !is_numeric($point[0] ?? null) || !is_numeric($point[1] ?? null)) {
            return false;
        }
        $lat = (float) $point[1];
        $lon = (float) $point[0];
        if (!is_finite($lat) || !is_finite($lon) || abs($lat) > 90 || abs($lon) > 180) return false;
        $a = sin(deg2rad($lat - $latitude) / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad($lat)) * sin(deg2rad($lon - $longitude) / 2) ** 2;

        return 6371000 * 2 * asin(sqrt(min(1, max(0, $a)))) <= 150;
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
        // GHN also exposes legacy region aliases. A shared alias must not hide
        // an unambiguous match with the region's current canonical name.
        $canonical = array_values(array_filter($response['data'], static function ($row) use ($nameKey, $normalize, $candidates) {
            return in_array($normalize($row[$nameKey] ?? ''), $candidates, true);
        }));
        if ($canonical) {
            return count($canonical) === 1 ? $canonical[0] : null;
        }
        $matches = array_values(array_filter($response['data'], static function ($row) use ($nameKey, $normalize, $candidates) {
            $names = array_merge([$row[$nameKey] ?? ''], is_array($row['NameExtension'] ?? null) ? $row['NameExtension'] : []);
            return count(array_intersect($candidates, array_filter(array_map($normalize, $names)))) > 0;
        }));

        return count($matches) === 1 ? $matches[0] : null;
    }
}
