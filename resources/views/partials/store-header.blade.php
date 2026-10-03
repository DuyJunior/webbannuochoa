<a class="ht-skip-link" href="#main-content">{{ __('Đến nội dung chính') }}</a>
<header class="ht-header soopi-header">
    <div class="sn-bar">
        <a class="sn-brand" href="{{ route('home') }}" aria-label="{{ __('Soopi — Trang chủ') }}">
            @include('partials.brand-logo', ['class' => 'sn-logo'])
        </a>
        <nav class="sn-nav" aria-label="{{ __('Điều hướng chính') }}">
            <button type="button" data-nav-panel="sn-collection" aria-controls="sn-collection" aria-expanded="false">{{ __('Bộ sưu tập') }} @include('partials.icon', ['name' => 'chevron', 'size' => 13])</button>
            <a href="{{ route('store.finder') }}" @if(request()->routeIs('store.finder')) aria-current="page" @endif>{{ __('Chọn hương') }}</a>
            <a href="{{ route('store.journal') }}" @if(request()->routeIs('store.journal')) aria-current="page" @endif>{{ __('Câu chuyện') }}</a>
            <a href="{{ route('livestream.show') }}" @if(request()->routeIs('livestream.show')) aria-current="page" @endif>Live</a>
        </nav>
        <div class="sn-tools">
            @include('partials.language-switcher', ['languageClass' => 'language-desktop'])
            <button class="sn-search-toggle" type="button" data-nav-panel="sn-search" aria-controls="sn-search" aria-expanded="false" aria-label="{{ __('Tìm mùi hương') }}">@include('partials.icon', ['name' => 'search', 'size' => 18])<span>{{ __('Tìm một mùi hương') }}</span></button>
            @auth
                <details class="ht-account">
                    <summary class="ht-header-action" aria-label="{{ __('Mở menu tài khoản') }}">@include('partials.icon', ['name' => 'user']) <span>{{ __('Tài khoản') }}</span></summary>
                    <div class="ht-account-menu">
                        <a class="ht-account-profile" href="{{ route('account.edit') }}">
                            <span class="ht-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
                            <span class="ht-account-identity"><strong>{{ Auth::user()->name }}</strong><small>{{ __('Hồ sơ & mật khẩu') }}</small></span>
                            @include('partials.icon', ['name' => 'external', 'size' => 16])
                        </a>
                        <div class="ht-account-grid">
                            <a href="{{ route('orders.index') }}">@include('partials.icon', ['name' => 'bag', 'size' => 17]) <span>{{ __('Đơn mua') }}</span></a>
                            <a href="{{ route('store.wishlist') }}">@include('partials.icon', ['name' => 'heart', 'size' => 17]) <span>{{ __('Yêu thích') }}</span></a>
                            <a href="{{ route('store.wardrobe') }}">@include('partials.icon', ['name' => 'bottle', 'size' => 17]) <span>{{ __('Tủ nước hoa') }}</span></a>
                            <a href="{{ route('store.member') }}">@include('partials.icon', ['name' => 'crown', 'size' => 17]) <span>{{ __('Thành viên') }}</span></a>
                        </div>
                        <a class="ht-account-tracking" href="{{ route('orders.tracking') }}">@include('partials.icon', ['name' => 'truck', 'size' => 17]) {{ __('Tra cứu đơn hàng') }} @include('partials.icon', ['name' => 'arrow', 'size' => 14])</a>
                        @if(Auth::user()->canManageLivestreams())
                            <div class="ht-account-staff">
                                <span class="ht-account-section-label">{{ __('Quản lý cửa hàng') }}</span>
                                <div class="ht-account-grid">
                                    @if(Auth::user()->role === 'admin')<a href="{{ route('admin.dashboard') }}">@include('partials.icon', ['name' => 'building', 'size' => 17]) <span>{{ __('Quản trị') }}</span></a>@endif
                                    <a href="{{ route('admin.livestreams.index') }}" aria-label="{{ __('Quản lý livestream') }}">@include('partials.icon', ['name' => 'broadcast', 'size' => 17]) <span>Livestream</span></a>
                                </div>
                            </div>
                        @endif
                        <form class="ht-account-logout" method="POST" action="{{ route('logout') }}">@csrf<button type="submit">@include('partials.icon', ['name' => 'logout', 'size' => 16]) {{ __('Đăng xuất') }}</button></form>
                    </div>
                </details>
            @else
                <a class="ht-header-action" href="{{ route('login') }}" aria-label="{{ __('Đăng nhập tài khoản') }}">@include('partials.icon', ['name' => 'user']) <span>{{ __('Tài khoản') }}</span></a>
            @endauth
            <a class="ht-header-action ht-cart-link" href="{{ route('cart.index') }}" aria-label="{{ __('Xem giỏ hàng') }}">
                @include('partials.icon', ['name' => 'bag'])<span>{{ __('Giỏ hàng') }}</span>
                <span class="ht-cart-count">{{ collect(session('cart', []))->sum(fn($item) => is_array($item) ? ($item['quantity'] ?? 0) : (int)$item) }}</span>
            </a>
            <button class="sn-mobile-toggle" type="button" data-nav-panel="sn-collection" aria-controls="sn-collection" aria-expanded="false" aria-label="{{ __('Mở menu') }}">@include('partials.icon', ['name' => 'menu'])</button>
        </div>
    </div>
    <section class="sn-panel" id="sn-collection" inert aria-label="{{ __('Bộ sưu tập nước hoa') }}">
        <button class="sn-close" type="button" data-nav-close aria-label="{{ __('Đóng menu') }}">{{ __('×') }}</button>
        <nav class="sn-secondary sn-mobile-links" aria-label="{{ __('Điều hướng chính trên điện thoại') }}">
            <a href="{{ route('store.finder') }}" @if(request()->routeIs('store.finder')) aria-current="page" @endif>{{ __('Chọn hương') }}</a>
            <a href="{{ route('store.journal') }}" @if(request()->routeIs('store.journal')) aria-current="page" @endif>{{ __('Câu chuyện') }}</a>
            <a href="{{ route('livestream.show') }}" @if(request()->routeIs('livestream.show')) aria-current="page" @endif>Live</a>
        </nav>
        @include('partials.language-switcher', ['languageClass' => 'language-mobile'])
        <div class="sn-menu-layout">
            <div class="sn-intro"><span class="sn-kicker">{{ __('KHÁM PHÁ SOOPI') }}</span><h2>{{ __('Chọn hương.') }}<br>{{ __('Chọn chính mình.') }}</h2><a class="sn-line-link" href="{{ route('home') }}#san-pham">{{ __('Toàn bộ bộ sưu tập ↗') }}</a></div>
            <nav class="sn-links" aria-label="{{ __('Danh mục nước hoa') }}">
                <a href="{{ route('home', ['gender' => 'nu']) }}#san-pham" style="--i:1"><small>01</small>{{ __('Dành cho nàng') }}<span>→</span></a>
                <a href="{{ route('home', ['gender' => 'nam']) }}#san-pham" style="--i:2"><small>02</small>{{ __('Dành cho chàng') }}<span>→</span></a>
                <a href="{{ route('home', ['gender' => 'unisex']) }}#san-pham" style="--i:3"><small>03</small>Unisex<span>→</span></a>
                <a href="{{ route('home', ['sort' => 'sale']) }}#san-pham" style="--i:4"><small>04</small>{{ __('Ưu đãi') }}<span>↗</span></a>
            </nav>
            <a class="sn-art" href="{{ route('home') }}#san-pham"><img src="{{ asset('images/bloom/collection-reveal.webp') }}" alt="{{ __('Bộ sưu tập nước hoa giữa những cánh lụa hồng') }}" width="1254" height="1254" loading="lazy"><span>L’ART DU PARFUM ↗</span></a>
        </div>
        <div class="sn-foot"><span>{{ __('MỘT DẤU HƯƠNG. MỘT THẾ GIỚI RIÊNG.') }}</span><button type="button" data-site-motion aria-pressed="true">{{ __('Hiệu ứng: bật') }}</button></div>
    </section>
    <section class="sn-panel sn-search-panel" id="sn-search" inert aria-label="{{ __('Tìm kiếm nước hoa') }}">
        <button class="sn-close" type="button" data-nav-close aria-label="{{ __('Đóng tìm kiếm') }}">{{ __('×') }}</button>
        <div class="sn-search-layout"><div><span class="sn-kicker">{{ __('ĐỂ CẢM XÚC DẪN LỐI') }}</span><h2>{{ __('Bạn đang tìm dấu hương nào?') }}</h2>
            <form class="ht-search sn-search-field" method="GET" action="{{ route('home') }}#san-pham" role="search"><input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Tên nước hoa, thương hiệu…') }}" aria-label="{{ __('Tìm nước hoa hoặc thương hiệu') }}" maxlength="100"><button type="submit" aria-label="{{ __('Tìm kiếm nước hoa') }}">@include('partials.icon', ['name' => 'search'])</button></form>
            <div class="sn-hints"><span>{{ __('Thử một gợi ý') }}</span><button type="button" data-search-query="Dior">Dior</button><button type="button" data-search-query="Chanel">Chanel</button></div>
        </div><div class="sn-suggestions"><a href="{{ route('home', ['search' => 'Dior']) }}#san-pham"><img src="{{ asset('images/gallery/miss-dior.webp') }}" alt="Miss Dior" width="95" height="120" loading="lazy"><span><strong>Dior</strong><small>{{ __('Khám phá thương hiệu ↗') }}</small></span></a><a href="{{ route('home', ['search' => 'Chanel']) }}#san-pham"><img src="{{ asset('images/gallery/chanel.webp') }}" alt="Chanel Chance" width="95" height="120" loading="lazy"><span><strong>Chanel</strong><small>{{ __('Khám phá thương hiệu ↗') }}</small></span></a></div></div>
    </section>
    <noscript><nav class="sn-noscript"><a href="{{ route('home') }}#san-pham">{{ __('Tất cả nước hoa') }}</a><a href="{{ route('store.finder') }}">{{ __('Chọn hương') }}</a><a href="{{ route('store.journal') }}">{{ __('Câu chuyện') }}</a><a href="{{ route('livestream.show') }}">Live</a><form method="GET" action="{{ route('home') }}#san-pham"><input name="search" aria-label="{{ __('Tìm nước hoa') }}"><button>{{ __('Tìm kiếm') }}</button></form></nav></noscript>
</header>
<button class="sn-backdrop" type="button" data-nav-close aria-label="{{ __('Đóng bảng điều hướng') }}" tabindex="-1" hidden></button>
