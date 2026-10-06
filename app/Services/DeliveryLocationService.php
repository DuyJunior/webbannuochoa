<?php

namespace App\Services;

use App\Exceptions\DeliveryLocationException;
use Illuminate\Support\Facades\Cache;

class DeliveryLocationService
{
    public function __construct(private PhotonGeocoder $geocoder) {}

    public function suggest(float $latitude, float $longitude): array
    {
        // No coordinates in cache keys, application logs or customer records.
        $coordinates = ['lat' => round($latitude, 5), 'lon' => round($longitude, 5)];
        $key = 'delivery-location:v3:'.hash('sha256', json_encode($coordinates));
        $properties = Cache::get($key);
        if (!is_array($properties)) {
            $feature = $this->geocoder->reverse($latitude, $longitude);
            $properties = $feature['properties'];
            $properties['street'] = $this->nearbyStreet($feature, $latitude, $longitude);
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
        // Location lookup only returns address names. Checkout matches these
        // against its delivery dropdowns; GHN never receives map coordinates.
        $names = static fn (array $keys) => array_values(array_unique(array_filter(
            array_map(static fn ($key) => $properties[$key] ?? '', $keys)
        )));
        return [
            'label' => implode(', ', $labels),
            // Nearby building numbers are not proof of the customer's house number.
            'street' => $properties['street'] ?? '',
            'regions' => [
                'province' => $names(['state', 'city']),
                'district' => $names(['county', 'city', 'district']),
                'ward' => $names(['locality', 'district']),
            ],
        ];
    }

    private function nearbyStreet(array $feature, float $latitude, float $longitude): string
    {
        $area = $feature['properties'];
        $street = '';
        $nearest = 150.0;
        foreach ($feature['address_candidates'] ?? [$feature] as $candidate) {
            if (!is_array($candidate) || !is_array($candidate['properties'] ?? null)) continue;
            $properties = $candidate['properties'];
            if (!is_string($properties['countrycode'] ?? null) || strtoupper($properties['countrycode']) !== 'VN') continue;
            // Do not borrow a road across an administrative boundary.
            foreach (['state', 'city', 'county', 'district', 'locality'] as $key) {
                if (!empty($area[$key]) && !empty($properties[$key]) && $area[$key] !== $properties[$key]) continue 2;
            }
            $name = $properties['street'] ?? '';
            // Photon puts a road's own name in `name`, not in `street`.
            // A POI's name (shop, building, etc.) is never an address line.
            if ((!is_string($name) || trim($name) === '') && ($properties['type'] ?? '') === 'street') {
                $name = $properties['name'] ?? '';
            }
            if (!is_string($name) || trim($name) === '' || mb_strlen($name) > 200) continue;
            $distance = $this->distance($candidate['geometry']['coordinates'] ?? null, $latitude, $longitude);
            if ($distance <= $nearest) {
                $nearest = $distance;
                $street = trim($name);
            }
        }

        return $street;
    }

    private function distance(mixed $point, float $latitude, float $longitude): float
    {
        if (!is_array($point) || count($point) < 2 || !is_numeric($point[0] ?? null) || !is_numeric($point[1] ?? null)) {
            return INF;
        }
        $lat = (float) $point[1];
        $lon = (float) $point[0];
        if (!is_finite($lat) || !is_finite($lon) || abs($lat) > 90 || abs($lon) > 180) return INF;
        $a = sin(deg2rad($lat - $latitude) / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad($lat)) * sin(deg2rad($lon - $longitude) / 2) ** 2;

        return 6371000 * 2 * asin(sqrt(min(1, max(0, $a))));
    }
}
