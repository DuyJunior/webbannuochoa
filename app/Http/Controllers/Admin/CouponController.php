<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    public function index()
    {
        return view('admin.coupons.index', ['coupons' => Coupon::latest()->paginate(20)]);
    }

    public function store(Request $request)
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'code' => 'required|string|alpha_dash|max:30|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|integer|min:1',
            'minimum_order' => 'required|integer|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);
        $data['code'] = Str::upper($data['code']);
        if ($data['type'] === 'percent' && $data['value'] > 100) {
            return back()->withErrors(['value' => 'Mức giảm theo phần trăm không được vượt 100%.'])->withInput();
        }
        Coupon::create($data + ['is_active' => true]);
        return back()->with('success', 'Đã tạo mã ưu đãi.');
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);
        return back()->with('success', 'Đã cập nhật trạng thái mã ưu đãi.');
    }
}
