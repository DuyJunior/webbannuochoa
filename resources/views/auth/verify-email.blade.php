@extends('layouts.store')

@section('title', __('Xác thực Email · Soopi'))

@section('content')
<section class="luxury-auth-section">
    <div class="luxury-auth-card" style="max-width: 480px; text-align: center;">
        <div class="luxury-auth-header">
            <div class="luxury-auth-icon-wrap">
                @include('partials.brand-mark', ['size' => 32])
            </div>
            <h1 class="luxury-auth-title">{{ __('Xác thực Email') }}</h1>
            <p class="luxury-auth-subtitle" style="margin-top: 8px;">
                {{ __('Vui lòng kiểm tra hộp thư và nhấn vào liên kết xác thực. Nếu liên kết cũ đã hết hạn, bạn có thể yêu cầu gửi lại bên dưới.') }}
            </p>
        </div>

        @if (session('message'))
            <div class="luxury-auth-alert alert-success" role="alert" style="text-align: left;">
                <span>@include('partials.icon', ['name' => 'check', 'size' => '1em'])</span>
                <div>{{ session('message') }}</div>
            </div>
        @endif

        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 24px;">
            @if (\App\Support\DemoMode::enabled() && config('mail.default') === 'log')
                <p>{{ __('DEMO LOCAL: email đang ghi vào log, không gửi ra hộp thư thật. Nút dưới mô phỏng mở liên kết xác thực của tài khoản đang đăng nhập.') }}</p>
                <form method="POST" action="{{ route('verification.demo') }}">
                    @csrf
                    <button class="luxury-auth-btn" type="submit">{{ __('Mở thư xác thực demo') }}</button>
                </form>
            @endif
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="luxury-auth-btn">
                    <span>{{ __('Gửi lại email xác thực') }}</span>
                    <span>@include('partials.icon', ['name' => 'mail', 'size' => '1em'])</span>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="luxury-auth-secondary-btn">
                    <span>@include('partials.icon', ['name' => 'logout', 'size' => '1em']) {{ __('Đăng xuất') }}</span>
                </button>
            </form>
        </div>
    </div>
</section>
@endsection
