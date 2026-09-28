<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ShippingUpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GHNWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Enable only behind a relay configured to attach this application-specific token.
        $token = (string) config('demo.ghn_webhook_token');
        abort_unless($token !== '' && hash_equals($token, (string) $request->header('X-Webhook-Token')), 403);
        Log::info('GHN Webhook received', ['order_code' => $request->input('OrderCode')]);
        $request->validate(['OrderCode' => 'required|string|max:100', 'Status' => 'required|string|max:50']);

        $orderCode = $request->input('OrderCode');
        $status = $request->input('Status');

        if ($orderCode) {
            $order = Order::where('ghn_order_code', $orderCode)->first();
            if ($order) {
                app(ShippingUpdateService::class)->apply($order, $status);
            }
        }

        return response()->json(['message' => 'GHN Webhook processed']);
    }
}
