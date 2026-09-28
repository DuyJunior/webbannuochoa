<?php

namespace App\Services;

use App\Support\DemoMode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GHNService
{
    protected string $baseUrl;

    protected string $token;

    protected int $shopId;

    public function __construct()
    {
        $this->baseUrl = (config('services.ghn.base_url') ?: 'https://dev-online-gateway.ghn.vn/shiip/public-api');
        $this->token = config('services.ghn.token') ?? '';
        $this->shopId = (int) config('services.ghn.shop_id', 0);
    }

    protected function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->withOptions([
                'verify' => filter_var(config('services.ghn.verify_ssl', false), FILTER_VALIDATE_BOOLEAN),
            ])
            ->acceptJson()
            ->timeout(15)
            ->withHeaders([
                'Token' => $this->token,
                'ShopId' => $this->shopId,
                'Content-Type' => 'application/json',
            ]);
    }

    // Lấy Tỉnh/Thành
    public function getProvinces(): array
    {
        if (DemoMode::enabled()) {
            return ['code' => 200, 'data' => [['ProvinceID' => 201, 'ProvinceName' => 'Hà Nội (DEMO)']]];
        }

        return $this->get('/master-data/province');
    }

    // Lấy Quận/Huyện
    public function getDistricts(int $provinceId): array
    {
        if (DemoMode::enabled()) {
            return ['code' => 200, 'data' => $provinceId === 201 ? [['DistrictID' => 1493, 'DistrictName' => 'Quận demo']] : []];
        }

        return $this->get('/master-data/district', [
            'province_id' => $provinceId,
        ]);
    }

    // Lấy Phường/Xã
    public function getWards(int $districtId): array
    {
        if (DemoMode::enabled()) {
            return ['code' => 200, 'data' => $districtId === 1493 ? [['WardCode' => 'DEMO', 'WardName' => 'Phường demo']] : []];
        }

        return $this->get('/master-data/ward', [
            'district_id' => $districtId,
        ]);
    }

    // Tính phí giao hàng
    public function calculateFee(array $params): array
    {
        if (DemoMode::enabled()) {
            return ['code' => 200, 'data' => ['total' => 30000], 'message' => 'DEMO: phí cố định 30.000đ'];
        }

        return $this->post('/v2/shipping-order/fee', array_merge([
            'shop_id' => $this->shopId,
        ], $params));
    }

    // Tạo đơn giao hàng
    public function createOrder(array $orderData): array
    {
        return $this->post('/v2/shipping-order/create', array_merge([
            'shop_id' => $this->shopId,
        ], $orderData));
    }

    // Hủy đơn hàng
    public function cancelOrder(array $orderCodes): array
    {
        if (count(array_filter($orderCodes, fn ($code) => str_starts_with($code, 'DEMO-'))) === count($orderCodes)) {
            return ['code' => 200, 'message' => 'DEMO: đã hủy vận đơn mô phỏng'];
        }

        return $this->post('/v2/switch-status/cancel', [
            'order_codes' => $orderCodes,
            'shop_id' => $this->shopId,
        ]);
    }

    // Helper package parameters
    public function packageParameters(int $weight = 200): array
    {
        $weight = $weight > 0 ? $weight : (int) config('services.ghn.default_weight', 200);

        // Kích thước chuẩn cho kiện hàng nước hoa để khối lượng quy đổi không vượt quá khối lượng thực
        // Quy đổi GHN: (dài x rộng x cao)/5 (gram). Kích thước 10x10x10 cm quy đổi đúng 200g.
        $height = max(5, min(10, (int) ceil(($weight * 5) / 100)));

        return [
            'service_type_id' => 2,
            'weight' => $weight,
            'length' => 10,
            'width' => 10,
            'height' => $height,
        ];
    }

    public function productWeight(): int
    {
        return (int) config('services.ghn.default_weight', 200);
    }

    protected function get(string $uri, array $query = []): array
    {
        try {
            $response = $this->client()->get($uri, $query);
            if (! $response->successful()) {
                Log::warning('GHN GET request failed', [
                    'uri' => $uri,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return ['code' => $response->status(), 'message' => 'GHN API request failed.', 'data' => []];
            }

            return $response->json() ?? ['code' => -1, 'message' => 'GHN returned an empty response.'];
        } catch (ConnectionException $exception) {
            Log::error('Unable to connect to GHN', ['uri' => $uri, 'error' => $exception->getMessage()]);

            return ['code' => -1, 'message' => 'Unable to connect to GHN.', 'data' => []];
        }
    }

    protected function post(string $uri, array $payload): array
    {
        try {
            $response = $this->client()->post($uri, $payload);
            if (! $response->successful()) {
                Log::warning('GHN POST request failed', [
                    'uri' => $uri,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return ['code' => $response->status(), 'message' => 'GHN API request failed.', 'data' => null];
            }

            return $response->json() ?? ['code' => -1, 'message' => 'GHN returned an empty response.'];
        } catch (ConnectionException $exception) {
            Log::error('Unable to connect to GHN', ['uri' => $uri, 'error' => $exception->getMessage()]);

            return ['code' => -1, 'message' => 'Unable to connect to GHN.', 'data' => null];
        }
    }
}
