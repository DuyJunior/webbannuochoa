@extends('layouts.store')

@section('title', 'Đăng nhập · Soopi')

@section('content')
<section class="luxury-auth-section">
    <div class="luxury-auth-card">
        <div class="luxury-auth-header">
            <div class="luxury-auth-icon-wrap">
                @include('partials.icon', ['name' => 'flower', 'size' => 27])
            </div>
            <h1 class="luxury-auth-title">Đăng nhập</h1>
            <p class="luxury-auth-subtitle">Chào mừng bạn quay trở lại với Soopi</p>
        </div>

        @if (session('success'))
            <div class="luxury-auth-alert alert-success" role="alert">
                <span>✓</span>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="luxury-auth-alert alert-danger" role="alert">
                <span>✕</span>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="luxury-auth-alert alert-danger" role="alert">
                <span>⚠️</span>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="luxury-auth-form" action="{{ route('login') }}" method="POST">
            @csrf

            <div class="luxury-form-group">
                <label for="email" class="luxury-form-label">
                    <span>Địa chỉ Email</span>
                </label>
                <div class="luxury-input-wrap">
                    <span class="luxury-input-icon">@include('partials.icon', ['name' => 'mail', 'size' => 17])</span>
                    <input type="email"
                           name="email" autocomplete="email"
                           id="email"
                           class="luxury-form-input"
                           placeholder="example@gmail.com"
                           required
                           value="{{ old('email') }}"
                           autofocus>
                </div>
            </div>

            <div class="luxury-form-group">
                <label for="password" class="luxury-form-label">
                    <span>Mật khẩu</span>
                </label>
                <div class="luxury-input-wrap">
                    <span class="luxury-input-icon">@include('partials.icon', ['name' => 'lock', 'size' => 17])</span>
                    <input type="password"
                           name="password" autocomplete="current-password"
                           id="password"
                           class="luxury-form-input"
                           placeholder="••••••••"
                           required>
                </div>
            </div>

            <button type="submit" class="luxury-auth-btn">
                <span>Đăng nhập ngay</span>
                <span>→</span>
            </button>
        </form>

        <div class="luxury-auth-footer">
            Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký tài khoản mới</a>
        </div>
    </div>
</section>
@endsection
