<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Đăng nhập Quản trị · Soopi') }}</title>
    @include('partials.brand-favicon')
    @include('partials.localization')
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    @vite('resources/css/admin-boutique.css')
</head>
<body class="boutique-admin-login studio-login-page">
    <main class="studio-login-shell">
        <section class="studio-login-art" aria-label="Soopi Fragrance Studio">
            <img src="{{ asset('images/admin/unsplash-perfume.jpg') }}" width="1000" height="1500" alt="{{ __('Nước hoa Prada Candy cùng hoa giấy trắng trên nền hồng') }}" fetchpriority="high">
            <a href="{{ route('home') }}" class="studio-login-brand" aria-label="{{ __('Soopi · Về cửa hàng') }}">@include('partials.brand-logo', ['class' => 'studio-login-logo'])</a>
            <div class="studio-login-quote"><span class="studio-eyebrow">THE ART OF FRAGRANCE</span><h1>{{ __('Tinh tế trong') }}<br>{{ __('từng dấu ấn.') }}</h1><a href="https://unsplash.com/photos/MoPGCgHPS6I" target="_blank" rel="noopener noreferrer">{{ __('Ảnh: Dário Gomes / Unsplash') }}</a></div>
        </section>
        <section class="studio-login-form">
            @include('partials.language-switcher', ['languageClass' => 'language-login'])
            <span class="studio-eyebrow">SOOPI MANAGEMENT STUDIO</span>
            <h2>{{ __('Chào mừng trở lại.') }}</h2>
            <p>{{ __('Đăng nhập để chăm sóc cửa hàng của bạn.') }}</p>
            @if($errors->any() || session('error'))
                <div class="studio-login-error" role="alert">
                    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    @if(session('error'))<div>{{ session('error') }}</div>@endif
                </div>
            @endif
            <form method="POST" action="{{ route('admin.login.post') }}">
                @csrf
                <label for="email">{{ __('Email quản trị viên / nhân viên') }}</label>
                <input type="email" name="email" id="email" value="{{ is_string(old('email')) ? old('email') : '' }}" placeholder="email@cuahang.com" autocomplete="username" required @error('email') aria-invalid="true" @enderror>
                <label for="password">{{ __('Mật khẩu') }}</label>
                <input type="password" name="password" id="password" placeholder="{{ __('Nhập mật khẩu của bạn') }}" autocomplete="current-password" required>
                <button type="submit">{{ __('Đăng nhập hệ thống') }} <span aria-hidden="true">→</span></button>
            </form>
            <a class="studio-login-back" href="{{ route('password.request') }}">{{ __('Quên mật khẩu?') }}</a>
            <a class="studio-login-back" href="{{ route('home') }}">{{ __('← Quay lại cửa hàng') }}</a>
            <footer>{{ __('Dành cho quản trị viên và nhân viên được cấp quyền.') }}</footer>
        </section>
    </main>
</body>
</html>
