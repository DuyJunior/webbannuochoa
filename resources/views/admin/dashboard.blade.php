@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Bảng điều khiển (Dashboard)')

@section('content')
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="admin-card" style="border-left: 4px solid var(--pink-main); background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-size: 2.1rem; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px;">{{ \App\Models\Product::count() }}</div>
                    <div class="text-muted font-weight-500" style="font-size: 0.85rem; margin-top: 2px;">Tổng sản phẩm</div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--pink-soft); color: var(--pink-dark); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; border: 1px solid var(--border);">
                    <i class="fa-solid fa-spray-can-sparkles"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="admin-card" style="border-left: 4px solid #f472b6; background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-size: 2.1rem; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px;">{{ \App\Models\Order::count() }}</div>
                    <div class="text-muted font-weight-500" style="font-size: 0.85rem; margin-top: 2px;">Tổng đơn hàng</div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #fdf2f8; color: #db2777; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; border: 1px solid rgba(244,114,182,.25);">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="admin-card" style="border-left: 4px solid #fb7185; background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-size: 2.1rem; font-weight: 800; color: var(--text-dark); letter-spacing: -0.5px;">{{ \App\Models\Order::where('status', 'pending')->count() }}</div>
                    <div class="text-muted font-weight-500" style="font-size: 0.85rem; margin-top: 2px;">Đơn chờ xử lý</div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: #fff1f2; color: #e11d48; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; border: 1px solid rgba(251,113,133,.25);">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="admin-card" style="border-left: 4px solid var(--pink-dark); background: #ffffff;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div style="font-size: 1.65rem; font-weight: 800; color: var(--text-dark); line-height: 2.2rem;">{{ number_format(\App\Models\Order::where('status', '!=', 'cancelled')->sum('total_price'), 0, ',', '.') }} đ</div>
                    <div class="text-muted font-weight-500" style="font-size: 0.85rem; margin-top: 2px;">Doanh thu ước tính</div>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 12px; background: var(--pink-pale); color: var(--pink-dark); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; border: 1px solid var(--border);">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="admin-card h-100">
            <h5 class="font-weight-bold mb-2" style="color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                <span style="color: var(--pink-main);"><i class="fa-solid fa-compass"></i></span>
                Lối tắt quản trị
            </h5>
            <p class="text-muted mb-4" style="font-size:0.88rem;">Truy cập nhanh các chức năng quản lý danh mục, sản phẩm và đơn hàng.</p>
            
            <div class="d-flex flex-column" style="gap: 12px;">
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-pink text-left py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius: 10px; font-weight: 600;">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-spray-can-sparkles" style="color: var(--pink-dark);"></i>
                        <span>Quản lý danh sách sản phẩm</span>
                    </span>
                    <i class="fa-solid fa-chevron-right" style="font-size: 12px; opacity: 0.7;"></i>
                </a>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-pink text-left py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius: 10px; font-weight: 600;">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-layer-group" style="color: var(--pink-dark);"></i>
                        <span>Quản lý danh mục nước hoa</span>
                    </span>
                    <i class="fa-solid fa-chevron-right" style="font-size: 12px; opacity: 0.7;"></i>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-pink text-left py-3 px-4 d-flex justify-content-between align-items-center" style="border-radius: 10px; font-weight: 600;">
                    <span style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-receipt" style="color: var(--pink-dark);"></i>
                        <span>Quản lý danh sách đơn hàng</span>
                    </span>
                    <i class="fa-solid fa-chevron-right" style="font-size: 12px; opacity: 0.7;"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="admin-card h-100">
            <h5 class="font-weight-bold mb-2" style="color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                <span style="color: var(--pink-dark);"><i class="fa-solid fa-shield-heart"></i></span>
                Thông tin quản trị viên
            </h5>
            <p class="text-muted mb-3" style="font-size:0.88rem;">Tài khoản quản lý cửa hàng Soopi.</p>
            <div class="table-responsive">
                <table class="table table-borderless" style="font-size:0.92rem;">
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="text-muted" width="140" style="padding: 10px 0;">Họ và tên:</td>
                        <td class="font-weight-bold" style="padding: 10px 0; color: var(--text-dark);">{{ Auth::user()->name }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="text-muted" style="padding: 10px 0;">Email:</td>
                        <td class="font-weight-bold" style="padding: 10px 0; color: var(--text-dark);">{{ Auth::user()->email }}</td>
                    </tr>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td class="text-muted" style="padding: 10px 0;">Vai trò:</td>
                        <td style="padding: 10px 0;">
                            <span class="badge" style="background: var(--pink-pale); color: var(--pink-dark); font-weight: 700; padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(232,114,138,.3);">
                                @include('partials.icon', ['name' => 'flower', 'size' => '1em']) QUẢN TRỊ VIÊN
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted" style="padding: 10px 0;">Hệ thống:</td>
                        <td style="padding: 10px 0; font-weight: 600; color: var(--pink-dark);">Soopi (Laravel 11)</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
