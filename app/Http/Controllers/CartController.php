<?php

namespace App\Http\Controllers;

use App\Models\Perfume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $items = collect($cart)->map(function ($itemData, $itemKey) use ($products) {
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
            if (! $product) {
                return null;
            }

            if ($volumeMl === 0) {
                $volumeMl = (int) ($product->volume_ml ?: 100);
            }

            if ($unitPrice === null) {
                $basePrice = (float) ($product->sale_price ?? $product->price);
                if ($volumeMl === 10) {
                    $unitPrice = round(($basePrice * 0.22) / 10000) * 10000;
                    if ($unitPrice < 20000) $unitPrice = 20000;
                } elseif ($volumeMl === 50) {
                    $unitPrice = round(($basePrice * 0.65) / 10000) * 10000;
                } else {
                    $unitPrice = $basePrice;
                }
                if ($hasGift) {
                    $unitPrice += 50000;
                }
            }

            $isDiscovery = (bool) ($itemData['is_discovery_box'] ?? false);
            $customTitle = $itemData['title'] ?? null;
            if ($isDiscovery) {
                $volumeLabel = 'Hộp Thử Mùi Discovery Box';
                $engraveText = 'Các mùi đã chọn: ' . ($itemData['sample_names'] ?? '');
            } elseif ($volumeMl === 10) {
                $volumeLabel = '10ml (Chiết Travel Spray)';
            } elseif ($volumeMl === 50) {
                $volumeLabel = '50ml (Chai Vừa Phải)';
            } else {
                $volumeLabel = $volumeMl . 'ml (Fullbox Nguyên Seal)';
            }

            return [
                'item_key' => (string) $itemKey,
                'product' => $product,
                'quantity' => $quantity,
                'volume_ml' => $volumeMl,
                'volume_label' => $volumeLabel,
                'is_discovery_box' => $isDiscovery,
                'custom_title' => $customTitle,
                'has_gift' => $hasGift,
                'has_engrave' => $hasEngrave,
                'engrave_text' => $engraveText,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $quantity,
            ];
        })->filter()->values();

        $subtotal = $items->sum('line_total');

        return view('cart.index', compact('items', 'subtotal'));
    }

    public function add(Request $request, Perfume $perfume): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'volume_ml' => ['nullable', 'integer'],
            'addon_gift' => ['nullable'],
            'addon_engrave' => ['nullable'],
            'engrave_text' => ['nullable', 'string', 'max:100'],
            'buy_now' => ['nullable', 'boolean'],
        ]);

        $volumeMl = (int) ($request->input('volume_ml') ?: ($perfume->volume_ml ?: 100));
        $availableStock = $perfume->getStockForVolume($volumeMl);

        if (! $perfume->is_active || $availableStock < 1) {
            return back()->withErrors(['quantity' => "Dung tích {$volumeMl}ml hiện đang hết hàng."]);
        }

        $hasGift = $request->filled('addon_gift') && $request->input('addon_gift') !== '0';
        $engraveText = $request->filled('engrave_text') ? trim((string) $request->input('engrave_text')) : null;
        $hasEngrave = ($request->filled('addon_engrave') && $request->input('addon_engrave') !== '0') || ($engraveText !== null && $engraveText !== '');

        $basePrice = (float) ($perfume->sale_price ?? $perfume->price);
        if ($volumeMl === 10) {
            $unitPrice = round(($basePrice * 0.22) / 10000) * 10000;
            if ($unitPrice < 20000) $unitPrice = 20000;
        } elseif ($volumeMl === 50) {
            $unitPrice = round(($basePrice * 0.65) / 10000) * 10000;
        } else {
            $unitPrice = $basePrice;
        }

        if ($hasGift) {
            $unitPrice += 50000;
        }

        $liveProduct = $request->session()->get('live_product', []);
        $clickedAt = (int) ($liveProduct['clicked_at'] ?? 0);
        $livestreamId = (int) ($liveProduct['perfume_id'] ?? 0) === $perfume->id
            && $clickedAt <= now()->timestamp
            && $clickedAt >= now()->subHours(2)->timestamp
            ? (int) ($liveProduct['livestream_id'] ?? 0) : null;

        $isStandardDefault = !$livestreamId && !$hasGift && !$hasEngrave && (!$request->has('volume_ml') || $volumeMl == ($perfume->volume_ml ?: 100));

        if ($isStandardDefault) {
            $itemKey = (string) $perfume->id;
        } else {
            $itemKey = 'item_' . $perfume->id . '_' . $volumeMl . ($hasGift ? '_gift' : '') . ($engraveText ? '_' . md5($engraveText) : '') . ($livestreamId ? '_live_' . $livestreamId : '');
        }

        $cart = $request->session()->get('cart', []);

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

        return back()->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function update(Request $request, string $itemKey): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $request->session()->get('cart', []);

        if (! isset($cart[$itemKey])) {
            return back()->withErrors(['quantity' => 'Sản phẩm không tồn tại trong giỏ.']);
        }

        if (is_array($cart[$itemKey])) {
            $perfume = Perfume::find($cart[$itemKey]['perfume_id']);
            $vol = (int) ($cart[$itemKey]['volume_ml'] ?? 100);
            $availableStock = $perfume ? $perfume->getStockForVolume($vol) : 0;
            if ($perfume && $validated['quantity'] > $availableStock) {
                return back()->withErrors(['quantity' => "Số lượng chọn vượt quá tồn kho hiện có của dung tích {$vol}ml (còn {$availableStock} chai)."]);
            }
            $cart[$itemKey]['quantity'] = $validated['quantity'];
        } else {
            $perfume = Perfume::find((int) $itemKey);
            $availableStock = $perfume ? $perfume->getStockForVolume() : 0;
            if ($perfume && $validated['quantity'] > $availableStock) {
                return back()->withErrors(['quantity' => 'Số lượng chọn vượt quá tồn kho hiện có.']);
            }
            $cart[$itemKey] = $validated['quantity'];
        }

        $request->session()->put('cart', $cart);

        return back()->with('success', 'Đã cập nhật số lượng.');
    }

    public function remove(Request $request, string $itemKey): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$itemKey]);
        $request->session()->put('cart', $cart);

        return back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        if ($request->has('phone')) {
            $cleanPhone = preg_replace('/[^0-9]/', '', (string)$request->phone);
            if (str_starts_with($cleanPhone, '84') && strlen($cleanPhone) === 11) {
                $cleanPhone = '0' . substr($cleanPhone, 2);
            }
            $request->merge(['phone' => $cleanPhone]);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^0[0-9]{9}$/'],
            'address' => ['required', 'string', 'max:500'],
            'gift_wrap' => ['nullable', 'string', 'max:100'],
            'gift_card' => ['nullable', 'string', 'max:100'],
            'gift_message' => ['nullable', 'string', 'max:1000'],
            'gift_delivery_date' => ['nullable', 'date'],
        ], [
            'customer_name.required' => 'Vui lòng nhập họ và tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại chỉ được gồm đúng 10 chữ số (bắt đầu bằng số 0).',
            'address.required' => 'Vui lòng nhập địa chỉ nhận hàng.',
        ]);

        $cart = $request->session()->get('cart', []);

        if ($cart === []) {
            return back()->withErrors(['cart' => 'Giỏ hàng đang trống.']);
        }

        $selectedKeys = $request->input('selected_items');
        if ($selectedKeys !== null) {
            if (! is_array($selectedKeys) || count($selectedKeys) === 0) {
                return back()->withErrors(['cart' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.']);
            }
            $selectedCart = array_intersect_key($cart, array_flip($selectedKeys));
            if (empty($selectedCart)) {
                return back()->withErrors(['cart' => 'Vui lòng chọn ít nhất một sản phẩm hợp lệ để thanh toán.']);
            }
        } else {
            $selectedCart = $cart;
            $selectedKeys = array_keys($cart);
        }

        $totalPrice = 0;
        $orderItemsData = [];

        foreach ($selectedCart as $itemKey => $itemData) {
            if (is_array($itemData)) {
                $perfumeId = (int) ($itemData['perfume_id'] ?? 0);
                $quantity = (int) ($itemData['quantity'] ?? 1);
                $volumeMl = (int) ($itemData['volume_ml'] ?? 100);
                $hasGift = (bool) ($itemData['has_gift'] ?? false);
                $engraveText = $itemData['engrave_text'] ?? null;
                $unitPrice = (float) ($itemData['unit_price'] ?? 0);
                $livestreamId = $itemData['livestream_id'] ?? null;
            } else {
                $perfumeId = (int) $itemKey;
                $quantity = (int) $itemData;
                $volumeMl = 100;
                $hasGift = false;
                $engraveText = null;
                $unitPrice = 0;
                $livestreamId = null;
            }

            $product = Perfume::find($perfumeId);
            if (! $product || ! $product->is_active || $product->stock < $quantity) {
                return back()->withErrors(['cart' => 'Một sản phẩm đã hết hàng hoặc không còn đủ số lượng.']);
            }

            if ($volumeMl === 0) {
                $volumeMl = (int) ($product->volume_ml ?: 100);
            }

            if ($unitPrice <= 0) {
                $basePrice = (float) ($product->sale_price ?? $product->price);
                if ($volumeMl === 10) {
                    $unitPrice = round(($basePrice * 0.22) / 10000) * 10000;
                    if ($unitPrice < 20000) $unitPrice = 20000;
                } elseif ($volumeMl === 50) {
                    $unitPrice = round(($basePrice * 0.65) / 10000) * 10000;
                } else {
                    $unitPrice = $basePrice;
                }
                if ($hasGift) {
                    $unitPrice += 50000;
                }
            }

            $lineTotal = $unitPrice * $quantity;
            $totalPrice += $lineTotal;

            $orderItemsData[] = [
                'perfume_id' => $product->id,
                'quantity' => $quantity,
                'price' => $unitPrice,
                'volume_ml' => $volumeMl,
                'addon_gift' => $hasGift,
                'engrave_text' => $engraveText,
                'livestream_id' => $livestreamId,
            ];
        }

        DB::transaction(function () use ($validated, $totalPrice, $orderItemsData) {
            // Create Order
            $order = \App\Models\Order::create([
                'user_id' => auth()->id(), // nullable
                'customer_name' => $validated['customer_name'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'total_price' => $totalPrice,
                'status' => 'pending',
                'gift_wrap' => $validated['gift_wrap'] ?? null,
                'gift_card' => $validated['gift_card'] ?? null,
                'gift_message' => $validated['gift_message'] ?? null,
                'gift_delivery_date' => $validated['gift_delivery_date'] ?? null,
            ]);

            // Save order items & decrement stock
            foreach ($orderItemsData as $itemData) {
                $product = Perfume::query()->lockForUpdate()->find($itemData['perfume_id']);
                if ($product->stock < $itemData['quantity']) {
                    throw ValidationException::withMessages([
                        'cart' => 'Số lượng tồn kho sản phẩm ' . $product->name . ' không đủ.',
                    ]);
                }
                $product->decrement('stock', $itemData['quantity']);

                $order->items()->create($itemData);
            }
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
            'Đặt hàng thành công! Ha Thu Perfume sẽ liên hệ '.$validated['customer_name'].' qua số '.$validated['phone'].'.'
        );
    }

    public function addDiscoveryBox(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'size' => ['required', 'in:3,5'],
            'perfume_ids' => ['required', 'array'],
            'perfume_ids.*' => ['required', 'exists:perfumes,id'],
        ]);

        $size = (int) $validated['size'];
        $selectedIds = array_slice($validated['perfume_ids'], 0, $size);
        if (count($selectedIds) < 3) {
            return back()->withErrors(['discovery' => 'Vui lòng chọn ít nhất 3 mẫu chiết cho Hộp thử mùi.']);
        }

        $perfumes = Perfume::whereIn('id', $selectedIds)->get();
        $price = $size === 5 ? 299000 : 199000;
        $names = $perfumes->pluck('name')->join(', ');

        $itemKey = 'discovery_box_' . \Illuminate\Support\Str::random(8);
        $cart = $request->session()->get('cart', []);

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
