<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $search = trim($filters['search'] ?? '');
        $coupons = Coupon::query()
            ->addSelect(['used_count' => Order::selectRaw('count(*)')
                ->whereColumn('coupon_code', 'coupons.code')->where('status', '!=', 'cancelled')])
            ->when($search !== '', fn ($query) => $query->where('code', 'like', '%'.Str::upper($search).'%'))
            ->when(!empty($filters['status']), fn ($query) => $query->where('is_active', $filters['status'] === 'active'))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function store(Request $request)
    {
        if (is_string($request->input('code'))) {
            $request->merge(['code' => Str::upper(trim($request->input('code')))]);
        }
        $data = $request->validate([
            'code' => 'required|string|alpha_dash|max:30|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|integer|min:1|max:4294967295',
            'minimum_order' => 'required|integer|min:0|max:4294967295',
            'usage_limit' => 'nullable|integer|min:1|max:4294967295',
            'starts_at' => 'nullable|date',
            'expires_at' => ['nullable', 'date', ...($request->filled('starts_at') ? ['after:starts_at'] : [])],
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
