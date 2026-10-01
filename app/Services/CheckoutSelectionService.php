<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CheckoutSelectionService
{
    /** An explicit empty or stale selection must never fall back to the whole cart. */
    public static function forRequest(Request $request, array $cart, bool $restoreInput = false): array
    {
        $input = $restoreInput && $request->session()->hasOldInput('selection')
            ? ['selection' => $request->old('selection'), 'selected_items' => $request->old('selected_items', [])]
            : $request->only('selection', 'selected_items');
        $data = Validator::make($input, [
            'selection' => ['nullable', 'boolean'],
            'selected_items' => ['required_if:selection,1', 'array', 'min:1', 'max:200'],
            'selected_items.*' => ['required', 'regex:/^[A-Za-z0-9_-]+$/', 'distinct'],
        ], [
            'selected_items.required_if' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.',
            'selected_items.min' => 'Vui lòng chọn ít nhất một sản phẩm để thanh toán.',
            'selected_items.*' => 'Lựa chọn sản phẩm không hợp lệ. Vui lòng chọn lại từ giỏ hàng.',
        ])->validate();

        if (! array_key_exists('selected_items', $data)) {
            return $cart;
        }
        $keys = array_map('strval', $data['selected_items']);
        if (array_diff($keys, array_map('strval', array_keys($cart)))) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng đã thay đổi. Vui lòng chọn lại sản phẩm muốn thanh toán.']);
        }

        return array_intersect_key($cart, array_flip($keys));
    }
}
