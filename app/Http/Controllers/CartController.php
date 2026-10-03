<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Perfume;
use App\Services\CartQuoteService;
use App\Services\CartStockService;
use App\Services\CheckoutSelectionService;
use App\Services\DiscoveryBoxService;
use App\Services\GiftBundleService;
use App\Services\OrderEmailService;
use App\Services\OrderInventoryService;
use App\Support\DemoMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        $cart = $request->session()->get('cart', []);

        $perfumeIds = collect($cart)->map(function ($item, $key) {
            return is_array($item) ? ($item['perfume_id'] ?? null) : (int) $key;
        })->filter()->unique()->values();

        $products = Perfume::query()->whereKey($perfumeIds)->get()->keyBy('id');

        $unavailableItems = [];
        $items = collect($cart)->map(function ($itemData, $itemKey) use ($products, $cart, &$unavailableItems) {
            if (is_array($itemData)) {
                $perfumeId = (int) ($itemData['perfume_id'] ?? 0);
                $quantity = (int) ($itemData['quantity'] ?? 1);
                $volumeMl = (int) ($itemData['volume_ml'] ?? 100);
                $hasGift = (bool) ($itemData['has_gift'] ?? false);
                $hasEngrave = (bool) ($itemData['has_engrave'] ?? false);
                $engraveText = $itemData['engrave_text'] ?? null;
                $unitPrice = isset($itemData['unit_price']) ? (float) $itemData['unit_price'] : null;
            } else {
                $perfumeId = (int) $itemKey;
                $quantity = (int) $itemData;
                $volumeMl = 100;
                $hasGift = false;
                $hasEngrave = false;
                $engraveText = null;
                $unitPrice = null;
            }

            $product = $products->get($perfumeId);
            if ($volumeMl === 0 && $product) {
                $volumeMl = (int) ($product->volume_ml ?: 100);
            }

            try {
                $quoted = app(CartQuoteService::class)->quote([$itemKey => $itemData])['items'][0];
                if ($quantity > CartStockService::limitFor($quoted, array_diff_key($cart, [$itemKey => true]))) {
                    throw ValidationException::withMessages(['cart' => __('Không đủ tồn kho sau khi tính các món khác trong giỏ. Hãy giảm số lượng hoặc xóa và chọn lại.')]);
                }
            } catch (ValidationException $exception) {
                $unavailableItems[] = ['item_key' => (string) $itemKey, 'name' => $product?->name ?? __('Sản phẩm đã ngừng bán'), 'reason' => collect($exception->errors())->flatten()->first()];

                return null;
            }
            $unitPrice = $quoted['price'];
            $volumeMl = $quoted['volume_ml'];

            $isDiscovery = (bool) ($itemData['is_discovery_box'] ?? false);
            $isGiftBundle = $quoted['is_gift_bundle'];
            $customTitle = $itemData['title'] ?? null;
            $sampleNames = $quoted['sample_names'];
            if ($isGiftBundle) {
                $customTitle = $quoted['product_name'];
                $volumeLabel = $volumeMl.__(' ml + 2 mẫu 5 ml + hộp quà');
                $hasGift = true;
                $hasEngrave = false;
                $engraveText = null;
            } elseif ($isDiscovery) {
                $volumeLabel = __('Hộp Thử Mùi Discovery Box');
                $engraveText = 'Các mùi đã chọn: '.($itemData['sample_names'] ?? '');
            } elseif ($volumeMl === 10) {
                $volumeLabel = __('10ml (Chiết Travel Spray)');
            } elseif ($volumeMl === 50) {
                $volumeLabel = __('50ml (Chai Vừa Phải)');
            } else {
                $volumeLabel = $volumeMl.__('ml (Fullbox Nguyên Seal)');
            }

            return [
                'item_key' => (string) $itemKey,
                'product' => $product,
                'quantity' => $quantity,
                'volume_ml' => $volumeMl,
                'volume_label' => $volumeLabel,
                'is_discovery_box' => $isDiscovery,
                'is_gift_bundle' => $isGiftBundle,
                'sample_names' => $sampleNames,
                'custom_title' => $customTitle,
                'has_gift' => $hasGift,
                'has_engrave' => $hasEngrave,
                'engrave_text' => $engraveText,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $quantity,
                'max_quantity' => CartStockService::limitFor($quoted, array_diff_key($cart, [$itemKey => true])),
            ];
        })->filter()->values();

        $subtotal = $items->sum('line_total');

        return view('cart.index', compact('items', 'subtotal', 'unavailableItems'));
    }

    public function add(Request $request, Perfume $perfume): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'volume_ml' => ['nullable', 'integer'],
            'addon_gift' => ['nullable'],
            'addon_engrave' => ['nullable'],
            'engrave_text' => ['nullable', 'string', 'max:100'],
            'buy_now' => ['nullable', 'boolean'],
            'is_gift_bundle' => ['prohibited'],
            'sample_ids' => ['prohibited'],
        ]);
        if (GiftBundleService::isLegacyMarker($validated['engrave_text'] ?? null)) {
            throw ValidationException::withMessages(['bundle' => __('Vui lòng dùng mục Combo Trọn Vẹn và chọn đủ hai mẫu 5 ml; không thêm combo bằng nội dung khắc chữ.')]);
        }

        $volumeMl = (int) ($request->input('volume_ml') ?: ($perfume->volume_ml ?: 100));
        app(CartQuoteService::class)->unitPrice($perfume, $volumeMl);
        $availableStock = $perfume->getStockForVolume($volumeMl);

        if (! $perfume->is_active || $availableStock < (int) $validated['quantity']) {
            return back()->withErrors(['quantity' => "Dung tích {$volumeMl}ml hiện đang hết hàng."]);
        }

        $hasGift = $request->filled('addon_gift') && $request->input('addon_gift') !== '0';
        $engraveText = $request->filled('engrave_text') ? trim((string) $request->input('engrave_text')) : null;
        $hasEngrave = ($request->filled('addon_engrave') && $request->input('addon_engrave') !== '0') || ($engraveText !== null && $engraveText !== '');

        $unitPrice = app(CartQuoteService::class)->unitPrice($perfume, $volumeMl, $hasGift);

        $liveProduct = $request->session()->get('live_product', []);
        $clickedAt = (int) ($liveProduct['clicked_at'] ?? 0);
        $livestreamId = (int) ($liveProduct['perfume_id'] ?? 0) === $perfume->id
            && $clickedAt <= now()->timestamp
            && $clickedAt >= now()->subHours(2)->timestamp
            ? (int) ($liveProduct['livestream_id'] ?? 0) : null;

        $isStandardDefault = ! $livestreamId && ! $hasGift && ! $hasEngrave && (! $request->has('volume_ml') || $volumeMl == ($perfume->volume_ml ?: 100));

        if ($isStandardDefault) {
            $itemKey = (string) $perfume->id;
        } else {
            $itemKey = 'item_'.$perfume->id.'_'.$volumeMl.($hasGift ? '_gift' : '').($engraveText ? '_'.md5($engraveText) : '').($livestreamId ? '_live_'.$livestreamId : '');
        }

        $cart = $request->session()->get('cart', []);

        $availableStock = CartStockService::remaining($perfume, $volumeMl, array_diff_key($cart, [$itemKey => true]));
        $existing = $cart[$itemKey] ?? 0;
        $existingQuantity = (int) (is_array($existing) ? ($existing['quantity'] ?? 0) : $existing);
        $desiredQuantity = (int) $validated['quantity'] + ($request->boolean('buy_now') ? 0 : $existingQuantity);
        if ($desiredQuantity > min(999, $availableStock)) {
            return back()->withErrors(['quantity' => "Dung tích {$volumeMl}ml chỉ có thể chọn tối đa {$availableStock} ở dòng này, sau khi tính các món khác trong giỏ."]);
        }

        if ($request->boolean('buy_now')) {
            if ($isStandardDefault) {
                $cart[$itemKey] = $validated['quantity'];
            } else {
                $cart[$itemKey] = [
                    'item_key' => $itemKey,
                    'perfume_id' => $perfume->id,
                    'quantity' => $validated['quantity'],
                    'volume_ml' => $volumeMl,
                    'has_gift' => $hasGift,
                    'has_engrave' => $hasEngrave,
                    'engrave_text' => $engraveText,
                    'unit_price' => $unitPrice,
                    'livestream_id' => $livestreamId,
                ];
            }
            $request->session()->put('cart', $cart);

            return redirect()->route('cart.index');
        }

        // Khi nhấn Thêm vào giỏ hàng
        $existingQty = 0;
        if (isset($cart[$itemKey])) {
            $existingQty = is_array($cart[$itemKey]) ? ($cart[$itemKey]['quantity'] ?? 0) : (int) $cart[$itemKey];
        }

        $newQuantity = $existingQty + $validated['quantity'];
        if ($newQuantity > $availableStock) {
            return back()->withErrors(['quantity' => "Số lượng chọn vượt quá tồn kho hiện có của dung tích {$volumeMl}ml (còn {$availableStock} chai)."]);
        }

        if ($isStandardDefault) {
            $cart[$itemKey] = $newQuantity;
        } else {
            $cart[$itemKey] = [
                'item_key' => $itemKey,
                'perfume_id' => $perfume->id,
                'quantity' => $newQuantity,
                'volume_ml' => $volumeMl,
                'has_gift' => $hasGift,
                'has_engrave' => $hasEngrave,
                'engrave_text' => $engraveText,
                'unit_price' => $unitPrice,
                'livestream_id' => $livestreamId,
            ];
        }

        $request->session()->put('cart', $cart);

        return back()->with('success', __('Đã thêm sản phẩm vào giỏ hàng.'));
    }

    public function update(Request $request, string $itemKey): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $cart = $request->session()->get('cart', []);

        if (! isset($cart[$itemKey])) {
            return back()->withErrors(['quantity' => __('Sản phẩm không tồn tại trong giỏ.')]);
        }

        $candidate = is_array($cart[$itemKey]) ? array_replace($cart[$itemKey], ['quantity' => 1]) : 1;
        $quoted = app(CartQuoteService::class)->quote([$itemKey => $candidate])['items'][0];
        $availableStock = CartStockService::limitFor($quoted, array_diff_key($cart, [$itemKey => true]));
        if ((int) $validated['quantity'] > $availableStock) {
            return back()->withErrors(['quantity' => "Sản phẩm hoặc hộp thử này chỉ có thể chọn tối đa {$availableStock}, sau khi tính các món khác trong giỏ."]);
        }
        if (is_array($cart[$itemKey])) {
            $cart[$itemKey]['quantity'] = $validated['quantity'];
        } else {
            $cart[$itemKey] = $validated['quantity'];
        }

        $request->session()->put('cart', $cart);

        return back()->with('success', __('Đã cập nhật số lượng.'));
    }

    public function remove(Request $request, string $itemKey): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$itemKey]);
        $request->session()->put('cart', $cart);

        return back()->with('success', __('Đã xóa sản phẩm khỏi giỏ hàng.'));
    }

    public function checkout(Request $request): RedirectResponse
    {
        if (is_string($request->input('phone'))) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string) $request->phone);
            if (str_starts_with($cleanPhone, '84') && strlen($cleanPhone) === 11) {
                $cleanPhone = '0'.substr($cleanPhone, 2);
            }
            $request->merge(['phone' => $cleanPhone]);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['bail', 'required', 'string', 'regex:/^0[0-9]{9}$/'],
            'address' => ['required', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
            'gift_wrap' => ['nullable', 'string', 'max:100'],
            'gift_card' => ['nullable', 'string', 'max:100'],
            'gift_message' => ['nullable', 'string', 'max:1000'],
            'gift_delivery_date' => ['nullable', 'date'],
        ], [
            'customer_name.required' => __('Vui lòng nhập họ và tên.'),
            'phone.required' => __('Vui lòng nhập số điện thoại.'),
            'phone.regex' => __('Số điện thoại chỉ được gồm đúng 10 chữ số (bắt đầu bằng số 0).'),
            'address.required' => __('Vui lòng nhập địa chỉ nhận hàng.'),
        ]);

        $cart = $request->session()->get('cart', []);

        if ($cart === []) {
            return back()->withErrors(['cart' => __('Giỏ hàng đang trống.')]);
        }

        $selectedCart = CheckoutSelectionService::forRequest($request, $cart);
        $selectedKeys = array_keys($selectedCart);

        $quote = app(CartQuoteService::class)->quote($selectedCart);
        $totalPrice = $quote['total'];
        $orderItemsData = $quote['items'];

        DB::transaction(function () use ($validated, $totalPrice, $orderItemsData) {
            // Create Order
            $order = Order::create([
                'user_id' => auth()->id(),
                'inventory_status' => 'unreserved',
                'is_demo' => DemoMode::enabled(),
                'shipping_status' => 'pending',
                'customer_name' => $validated['customer_name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'note' => $validated['note'] ?? null,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'gift_wrap' => $validated['gift_wrap'] ?? null,
                'gift_card' => $validated['gift_card'] ?? null,
                'gift_message' => $validated['gift_message'] ?? null,
                'gift_delivery_date' => $validated['gift_delivery_date'] ?? null,
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }
            app(OrderInventoryService::class)->reserve($order);
            app(OrderEmailService::class)->placed($order);
        });

        // Xóa những sản phẩm đã chọn thanh toán khỏi giỏ hàng
        foreach ($selectedKeys as $key) {
            unset($cart[$key]);
        }

        if (empty($cart)) {
            $request->session()->forget('cart');
        } else {
            $request->session()->put('cart', $cart);
        }

        return redirect()->route('home')->with(
            'success',
            __('Đặt hàng thành công! Soopi sẽ liên hệ ').$validated['customer_name'].' qua số '.$validated['phone'].'.'
        );
    }

    public function addGiftBundle(Request $request, Perfume $perfume, GiftBundleService $bundles): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'sample_ids' => ['required', 'array', 'size:2'],
            'sample_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);
        $bundle = $bundles->quote($perfume, $validated['sample_ids']);
        $itemKey = 'gift_bundle_'.$perfume->id.'_'.implode('_', $bundle['sample_ids']);
        $cart = $request->session()->get('cart', []);
        $existing = $cart[$itemKey] ?? [];
        $quantity = (int) $validated['quantity'] + (is_array($existing) ? (int) ($existing['quantity'] ?? 0) : 0);
        $limit = CartStockService::limitFor([
            'perfume_id' => $perfume->id, 'volume_ml' => $bundle['volume_ml'], 'stock_components' => $bundle['stock_components'],
        ], array_diff_key($cart, [$itemKey => true]));
        if ($quantity < 1 || $quantity > min(999, $limit)) {
            throw ValidationException::withMessages(['quantity' => "Combo này chỉ có thể chọn tối đa {$limit}, sau khi tính chai chính và hai mẫu thử trong giỏ."]);
        }
        $cart[$itemKey] = [
            'item_key' => $itemKey, 'is_gift_bundle' => true, 'is_discovery_box' => false,
            'perfume_id' => $perfume->id, 'quantity' => $quantity, 'volume_ml' => $bundle['volume_ml'],
            'sample_ids' => $bundle['sample_ids'], 'sample_names' => $bundle['sample_names'],
            'title' => $bundle['product_name'], 'unit_price' => $bundle['price'],
            'has_gift' => true, 'has_engrave' => false, 'engrave_text' => null,
        ];
        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', __('Đã thêm combo nguyên chai, hai mẫu 5 ml và hộp quà vào giỏ hàng.'));
    }

    public function addDiscoveryBox(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'size' => ['required', 'in:3,5'],
            'perfume_ids' => ['required', 'array', 'min:3', 'max:5'],
            'perfume_ids.*' => ['required', 'integer', 'distinct', 'exists:perfumes,id'],
        ]);

        $size = (int) $validated['size'];
        $selectedIds = array_map('intval', $validated['perfume_ids']);
        if (count($selectedIds) !== $size) {
            return back()->withInput()->withErrors(['discovery' => __('Vui lòng chọn đúng số mẫu chiết cho Hộp thử mùi.')]);
        }

        $perfumes = Perfume::whereIn('id', $selectedIds)->where('is_active', true)->get();
        if ($perfumes->count() !== $size) {
            return back()->withInput()->withErrors(['discovery' => __('Có mẫu thử đã ngừng bán. Vui lòng chọn lại.')]);
        }
        $cart = $request->session()->get('cart', []);
        DiscoveryBoxService::assertAvailable($perfumes, $cart);
        $byId = $perfumes->keyBy('id');
        $perfumes = collect($selectedIds)->map(fn ($id) => $byId->get($id));
        $price = $size === 5 ? 299000 : 199000;
        $names = $perfumes->pluck('name')->join(', ');

        $itemKey = 'discovery_box_'.Str::random(8);
        $cart[$itemKey] = [
            'item_key' => $itemKey,
            'is_discovery_box' => true,
            'title' => "Hộp Thử Mùi Discovery Box ({$size} Mẫu Chiết)",
            'sample_names' => $names,
            'sample_ids' => $selectedIds,
            'perfume_id' => $perfumes->first()->id,
            'quantity' => 1,
            'volume_ml' => 5,
            'unit_price' => $price,
            'has_gift' => true,
            'engrave_text' => "Discovery Box ({$size} mẫu: {$names})",
        ];

        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', "Đã thêm Hộp Thử Mùi Discovery Box ({$size} mẫu) vào giỏ hàng!");
    }
}
