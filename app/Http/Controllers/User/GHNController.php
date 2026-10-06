<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\CartQuoteService;
use App\Services\CheckoutSelectionService;
use App\Services\GHNService;
use Illuminate\Http\Request;

class GHNController extends Controller
{
    public function getProvinces(GHNService $ghn)
    {
        return response()->json($ghn->getProvinces());
    }

    public function getDistricts(int $provinceId, GHNService $ghn)
    {
        return response()->json($ghn->getDistricts($provinceId));
    }

    public function getWards(int $districtId, GHNService $ghn)
    {
        return response()->json($ghn->getWards($districtId));
    }

    public function getWardDirectory(int $provinceId, GHNService $ghn)
    {
        $result = $ghn->getWardDirectory($provinceId);
        return response()->json($result, ($result['code'] ?? null) == 200 ? 200 : 503);
    }

    public function getShippingFee(Request $request, GHNService $ghn)
    {
        $request->validate([
            'to_district_id' => 'required|integer',
            'to_ward_code' => 'required|string',
        ]);

        $cart = CheckoutSelectionService::forRequest($request, session('cart', []));
        $weight = $cart ? app(CartQuoteService::class)->quote($cart)['weight'] : 0;

        if ($weight <= 0) {
            $weight = (int) config('services.ghn.default_weight', 200);
        }

        $fromDistrictId = (int) (config('services.ghn.from_district_id') ?: 1493);

        return response()->json($ghn->calculateFee(array_merge([
            'from_district_id' => $fromDistrictId,
            'to_district_id' => (int) $request->to_district_id,
            'to_ward_code' => (string) $request->to_ward_code,
        ], $ghn->packageParameters($weight))));
    }
}
