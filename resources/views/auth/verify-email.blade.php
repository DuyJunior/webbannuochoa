@extends('layouts.store')

@section('title', 'Xác thực Email · Ha Thu Perfume')

@section('content')
<section class="luxury-auth-section">
    <div class="luxury-auth-card" style="max-width: 480px; text-align: center;">
        <div class="luxury-auth-header">
            <div class="luxury-auth-icon-wrap" style="width: 64px; height: 64px; font-size: 30px;">
                @include('partials.icon', ['name' => 'mail', 'size' => 27])
            </div>
            <h1 class="luxury-auth-title">Xác thực Email</h1>
            <p class="luxury-auth-subtitle" style="margin-top: 8px;">
                Vui lòng kiểm tra hộp thư và nhấn vào liên kết xác thực. Nếu liên kết cũ đã hết hạn, bạn có thể yêu cầu gửi lại bên dưới.
            </p>
        </div>

        @if (session('message'))
            <div class="luxury-auth-alert alert-success" role="alert" style="text-align: left;">
                <span>✓</span>
                <div>{{ session('message') }}</div>
            </div>
        @endif

        @if (session('success'))
            <div class="luxury-auth-alert alert-success" role="alert" style="text-align: left;">
                <span>✓</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 24px;">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="luxury-auth-btn">
                    <span>Gửi lại email xác thực</span>
                    <span>✉️</span>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="luxury-auth-secondary-btn">
                    <span>🚪 Đăng xuất</span>
                </button>
            </form>
        </div>
    </div>
</section>
@endsection
