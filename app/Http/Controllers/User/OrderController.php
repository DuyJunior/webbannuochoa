<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Perfume;
use App\Services\GHNOrderService;
use App\Services\GHNService;
use App\Services\LoyaltyService;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    // ==========================================
    // 1. CÁC VIEW HIỂN THỊ ĐƠN HÀNG & THANH TOÁN
    // ==========================================
    public function index()
    {
        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống.');
        }

        // Lấy danh sách sản phẩm từ Cart
        $perfumeIds = collect($cart)->map(function ($item, $key) {
            return is_array($item) ? ($item['perfume_id'] ?? null) : (int) $key;
        })->filter()->unique()->values();

        $products = Perfume::whereIn('id', $perfumeIds)->get()->keyBy('id');

        $cartItems = collect($cart)->map(function ($itemData, $itemKey) use ($products) {
            if (is_array($itemData)) {
                $perfumeId = (int) ($itemData['perfume_id'] ?? 0);
                $quantity = (int) ($itemData['quantity'] ?? 1);
                $unitPrice = isset($itemData['unit_price']) ? (float) $itemData['unit_price'] : null;
                $volumeMl = $itemData['volume_ml'] ?? 100;
            } else {
                $perfumeId = (int) $itemKey;
                $quantity = (int) $itemData;
                $unitPrice = null;
                $volumeMl = 100;
            }

            $product = $products->get($perfumeId);
            if (!$product) {
                return null;
            }

            if ($unitPrice === null) {
                $unitPrice = (float) ($product->sale_price ?? $product->price);
            }

            $itemWeight = $product->getWeightForVolume($volumeMl);

            return [
                'product' => $product,
                'quantity' => $quantity,
                'price' => $unitPrice,
                'total' => $unitPrice * $quantity,
                'volume_ml' => $volumeMl,
                'weight' => $itemWeight,
                'total_weight' => $itemWeight * $quantity,
            ];
        })->filter()->values();

        $totalPrice = $cartItems->sum('total');
        $totalWeight = $cartItems->sum('total_weight');

        $loyaltyBalance = LoyaltyService::balance(Auth::id());
        $availableCoupons = Coupon::where('is_active', true)->orderBy('minimum_order')->get()
            ->filter(fn (Coupon $coupon) => $coupon->isAvailableFor((int) $totalPrice));
        return view('user.payment.index', compact('cart', 'cartItems', 'totalPrice', 'totalWeight', 'loyaltyBalance', 'availableCoupons'));
    }

    public function processPayment(Request $request, GHNService $ghn, GHNOrderService $ghnOrderService)
    {
        if (!$request->has('name') && $request->has('customer_name')) {
            $request->merge(['name' => $request->customer_name]);
        }

        if (!$request->filled('payment_method')) {
            $request->merge(['payment_method' => 'cod']);
        }

        if ($request->has('phone')) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$request->phone);
            if (str_starts_with($cleanPhone, '84') && strlen($cleanPhone) === 11) {
                $cleanPhone = '0' . substr($cleanPhone, 2);
            }
            $request->merge(['phone' => $cleanPhone]);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'address' => 'required|string|max:255',
            'to_district_id' => 'required|integer',
            'to_ward_code' => 'required|string',
            'payment_method' => 'nullable|in:cod,momo,atm_domestic,atm_international',
            'note' => 'nullable|string|max:500',
            'coupon_code' => 'nullable|string|max:30',
            'points_used' => 'nullable|integer|min:0',
        ], [
            'name.required' => 'Vui lòng nhập họ tên người nhận.',
            'phone.required' => 'Vui lòng nhập số điện thoại người nhận.',
            'phone.regex' => 'Số điện thoại chỉ được gồm đúng 10 chữ số (bắt đầu bằng số 0).',
            'address.required' => 'Vui lòng nhập địa chỉ nhận hàng.',
            'to_district_id.required' => 'Vui lòng chọn Quận/Huyện giao hàng.',
            'to_ward_code.required' => 'Vui lòng chọn Phường/Xã giao hàng.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
        ]);

        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('user.cart.index')->with('error', 'Không thể thanh toán vì giỏ hàng trống.');
        }

        // 1. Tính tổng tiền hàng và tổng khối lượng sản phẩm
        $perfumeIds = collect($cart)->map(function ($item, $key) {
            return is_array($item) ? ($item['perfume_id'] ?? $item['id'] ?? null) : (int) $key;
        })->filter()->unique()->values();

        $products = Perfume::whereIn('id', $perfumeIds)->get()->keyBy('id');

        $orderItemsData = [];
        $subtotal = 0;
        $totalWeight = 0;

        foreach ($cart as $key => $itemData) {
            if (is_array($itemData)) {
                $perfumeId = (int) ($itemData['perfume_id'] ?? $itemData['id'] ?? 0);
                $quantity = (int) ($itemData['quantity'] ?? 1);
                $unitPrice = isset($itemData['unit_price']) ? (float) $itemData['unit_price'] : (isset($itemData['price']) ? (float) $itemData['price'] : null);
                $volumeMl = (int) ($itemData['volume_ml'] ?? 100);
                $hasGift = (bool) ($itemData['has_gift'] ?? false);
                $engraveText = $itemData['engrave_text'] ?? null;
                $livestreamId = $itemData['livestream_id'] ?? null;
            } else {
                $perfumeId = (int) $key;
                $quantity = (int) $itemData;
                $unitPrice = null;
                $volumeMl = 100;
                $hasGift = false;
                $engraveText = null;
                $livestreamId = null;
            }

            $product = $products->get($perfumeId);
            if (!$product) continue;

            if ($unitPrice === null) {
                $unitPrice = (float) ($product->sale_price ?? $product->price);
            }

            $itemWeight = $product->getWeightForVolume($volumeMl);
            $subtotal += $unitPrice * $quantity;
            $totalWeight += $itemWeight * (int) $quantity;

            $orderItemsData[] = [
                'perfume_id' => $product->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $unitPrice,
                'volume_ml' => $volumeMl,
                'addon_gift' => $hasGift,
                'engrave_text' => $engraveText,
                'livestream_id' => $livestreamId,
            ];
        }

        if (empty($orderItemsData)) {
            return redirect()->route('user.cart.index')->with('error', 'Không thể thanh toán vì giỏ hàng trống.');
        }

        if ($totalWeight <= 0) {
            $totalWeight = (int) config('services.ghn.default_weight', 200);
        }

        // 2. Tính lại phí ship chuẩn xác từ GHN trên server theo đúng tổng khối lượng thực tế
        $feeResponse = $ghn->calculateFee(array_merge([
            'from_district_id' => (int) (config('services.ghn.from_district_id') ?: 1493),
            'to_district_id' => (int) $request->to_district_id,
            'to_ward_code' => (string) $request->to_ward_code,
        ], $ghn->packageParameters($totalWeight)));

        $shippingFee = (isset($feeResponse['code']) && $feeResponse['code'] == 200)
            ? (int) $feeResponse['data']['total']
            : 0;

        // Khóa hàng dữ liệu liên quan để tránh dùng mã hay điểm quá giới hạn khi đặt cùng lúc.
        $order = DB::transaction(function () use ($request, $shippingFee, $subtotal, $orderItemsData) {
            DB::table('users')->where('id', Auth::id())->lockForUpdate()->first();
            $couponCode = strtoupper(trim((string) $request->input('coupon_code', '')));
            $coupon = $couponCode ? Coupon::where('code', $couponCode)->lockForUpdate()->first() : null;
            if ($couponCode && (! $coupon || ! $coupon->isAvailableFor((int) $subtotal))) {
                throw ValidationException::withMessages(['coupon_code' => 'Mã ưu đãi không hợp lệ hoặc chưa đủ điều kiện.']);
            }
            $discount = $coupon ? $coupon->discountFor((int) $subtotal) : 0;
            $points = (int) $request->input('points_used', 0);
            $maximumPoints = min(LoyaltyService::balance(Auth::id()), (int) floor(($subtotal - $discount) * 0.2 / 1000));
            if ($points > $maximumPoints) {
                throw ValidationException::withMessages(['points_used' => 'Số điểm sử dụng vượt quá mức hiện có hoặc giới hạn 20% tiền hàng.']);
            }
            $finalTotal = max(0, $subtotal + $shippingFee - $discount - $points * 1000);
            $order = Order::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'customer_name' => $request->name,
                'address' => $request->address,
                'phone' => $request->phone,
                'total_price' => $finalTotal,
                'coupon_code' => $coupon?->code,
                'discount_amount' => $discount,
                'points_used' => $points,
                'status' => 'pending',
                'to_district_id' => (int) $request->to_district_id,
                'to_ward_code' => (string) $request->to_ward_code,
                'ghn_total_fee' => $shippingFee,
                'shipping_status' => 'pending',
                'gift_wrap' => $request->input('gift_wrap'),
                'gift_card' => $request->input('gift_card'),
                'gift_message' => $request->input('gift_message'),
                'gift_delivery_date' => $request->input('gift_delivery_date'),
            ]);

            foreach ($orderItemsData as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'perfume_id' => $item['perfume_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'volume_ml' => $item['volume_ml'],
                    'addon_gift' => $item['addon_gift'],
                    'engrave_text' => $item['engrave_text'],
                    'livestream_id' => $item['livestream_id'],
                ]);
            }

            return $order;
        });

        // Xóa session giỏ hàng
        session()->forget('cart');

        // 4. Phân luồng thanh toán theo đúng tài liệu Lab 06
        if (in_array($request->payment_method, ['momo', 'atm_domestic', 'atm_international'], true)) {
            PaymentTransaction::create([
                'order_id' => $order->id,
                'gateway' => 'momo',
                'amount' => $order->total_price,
                'status' => 'pending',
            ]);

            return redirect()->route('user.orders.momo.start', [
                'order' => $order,
                'method' => $request->payment_method,
            ]);
        }

        PaymentTransaction::create([
            'order_id' => $order->id,
            'gateway' => 'cod',
            'amount' => $order->total_price,
            'status' => 'pending',
            'message' => 'Thanh toán khi nhận hàng',
        ]);

        // --- NHÁNH COD: TẠO VẬN ĐƠN GHN NGAY LẬP TỨC ---
        $order->load('items.product');
        $ghnOrderResponse = $ghnOrderService->create($order);

        if (($ghnOrderResponse['code'] ?? null) == 200 && !empty($ghnOrderResponse['data']['order_code'])) {
            $order->update([
                'status' => 'cod_ordered',
                'ghn_order_code' => $ghnOrderResponse['data']['order_code'],
                'shipping_status' => 'ready_to_pick',
            ]);

            return redirect()->route('orders.show', $order->id)
                ->with('success', 'Đặt hàng thành công! Mã vận đơn GHN: ' . $ghnOrderResponse['data']['order_code']);
        }

        Log::error('GHN COD Order Failed: ', $ghnOrderResponse ?? []);
        $order->update(['status' => 'cod_ordered']);

        return redirect()->route('user.orders.index')
            ->with('warning', 'Đặt hàng thành công nhưng chưa thể tạo vận đơn GHN tự động.');
    }

    public function paymentPending(Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->load('items.product');

        // Ưu tiên: query param ?method= (khi click từ lịch sử đơn hàng)
        // Fallback: PaymentTransaction cuối cùng -> session
        $allowedMethods = ['atm_domestic', 'atm_international'];
        $queryMethod = request()->query('method');

        if (in_array($queryMethod, $allowedMethods)) {
            $payMethod = $queryMethod;
        } else {
            $lastTransaction = $order->paymentTransactions()->latest()->first();
            $payMethod = $lastTransaction?->gateway
                ?? session('atm_pay_method_' . $order->id, 'atm_domestic');
        }

        return view('user.payment.pending', compact('order', 'payMethod'));
    }

    public function confirmPayment(Order $order, Request $request, GHNOrderService $ghnOrderService)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $gateway = $request->input('gateway', 'atm_domestic');
        $gatewayNames = [
            'atm_domestic' => 'Thẻ ATM Nội Địa (Napas)',
            'atm_international' => 'Thẻ Quốc Tế (Visa/Mastercard)',
            'momo' => 'Ví MoMo',
        ];
        $gatewayName = $gatewayNames[$gateway] ?? 'Trực tuyến';

        // Kiểm tra 4 trường hợp thẻ test theo đúng tài liệu Lab 06:
        $cardNum = preg_replace('/\D/', '', (string) $request->input('card_number', ''));

        if (str_ends_with($cardNum, '0026')) {
            return redirect()->route('user.orders.index')
                ->with('error', '✕ Thanh toán thất bại: Thẻ ' . ($cardNum ?: '9704 0000 0000 0026') . ' đã bị khóa bởi ngân hàng. Vui lòng bấm "Thanh toán lại" để chọn thẻ khác.');
        }

        if (str_ends_with($cardNum, '0034')) {
            return redirect()->route('user.orders.index')
                ->with('error', '✕ Thanh toán thất bại: Số dư tài khoản không đủ tiền để thanh toán đơn hàng. Vui lòng bấm "Thanh toán lại" để thử lại.');
        }

        if (str_ends_with($cardNum, '0042')) {
            return redirect()->route('user.orders.index')
                ->with('error', '✕ Thanh toán thất bại: Giao dịch vượt quá hạn mức thanh toán cho phép của thẻ. Vui lòng bấm "Thanh toán lại" để thử lại.');
        }

        // 1. Xóa session giỏ hàng
        session()->forget('cart');

        // 2. Tạo vận đơn GHN trả trước (cod_amount = 0) nếu chưa có
        $ghnMsg = '';
        $updateData = [
            'status' => 'paid',
            'shipping_status' => 'processing',
        ];

        if (!$order->ghn_order_code) {
            $order->load('items.product');
            $ghnOrderResponse = $ghnOrderService->create($order, true);

            if (($ghnOrderResponse['code'] ?? null) == 200 && !empty($ghnOrderResponse['data']['order_code'])) {
                $updateData['ghn_order_code'] = $ghnOrderResponse['data']['order_code'];
                $updateData['shipping_status'] = 'ready_to_pick';
                $ghnMsg = ' (Mã vận đơn GHN: ' . $ghnOrderResponse['data']['order_code'] . ')';
            }
        }

        $order->update($updateData);

        // 3. Cập nhật hoặc tạo PaymentTransaction
        if (Schema::hasTable('payment_transactions')) {
            $tx = $order->paymentTransactions()->latest()->first();
            if ($tx) {
                $tx->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'gateway' => $gateway,
                    'message' => 'Thanh toán thành công qua ' . $gatewayName,
                ]);
            } else {
                PaymentTransaction::create([
                    'order_id' => $order->id,
                    'gateway' => $gateway,
                    'amount' => $order->total_price,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'message' => 'Thanh toán thành công qua ' . $gatewayName,
                ]);
            }
        }

        return redirect()->route('user.orders.index')
            ->with('success', '✓ Đã thanh toán đơn hàng #' . $order->id . ' qua ' . $gatewayName . ' thành công!' . $ghnMsg);
    }

    public function orderHistory()
    {
        $with = ['items.product'];
        if (Schema::hasTable('payment_transactions')) {
            $with['paymentTransactions'] = function ($query) {
                $query->latest();
            };
        }

        $orders = Order::where('user_id', Auth::id())
            ->with($with)
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('user.payment.order', compact('orders'));
    }

    public function show(Order $order)
    {
        if ($order->user_id !== Auth::id() && (!Auth::user() || !Auth::user()->is_admin)) {
            abort(403);
        }

        $with = ['items.product'];
        if (Schema::hasTable('payment_transactions')) {
            $with['paymentTransactions'] = function ($query) {
                $query->latest();
            };
        }

        $order->load($with);

        return view('user.payment.show', compact('order'));
    }

    public function cancel(Order $order, GHNService $ghn)
    {
        abort_unless($order->user_id === Auth::id(), 403);
        $allowedStatuses = ['pending', 'ready_to_pick'];

        if (!in_array($order->shipping_status, $allowedStatuses, true)) {
            return back()->with('error', 'Đơn hàng không còn ở trạng thái có thể hủy.');
        }

        if ($order->ghn_order_code) {
            $response = $ghn->cancelOrder([$order->ghn_order_code]);
            if (($response['code'] ?? null) !== 200) {
                return back()->with('error', 'GHN không cho phép hủy vận đơn này: ' . ($response['message'] ?? ''));
            }
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'status' => 'cancelled',
                'shipping_status' => 'cancelled',
            ]);

            if (Schema::hasTable('payment_transactions') && method_exists($order, 'paymentTransactions')) {
                $order->paymentTransactions()
                    ->whereIn('status', ['pending', 'initiated'])
                    ->update(['status' => 'cancelled']);

                $order->paymentTransactions()
                    ->where('status', 'paid')
                    ->update(['status' => 'refund_pending']);
            }
        });

        return back()->with('success', 'Đơn hàng đã được hủy thành công.');
    }

    // ==========================================
    // 2. TRA CỨU & KIỂM TRA ĐƠN HÀNG (PUBLIC)
    // ==========================================
    public function trackingForm(Request $request)
    {
        $myRecentOrders = collect();
        if (Auth::check()) {
            $myRecentOrders = Order::where('user_id', Auth::id())
                ->with(['items.product'])
                ->orderByDesc('created_at')
                ->take(5)
                ->get();
        }

        return view('user.payment.tracking', [
            'orders' => null,
            'searched' => false,
            'keyword' => '',
            'phone' => Auth::user()?->phone ?? '',
            'myRecentOrders' => $myRecentOrders,
        ]);
    }

    public function trackingSearch(Request $request)
    {
        $keyword = trim((string) $request->input('keyword', ''));
        $phone = trim((string) $request->input('phone', ''));

        if (empty($keyword) && empty($phone)) {
            return back()->with('error', 'Vui lòng nhập Mã đơn hàng / Mã GHN hoặc Số điện thoại để tra cứu.')->withInput();
        }

        $query = Order::query()->with(['items.product']);

        if (!empty($phone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            $query->where(function ($q) use ($phone, $cleanPhone) {
                $q->where('phone', 'like', "%{$phone}%");
                if (!empty($cleanPhone)) {
                    $q->orWhereRaw("REPLACE(REPLACE(phone, ' ', ''), '-', '') LIKE ?", ["%{$cleanPhone}%"]);
                }
            });
        }

        if (!empty($keyword)) {
            $cleanKeyword = ltrim($keyword, '#');
            $query->where(function ($q) use ($keyword, $cleanKeyword) {
                if (is_numeric($cleanKeyword)) {
                    $q->where('id', (int) $cleanKeyword);
                }
                $q->orWhere('ghn_order_code', 'like', "%{$keyword}%")
                  ->orWhere('ghn_order_code', 'like', "%{$cleanKeyword}%");
            });
        }

        $orders = $query->orderByDesc('created_at')->get();

        $myRecentOrders = collect();
        if (Auth::check()) {
            $myRecentOrders = Order::where('user_id', Auth::id())
                ->with(['items.product'])
                ->orderByDesc('created_at')
                ->take(5)
                ->get();
        }

        return view('user.payment.tracking', [
            'orders' => $orders,
            'searched' => true,
            'keyword' => $keyword,
            'phone' => $phone,
            'myRecentOrders' => $myRecentOrders,
        ]);
    }
}
