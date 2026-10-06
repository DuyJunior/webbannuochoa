<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Exceptions\DeliveryLocationException;
use App\Services\DeliveryLocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            $reason = $exception instanceof DeliveryLocationException ? $exception->getMessage() : 'unavailable';
            $messages = [
                'connection' => __('Đã lấy được vị trí nhưng kết nối đến bản đồ bị gián đoạn. Hãy thử lại hoặc tự nhập địa chỉ.'),
                'busy' => __('Dịch vụ bản đồ đang bận. Vui lòng chờ vài giây rồi thử lại hoặc tự nhập địa chỉ.'),
                'not_found' => __('Bản đồ chưa có địa chỉ đủ chi tiết tại vị trí này. Vui lòng tự nhập địa chỉ giao hàng.'),
                'unavailable' => __('Dịch vụ tìm địa chỉ tạm thời chưa phản hồi. Bạn vẫn có thể tự nhập địa chỉ để tiếp tục.'),
            ];
            if (!isset($messages[$reason])) $reason = 'unavailable';
            // Only a fixed reason code is logged, never exception messages, URLs, coordinates or user IDs.
            Log::warning('Delivery address lookup unavailable', ['reason' => $reason]);
            return response()->json(['code' => $reason, 'message' => $messages[$reason]], 503)
                ->header('Cache-Control', 'no-store, private');
        }

        return response()->json($suggestion)->header('Cache-Control', 'no-store, private');
    }
}
