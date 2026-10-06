<?php

namespace App\Services;

use App\Support\DemoMode;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
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

    // Public ward directory, used only when a ward is known but its district is missing.
    public function getWardDirectory(int $provinceId): array
    {
        // Public delivery names only. This directory never receives GPS coordinates.
        $key = 'ghn:ward-directory:v1:'.hash('sha256', $this->baseUrl).':'.$provinceId;
        if ($cached = Cache::get($key)) return $cached;
        $failure = ['code' => 503, 'data' => []];
        $lock = Cache::lock($key.':lock', 45);
        if (!$lock->get()) return $failure;
        try {
            if ($cached = Cache::get($key)) return $cached;
            $districts = $this->getDistricts($provinceId);
            if (($districts['code'] ?? null) != 200 || !is_array($districts['data'] ?? null)
                || !$districts['data'] || count($districts['data']) > 100) return $failure;
            $ids = [];
            foreach ($districts['data'] as $district) {
                $id = filter_var($district['DistrictID'] ?? null, FILTER_VALIDATE_INT);
                if (!$id || (isset($district['ProvinceID']) && (int) $district['ProvinceID'] !== $provinceId)) return $failure;
                $ids[] = $id;
            }
            $ids = array_values(array_unique($ids));
            if (DemoMode::enabled()) {
                $responses = array_combine($ids, array_map(fn ($id) => $this->getWards($id), $ids));
            } else {
                $deadline = microtime(true) + 15;
                $responses = Http::pool(function (Pool $pool) use ($ids, $deadline) {
                    foreach ($ids as $id) {
                        $pool->as((string) $id)->baseUrl($this->baseUrl)->acceptJson()
                            ->withOptions(['verify' => filter_var(config('services.ghn.verify_ssl', false), FILTER_VALIDATE_BOOLEAN)])
                            ->withHeaders(['Token' => $this->token, 'ShopId' => $this->shopId])
                            ->connectTimeout(3)->timeout(4)->withoutRedirecting()
                            ->beforeSending(static function () use ($deadline) {
                                if (microtime(true) >= $deadline) throw new \RuntimeException('Directory lookup timed out');
                            })
                            ->get('/master-data/ward', ['district_id' => $id]);
                    }
                }, concurrency: 5);
            }
            $rows = [];
            foreach ($ids as $id) {
                $response = $responses[$id] ?? null;
                if ($response instanceof Response) {
                    if (!$response->successful()) return $failure;
                    $response = $response->json();
                }
                // A missing district's data could hide a duplicate ward name.
                // Never infer a unique match from an incomplete directory.
                if (!is_array($response) || ($response['code'] ?? null) != 200
                    || !is_array($response['data'] ?? null)) return $failure;
                foreach ($response['data'] as $ward) {
                    if (!is_array($ward) || empty($ward['WardCode']) || !is_string($ward['WardName'] ?? null)
                        || (isset($ward['DistrictID']) && (int) $ward['DistrictID'] !== $id)) return $failure;
                    $rows[] = [
                        'DistrictID' => $id, 'WardCode' => (string) $ward['WardCode'], 'WardName' => $ward['WardName'],
                        'NameExtension' => is_array($ward['NameExtension'] ?? null) ? $ward['NameExtension'] : [],
                    ];
                }
            }
            $result = ['code' => 200, 'data' => $rows];
            Cache::put($key, $result, now()->addHours(6));
            return $result;
        } catch (\Throwable) {
            return $failure;
        } finally {
            $lock->release();
        }
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
