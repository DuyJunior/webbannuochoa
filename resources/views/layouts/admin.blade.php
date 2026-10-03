<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Quản trị')) · Soopi</title>
    @include('partials.brand-favicon')
    @include('partials.localization')
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,600&display=swap&subset=vietnamese" rel="stylesheet">
    @vite(['resources/css/admin-boutique.css', 'resources/js/admin-boutique.js'])
    @yield('styles')
    @vite('resources/css/admin-polish.css')
</head>
<body class="boutique-admin">
    <a class="studio-skip-link" href="#admin-main">{{ __('Đến nội dung chính') }}</a>
    <button type="button" class="ht-admin-backdrop" aria-label="{{ __('Đóng menu quản trị') }}" hidden></button>

    {{-- Sidebar --}}
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="{{ __('Menu quản trị') }}">
        <a href="{{ Auth::user()->role === 'admin' ? route('admin.dashboard') : route('admin.livestreams.index') }}" class="sidebar-brand" aria-label="{{ __('Soopi · Trang quản trị') }}">
            @include('partials.brand-logo', ['class' => 'studio-sidebar-logo', 'light' => true])
        </a>

        <button type="button" class="studio-sidebar-close" aria-label="{{ __('Đóng menu quản trị') }}">@include('partials.icon', ['name' => 'close'])</button>
        @include('partials.admin-navigation')

        <div class="sidebar-footer">
            <div class="admin-user-info">
                <div class="admin-avatar">{{ mb_strtoupper(mb_substr(Auth::user()->name ?? 'A', 0, 1)) }}</div>
                <div class="admin-meta">
                    <div class="name">{{ Auth::user()->name ?? 'Admin' }}</div>
                    <div class="role">{{ Auth::user()->role === 'admin' ? __('Quản trị viên') : __('Nhân viên livestream') }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="btn-sidebar-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> {{ __('Đăng xuất') }}
                </button>
            </form>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="admin-main-wrapper">
        <header class="admin-topbar">
            <div class="topbar-left"><button type="button" class="ht-admin-toggle" aria-label="{{ __('Mở menu quản trị') }}" aria-controls="admin-sidebar" aria-expanded="false">@include('partials.icon', ['name' => 'menu'])<span>Menu</span></button>
                <div>
                    <span class="studio-topbar-kicker">{{ __('SOOPI / QUẢN TRỊ') }}</span><h1>@yield('page_title', __('Quản trị hệ thống'))</h1>
                </div>
            </div>
            <div class="topbar-right">
                @include('partials.language-switcher')
                <button type="button" class="studio-command-open" aria-label="{{ __('Tìm chức năng quản trị') }}" aria-haspopup="dialog" aria-controls="studio-command-menu"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>{{ __('Tìm chức năng') }}</span><kbd>Ctrl K</kbd></button>
                <button type="button" class="studio-motion-toggle" aria-pressed="true" title="{{ __('Bật hoặc tắt hiệu ứng chuyển động') }}">
                    <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i><span>{{ __('Hiệu ứng') }}</span>
                </button>
                <div class="topbar-date">
                    <i class="fa-regular fa-calendar"></i>
                    {{ date('d/m/Y') }}
                </div>
                <a class="studio-store-link" href="{{ route('home') }}" target="_blank" rel="noopener" aria-label="{{ __('Xem cửa hàng trong tab mới') }}"><span>{{ __('Xem cửa hàng') }}</span><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a>
            </div>
        </header>

        <main class="admin-content-area" id="admin-main" tabindex="-1">
            @include('partials.admin-workspace-heading')
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Đóng thông báo') }}"><span>&times;</span></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Đóng thông báo') }}"><span>&times;</span></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mb-4" role="alert" tabindex="-1" data-validation-summary>
                    <strong>{{ __('Chưa thể hoàn tất thao tác. Vui lòng kiểm tra lại:') }}</strong>
                    <ul class="mb-0 mt-2 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    @include('partials.admin-command-menu')
    @include('partials.admin-confirm')

    @include('partials.admin-chat')

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    @yield('scripts')



</body>
</html>
