<!DOCTYPE html>
<html lang="vi" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#fff6f8">

    {{-- SEO: Title & Description --}}
    <title>@yield('title', 'Soopi · Hương thơm của riêng bạn')</title>
    <meta name="description" content="@yield('meta_description', 'Soopi — Khám phá nước hoa chính hãng 100%. Giao hàng toàn quốc, đóng gói 3 lớp, hỗ trợ đổi trả trong 7 ngày.')">
    <meta name="keywords" content="@yield('meta_keywords', 'nước hoa chính hãng, nước hoa nữ, nước hoa nam, Dior, Chanel, YSL, nước hoa Soopi')">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph (Facebook / Zalo share) --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Soopi">
    <meta property="og:title" content="@yield('title', 'Soopi · Hương thơm của riêng bạn')">
    <meta property="og:description" content="@yield('meta_description', 'Soopi — Nước hoa chính hãng, giao toàn quốc.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/perfume-hero.jpg'))">
    <meta property="og:locale" content="vi_VN">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Soopi · Hương thơm của riêng bạn')">
    <meta name="twitter:description" content="@yield('meta_description', 'Soopi — Nước hoa chính hãng, giao toàn quốc.')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/perfume-hero.jpg'))">

    {{-- Structured Data: Local Business --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Store',
        'name' => 'Soopi',
        'description' => 'Cửa hàng nước hoa chính hãng, đa dạng thương hiệu quốc tế.',
        'url' => config('app.url'),
        'image' => asset('images/perfume-hero.jpg'),
        'priceRange' => '₫₫',
        'currenciesAccepted' => 'VND',
        'paymentAccepted' => 'Cash, Credit Card, MoMo',
        'areaServed' => 'VN',
        'sameAs' => [
            'https://facebook.com/hathu.perfume',
            'https://instagram.com/hathu.perfume',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    <link rel="icon" href="{{ asset('images/ha-thu-mark.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,600&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap&subset=vietnamese">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @unless(request()->routeIs('livestream.show'))<link rel="stylesheet" href="{{ asset('css/livestream-follow.css') }}">@endunless
    @stack('styles')
</head>

<body class="store-page boutique-store" style="--ht-auth-image:url('{{ asset('images/products/narciso-musc-noir-rose.jpg') }}')">
    @include('partials.store-header')
    <main class="public-main" id="main-content" tabindex="-1">
        @if(session('success') && !request()->routeIs('login'))<div class="store-container public-flash" role="status">✓ {{ session('success') }}</div>@endif
        @if(session('error'))<div class="store-container public-flash alert-danger" role="alert">{{ session('error') }}</div>@endif
        @if(\App\Support\DemoMode::enabled())
            <div role="status" style="padding:10px 20px;text-align:center;background:#fff3d9;color:#734b13;font-size:13px">
                BẢN DEMO BÀI TẬP · Không thu tiền thật · Giao hàng mô phỏng · AI cần Internet
            </div>
        @endif
        @yield('content')
    </main>
    @include('partials.store-footer')
    @unless(request()->routeIs('livestream.show'))
        <aside id="live-follow" class="ht-live-follow" hidden aria-label="Livestream thu nhỏ"
               data-state-url="{{ route('livestream.state') }}"
               data-live-url="{{ route('livestream.show') }}"
               data-library-url="{{ asset('vendor/livekit-client/livekit-client.umd.js') }}">
            <div class="ht-live-follow-top">
                <span>● ĐANG TRỰC TIẾP</span>
                <button id="live-follow-close" type="button" aria-label="Đóng video livestream">×</button>
            </div>
            <div class="ht-live-follow-stage">
                <video id="live-follow-video" autoplay muted playsinline hidden></video>
                <audio id="live-follow-audio" autoplay></audio>
                <iframe id="live-follow-youtube" title="Livestream Soopi" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen hidden></iframe>
                <p id="live-follow-message" role="status">Đang kết nối lại buổi live...</p>
            </div>
            <div class="ht-live-follow-bottom">
                <strong id="live-follow-title">Soopi đang livestream</strong>
                <div><button id="live-follow-sound" type="button" hidden>Bật tiếng</button><a href="{{ route('livestream.show') }}">Xem lớn ↗</a></div>
            </div>
        </aside>
        <script defer src="{{ asset('js/livestream-follow.js') }}"></script>
    @endunless
    @unless(request()->routeIs('livestream.*'))
        @unless(request()->routeIs('account.*', 'payment.*', 'user.payment.*'))
            @include('partials.social-float')
            @include('partials.discount-popup')
            @include('partials.lucky-wheel')
        @endunless
        @include('partials.chat_popup')
        @include('partials.video_modal')
    @endunless
    {{-- Shared theme follows the existing page-specific styles. --}}
    @vite('resources/css/boutique.css')
    <link rel="stylesheet" href="{{ asset('css/store-experience.css') }}">
    @stack('scripts')
</body>
</html>
