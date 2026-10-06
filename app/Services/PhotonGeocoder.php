<?php

namespace App\Services;

use App\Exceptions\DeliveryLocationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PhotonGeocoder
{
    public function reverse(float $latitude, float $longitude): array
    {
        if (!Cache::add('delivery-location:provider-limit', true, 1)) {
            throw new DeliveryLocationException('busy');
        }

        try {
            $response = Http::acceptJson()->withUserAgent('Soopi/1.0 (+https://soopi.site)')
                ->connectTimeout(8)->timeout(15)->withoutRedirecting()
                // One recovery attempt for transient transport/server errors only.
                // Never retry a quota/authentication failure or a valid empty result.
                ->retry(2, 1000, fn ($error) => $error instanceof ConnectionException
                    || ($error instanceof RequestException && $error->response->serverError()), throw: false)
                ->get(rtrim(config('services.photon.url'), '/').'/reverse', [
                    'lat' => round($latitude, 5), 'lon' => round($longitude, 5), 'limit' => 5, 'radius' => 1,
                ]);
        } catch (ConnectionException) {
            // Exception URLs contain coordinates. Never retain/log the original.
            throw new DeliveryLocationException('connection');
        }

        if ($response->status() === 429) {
            throw new DeliveryLocationException('busy');
        }
        if (!$response->successful()) {
            throw new DeliveryLocationException('unavailable');
        }
        $feature = $response->json('features.0');
        if (!is_array($feature) || !is_array($feature['properties'] ?? null)) {
            throw new DeliveryLocationException(is_array($response->json('features')) ? 'not_found' : 'unavailable');
        }
        $country = $feature['properties']['countrycode'] ?? null;
        if (!is_string($country) || strtoupper($country) !== 'VN') {
            throw new DeliveryLocationException('not_found');
        }

        // The first result may only name an administrative area. Preserve the
        // other nearby results so the address service can find a street too.
        $feature['address_candidates'] = array_slice($response->json('features'), 0, 5);

        return $feature;
    }
}
