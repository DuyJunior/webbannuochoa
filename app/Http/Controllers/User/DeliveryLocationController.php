<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\DeliveryLocationService;
use Illuminate\Http\Request;
use Throwable;

class DeliveryLocationController extends Controller
{
    public function __invoke(Request $request, DeliveryLocationService $locations)
    {
        $input = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);
        try {
            $suggestion = $locations->suggest((float) $input['latitude'], (float) $input['longitude']);
        } catch (Throwable $exception) {
            // Connection exceptions include the coordinate-bearing URL; do not log/report them.
            return response()->json(['message' => __('Chưa tìm được địa chỉ phù hợp. Bạn có thể thử lại hoặc nhập địa chỉ bên dưới.')], 503)
                ->header('Cache-Control', 'no-store, private');
        }

        return response()->json($suggestion)->header('Cache-Control', 'no-store, private');
    }
}
