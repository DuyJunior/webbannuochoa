@php
    $navigation = [
        'Không gian làm việc' => [
            ['admin.dashboard', 'admin.dashboard', __('Tổng quan'), 'chart-pie'],
            ['admin.orders.index', 'admin.orders.*', __('Đơn hàng'), 'bag-shopping'],
            ['admin.products.index', 'admin.products.*', __('Sản phẩm'), 'spray-can-sparkles'],
            ['admin.categories.index', 'admin.categories.*', __('Danh mục'), 'layer-group'],
            ['admin.users.index', 'admin.users.*', __('Khách hàng & nhân viên'), 'users'],
        ],
        'Thương hiệu & nội dung' => [
            ['admin.shop-location.edit', 'admin.shop-location.*', __('Vị trí cửa hàng'), 'location-dot'],
            ['admin.coupons.index', 'admin.coupons.*', __('Mã ưu đãi'), 'ticket'],
            ['admin.articles.index', 'admin.articles.*', __('Cẩm nang'), 'book-open'],
            ['admin.videos.index', 'admin.videos.*', 'Video & Shorts', 'clapperboard'],
            ['admin.livestreams.index', 'admin.livestreams.*', 'Livestream', 'video'],
        ],
        'Hiệu quả kinh doanh' => [
            ['admin.reports.index', 'admin.reports.*', __('Báo cáo'), 'chart-line'],
            ['admin.finance.index', 'admin.finance.index', __('Thống kê tài chính'), 'wallet'],
            ['admin.finance.transactions', 'admin.finance.transactions', __('Giao dịch thanh toán'), 'money-bill-transfer'],
        ],
    ];
    if (Auth::user()->role !== 'admin') {
        $navigation = ['Không gian làm việc' => [['admin.livestreams.index', 'admin.livestreams.*', 'Livestream', 'video']]];
    }
@endphp
<nav class="sidebar-menu" aria-label="{{ __('Điều hướng quản trị') }}">
    @foreach($navigation as $group => $links)
        <div class="studio-nav-group">
            <div class="sidebar-section-label">{{ __($group) }}</div>
            @foreach($links as [$route, $pattern, $label, $icon])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif><i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i><span>{{ __($label) }}</span></a>
            @endforeach
        </div>
    @endforeach
</nav>
