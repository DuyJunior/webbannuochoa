<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SePayService;
use App\Support\DemoMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SePayController extends Controller
{
    public function show(Order $order, Request $request, SePayService $sepay)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        if ($order->is_demo) {
            abort_unless(DemoMode::enabled(), 404);

            return redirect()->route('user.orders.payment.pending', $order);
        }
        abort_unless($sepay->ready(), 503, __('Thanh toán SePay đang tạm ngưng. Vui lòng liên hệ cửa hàng.'));
        $payment = $order->paymentTransactions()->where('gateway', 'sepay')->whereNotNull('gateway_order_id')->latest('id')->first();
        abort_unless($payment, 404);
        if ($payment->status === 'paid') {
            return redirect()->route('orders.show', $order);
        }
        abort_unless($sepay->canPay($order) && in_array($payment->status, ['pending', 'initiated'], true), 409,
            __('Đơn hàng đã hết hạn hoặc không thể thanh toán.'));

        return response()->view('user.payment.sepay', [
            'order' => $order, 'payment' => $payment, 'receiver' => $payment->request_payload,
            'qrUrl' => $sepay->qrUrl($payment),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function status(Order $order, Request $request, SePayService $sepay)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $payment = $order->paymentTransactions()->where('gateway', 'sepay')->latest('id')->first();
        abort_unless($payment && ! $order->is_demo, 404);

        return response()->json([
            'status' => $payment->status,
            'can_pay' => $sepay->ready() && $sepay->canPay($order),
            'order_url' => route('orders.show', $order),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function webhook(Request $request, SePayService $sepay)
    {
        if (! $sepay->ready()) {
            return response()->json(['success' => false], 503);
        }
        if (strlen($request->getContent()) > 16384) {
            return response()->json(['success' => false], 413);
        }
        if (! $request->isJson() || ! $sepay->validSignature($request)) {
            return response()->json(['success' => false], 401);
        }
        $validator = Validator::make($request->json()->all(), [
            'id' => ['required', 'integer', 'min:1'],
            'gateway' => ['required', 'string', 'max:100'],
            'accountNumber' => ['required', 'string', 'max:100'],
            'subAccount' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:64'],
            'transferType' => ['required', 'in:in,out'],
            'transferAmount' => ['required', 'integer', 'min:1', 'max:9999999999999'],
            'referenceCode' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false], 422);
        }
        $sepay->receive($validator->validated());

        return response()->json(['success' => true]);
    }
}
