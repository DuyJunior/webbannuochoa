<a class="ht-skip-link" href="#main-content">Đến nội dung chính</a>
<header class="ht-header">
    <div class="store-container ht-header-main">
        <a class="ht-brand" href="{{ route('home') }}" aria-label="Soopi — Trang chủ">
            <span class="ht-brand-mark">@include('partials.icon', ['name' => 'flower', 'size' => 30])</span>
            <span class="ht-brand-name">Soopi<span>PERFUME STUDIO</span></span>
        </a>
        <form class="ht-search" method="GET" action="{{ route('home') }}#san-pham" role="search">
            @include('partials.icon', ['name' => 'search'])
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Tìm mùi hương dành riêng cho bạn..." aria-label="Tìm nước hoa hoặc thương hiệu">
            <button type="submit" aria-label="Tìm kiếm nước hoa">@include('partials.icon', ['name' => 'arrow', 'size' => 18])</button>
        </form>
        <div class="ht-header-actions">
            @auth
                <details class="ht-account">
                    <summary class="ht-header-action" aria-label="Mở menu tài khoản">@include('partials.icon', ['name' => 'user']) <span>Tài khoản</span></summary>
                    <div class="ht-account-menu">
                        <a class="ht-account-profile" href="{{ route('account.edit') }}">
                            <span class="ht-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                            <span class="ht-account-identity"><strong>{{ Auth::user()->name }}</strong><small>Hồ sơ & mật khẩu</small></span>
                            @include('partials.icon', ['name' => 'external', 'size' => 16])
                        </a>
                        <div class="ht-account-grid">
                            <a href="{{ route('orders.index') }}">@include('partials.icon', ['name' => 'bag', 'size' => 17]) Đơn mua</a>
                            <a href="{{ route('store.wishlist') }}">@include('partials.icon', ['name' => 'heart', 'size' => 17]) Yêu thích</a>
                            <a href="{{ route('store.wardrobe') }}">@include('partials.icon', ['name' => 'bottle', 'size' => 17]) Tủ nước hoa</a>
                            <a href="{{ route('store.member') }}">@include('partials.icon', ['name' => 'crown', 'size' => 17]) Thành viên</a>
                        </div>
                        <a class="ht-account-tracking" href="{{ route('orders.tracking') }}">@include('partials.icon', ['name' => 'truck', 'size' => 17]) Tra cứu đơn hàng @include('partials.icon', ['name' => 'arrow', 'size' => 14])</a>
                        @if(Auth::user()->canManageLivestreams())
                            <div class="ht-account-staff">
                                <span class="ht-account-section-label">Quản lý cửa hàng</span>
                                <div class="ht-account-grid">
                                    @if(Auth::user()->role === 'admin')<a href="{{ route('admin.dashboard') }}">@include('partials.icon', ['name' => 'building', 'size' => 17]) Quản trị</a>@endif
                                    <a href="{{ route('admin.livestreams.index') }}" aria-label="Quản lý livestream">@include('partials.icon', ['name' => 'broadcast', 'size' => 17]) Livestream</a>
                                </div>
                            </div>
                        @endif
                        <form class="ht-account-logout" method="POST" action="{{ route('logout') }}">@csrf<button type="submit">@include('partials.icon', ['name' => 'logout', 'size' => 16]) Đăng xuất</button></form>
                    </div>
                </details>
            @else
                <a class="ht-header-action" href="{{ route('login') }}" aria-label="Đăng nhập tài khoản">@include('partials.icon', ['name' => 'user']) <span>Tài khoản</span></a>
            @endauth
            <a class="ht-header-action ht-cart-link" href="{{ route('cart.index') }}" aria-label="Xem giỏ hàng">
                @include('partials.icon', ['name' => 'bag'])<span>Giỏ hàng</span>
                <span class="ht-cart-count">{{ collect(session('cart', []))->sum(fn($item) => is_array($item) ? ($item['quantity'] ?? 0) : (int)$item) }}</span>
            </a>
        </div>
    </div>
    <div class="ht-nav-border">
        <nav class="store-container ht-nav" aria-label="Danh mục nước hoa">
            <div class="ht-nav-links">
                <a href="{{ route('home') }}" @class(['active' => request()->routeIs('home', 'welcome') && !request()->hasAny(['gender', 'category', 'search', 'sort'])])>Khám phá</a>
                <details class="ht-category-menu ht-nav-products">
                    <summary @class(['active' => request()->routeIs('home', 'welcome') && request()->hasAny(['gender', 'category'])])>Nước hoa @include('partials.icon', ['name' => 'chevron', 'size' => 14])</summary>
                    <div class="ht-category-dropdown">
                        <a href="{{ route('home') }}#san-pham">Tất cả nước hoa <span>{{ $totalPerfumes ?? 0 }}</span></a>
                        <a href="{{ route('home', ['gender' => 'nu']) }}#san-pham">Nước hoa nữ</a>
                        <a href="{{ route('home', ['gender' => 'nam']) }}#san-pham">Nước hoa nam</a>
                        <a href="{{ route('home', ['gender' => 'unisex']) }}#san-pham">Unisex</a>
                        @if(($categories ?? collect())->isNotEmpty())<span class="ht-nav-dropdown-label">Danh mục hương</span>@endif
                        @foreach(($categories ?? []) as $category)
                            <a href="{{ route('home', ['category' => $category->id]) }}#san-pham">{{ $category->name }} <span>{{ $category->perfumes_count ?? 0 }}</span></a>
                        @endforeach
                    </div>
                </details>
                <a href="{{ route('livestream.show') }}" @class(['active' => request()->routeIs('livestream.show')])>Livestream</a>
                <a class="ht-nav-quiz" href="{{ route('store.quiz') }}" @class(['active' => request()->routeIs('store.quiz*')])>Chọn hương</a>
                <a href="{{ route('home', ['sort' => 'sale']) }}#san-pham" @class(['ht-nav-sale', 'active' => request('sort') === 'sale'])>Ưu đãi</a>
                <details class="ht-category-menu ht-nav-more">
                    <summary @class(['active' => request()->routeIs('store.discovery-box', 'store.scent-of-the-day', 'store.compare', 'store.journal', 'store.article', 'store.faq')])><span class="ht-nav-more-desktop">Khám phá thêm</span><span class="ht-nav-more-mobile">Thêm</span> @include('partials.icon', ['name' => 'chevron', 'size' => 14])</summary>
                    <div class="ht-category-dropdown">
                        <a class="ht-nav-mobile-only" href="{{ route('store.quiz') }}">Chọn hương</a>
                        <a class="ht-nav-mobile-only" href="{{ route('home', ['sort' => 'sale']) }}#san-pham">Ưu đãi</a>
                        <a href="{{ route('store.discovery-box') }}">Hộp thử mùi</a>
                        <a href="{{ route('store.scent-of-the-day') }}">Mùi hôm nay</a>
                        <a href="{{ route('store.compare') }}">So sánh nước hoa</a>
                        <a href="{{ route('store.journal') }}">Cẩm nang</a>
                        <a href="{{ route('store.faq') }}">Hỏi đáp</a>
                    </div>
                </details>
            </div>
        </nav>
    </div>
</header>
