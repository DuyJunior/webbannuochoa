<a class="ht-skip-link" href="#main-content">Đến nội dung chính</a>
<div class="ht-announcement">Một mùi hương đẹp. Một dấu ấn riêng. <span>Khám phá thế giới nước hoa cùng Soopi</span></div>
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
                    <summary class="ht-header-action">@include('partials.icon', ['name' => 'user']) <span>Tài khoản</span></summary>
                    <div class="ht-account-menu">
                        <strong>Chào, {{ Auth::user()->name }}</strong>
                        <a href="{{ route('account.edit') }}">Thông tin & đổi mật khẩu</a>
                        <a href="{{ route('orders.index') }}">Đơn mua của tôi</a>
                        <a href="{{ route('store.wardrobe') }}">@include('partials.icon', ['name' => 'gem', 'size' => '1em']) Tủ nước hoa của tôi</a>
                        <a href="{{ route('store.wishlist') }}">Mùi hương yêu thích</a>
                        <a href="{{ route('store.member') }}">@include('partials.icon', ['name' => 'crown', 'size' => '1em']) Thẻ thành viên VIP</a>
                        @if(Auth::user()->role === 'admin')<a href="{{ route('admin.coupons.index') }}">Mã ưu đãi</a>@endif
                        <a href="{{ route('orders.tracking') }}">Tra cứu đơn hàng</a>
                        @if(Auth::user()->role === 'admin')<a href="{{ route('admin.dashboard') }}">Quản trị cửa hàng</a>@endif
                        @if(Auth::user()->canManageLivestreams())<a href="{{ route('admin.livestreams.index') }}">@include('partials.icon', ['name' => 'broadcast', 'size' => '1em']) Quản lý livestream</a>@endif
                        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Đăng xuất</button></form>
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
                <a href="{{ route('livestream.show') }}" @class(['active' => request()->routeIs('livestream.show')])>Livestream</a>
                <a href="{{ route('store.quiz') }}" @class(['active' => request()->routeIs('store.quiz*')])>Trắc nghiệm hương</a>
                <a href="{{ route('store.discovery-box') }}" @class(['active' => request()->routeIs('store.discovery-box')])>@include('partials.icon', ['name' => 'gift', 'size' => '1em']) Hộp thử mùi</a>
                <a href="{{ route('store.scent-of-the-day') }}" @class(['active' => request()->routeIs('store.scent-of-the-day')])>@include('partials.icon', ['name' => 'star', 'size' => '1em']) Mùi hôm nay</a>
                <a href="{{ route('home', ['gender' => 'nu']) }}#san-pham" @class(['active' => request()->routeIs('home', 'welcome') && request('gender') === 'nu'])>Nước hoa nữ</a>
                <a href="{{ route('home', ['gender' => 'nam']) }}#san-pham" @class(['active' => request()->routeIs('home', 'welcome') && request('gender') === 'nam'])>Nước hoa nam</a>
                <a href="{{ route('home', ['gender' => 'unisex']) }}#san-pham" @class(['active' => request()->routeIs('home', 'welcome') && request('gender') === 'unisex'])>Unisex</a>
                <details class="ht-category-menu">
                    <summary>Danh mục @include('partials.icon', ['name' => 'chevron', 'size' => 14])</summary>
                    <div class="ht-category-dropdown">
                        <a href="{{ route('home') }}#san-pham">Tất cả nước hoa <span>{{ $totalPerfumes ?? 0 }}</span></a>
                        @foreach(($categories ?? []) as $category)
                            <a href="{{ route('home', ['category' => $category->id]) }}#san-pham">{{ $category->name }} <span>{{ $category->perfumes_count ?? 0 }}</span></a>
                        @endforeach
                    </div>
                </details>
                <a href="{{ route('home', ['sort' => 'sale']) }}#san-pham" @class(['ht-nav-sale', 'active' => request('sort') === 'sale'])>Ưu đãi</a>
                <a href="{{ route('store.compare') }}" @class(['active' => request()->routeIs('store.compare')])>So sánh</a>
                <a href="{{ route('store.journal') }}">Cẩm nang</a>
                <a href="{{ route('store.faq') }}" @class(['active' => request()->routeIs('store.faq')])>Hỏi đáp</a>
            </div>
        </nav>
    </div>
</header>
