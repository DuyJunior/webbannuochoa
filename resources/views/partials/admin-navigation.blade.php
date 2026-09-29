@php
    $navigation = [
        'Không gian làm việc' => [
            ['admin.dashboard', 'admin.dashboard', 'Tổng quan', 'chart-pie'],
            ['admin.orders.index', 'admin.orders.*', 'Đơn hàng', 'bag-shopping'],
            ['admin.products.index', 'admin.products.*', 'Sản phẩm', 'spray-can-sparkles'],
            ['admin.categories.index', 'admin.categories.*', 'Danh mục', 'layer-group'],
            ['admin.users.index', 'admin.users.*', 'Khách hàng & nhân viên', 'users'],
        ],
        'Thương hiệu & nội dung' => [
            ['admin.coupons.index', 'admin.coupons.*', 'Mã ưu đãi', 'ticket'],
            ['admin.articles.index', 'admin.articles.*', 'Cẩm nang', 'book-open'],
            ['admin.videos.index', 'admin.videos.*', 'Video & Shorts', 'clapperboard'],
            ['admin.livestreams.index', 'admin.livestreams.*', 'Livestream', 'video'],
        ],
        'Hiệu quả kinh doanh' => [
            ['admin.reports.index', 'admin.reports.*', 'Báo cáo', 'chart-line'],
            ['admin.finance.index', 'admin.finance.index', 'Thống kê tài chính', 'wallet'],
            ['admin.finance.transactions', 'admin.finance.transactions', 'Giao dịch thanh toán', 'money-bill-transfer'],
        ],
    ];
    if (Auth::user()->role !== 'admin') {
        $navigation = ['Không gian làm việc' => [['admin.livestreams.index', 'admin.livestreams.*', 'Livestream', 'video']]];
    }
@endphp
<nav class="sidebar-menu" aria-label="Điều hướng quản trị">
    @foreach($navigation as $group => $links)
        <div class="studio-nav-group">
            <div class="sidebar-section-label">{{ $group }}</div>
            @foreach($links as [$route, $pattern, $label, $icon])
                <a href="{{ route($route) }}" class="{{ request()->routeIs($pattern) ? 'active' : '' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif><i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i><span>{{ $label }}</span></a>
            @endforeach
        </div>
    @endforeach
</nav>
