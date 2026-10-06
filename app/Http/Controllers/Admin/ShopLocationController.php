<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopLocation;
use App\Services\ShopLocationService;
use Illuminate\Http\Request;

class ShopLocationController extends Controller
{
    public function edit(ShopLocationService $locations)
    {
        return view('admin.shop-location.edit', ['location' => $locations->current()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-85,85'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'hours' => ['nullable', 'string', 'max:160'],
            'is_demo' => ['required', 'boolean'],
        ]);
        ShopLocation::updateOrCreate(['id' => 1], $data);
        return redirect()->route('admin.shop-location.edit')
            ->with('success', 'Đã cập nhật vị trí cửa hàng trên trang chủ, trang liên hệ và chân trang.');
    }
}
