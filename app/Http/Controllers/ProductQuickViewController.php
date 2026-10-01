<?php

namespace App\Http\Controllers;

use App\Models\Perfume;
use App\Services\CartQuoteService;
use App\Services\CartStockService;
use App\Services\FragranceEditorialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductQuickViewController extends Controller
{
    public function show(Request $request, Perfume $perfume, CartQuoteService $quotes): JsonResponse
    {
        abort_unless($perfume->is_active, 404);

        $editorial = FragranceEditorialService::forPerfume($perfume);
        $defaultVolume = (int) ($perfume->volume_ml ?: 100);
        $cart = $request->session()->get('cart', []);
        $volumes = array_values(array_unique([$defaultVolume, 50, 10]));
        $variants = array_map(function (int $volume) use ($perfume, $defaultVolume, $cart, $quotes) {
            $stock = max(0, $perfume->getStockForVolume($volume));
            $remaining = CartStockService::remaining($perfume, $volume, $cart);
            $inCart = $stock - $remaining;

            return [
                'volume' => $volume,
                'label' => $volume.' ml',
                'price' => $quotes->unitPrice($perfume, $volume),
                'original_price' => $volume === $defaultVolume && $perfume->sale_price !== null && $perfume->sale_price < $perfume->price
                    ? (int) $perfume->price : null,
                'stock' => $stock,
                'in_cart' => $inCart,
                'max_quantity' => max(0, min($remaining, 999 - $inCart)),
            ];
        }, $volumes);

        $artwork = config('scent-gallery.artwork', [])[$perfume->slug] ?? null;
        $editorialImage = ! $perfume->image_src && $artwork && is_file(public_path($artwork));
        $image = $perfume->image_src ?: ($editorialImage ? asset($artwork) : null);

        return response()->json([
            'id' => $perfume->id,
            'name' => $perfume->name,
            'brand' => $perfume->brand,
            'concentration' => $perfume->concentration,
            'description' => $editorial['verified'] ? Str::limit($editorial['story'], 180) : '',
            'image' => $image,
            'image_is_editorial' => (bool) $editorialImage,
            'variants' => $variants,
            'product_url' => route('perfumes.show', $perfume),
            'cart_url' => route('cart.add', $perfume),
            'login_url' => route('perfumes.quick-view.login', $perfume),
            'authenticated' => $request->user() !== null,
            'csrf_token' => csrf_token(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function login(Request $request, Perfume $perfume): RedirectResponse
    {
        abort_unless($perfume->is_active, 404);

        $productUrl = route('perfumes.show', $perfume);
        if ($request->user()) {
            return redirect()->to($productUrl);
        }

        // Save an internal destination only after the customer explicitly asks to sign in.
        $request->session()->put('url.intended', $productUrl);

        return redirect()->route('login');
    }
}
