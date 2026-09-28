@extends('layouts.store')

@section('title', 'Đăng ký tài khoản · Soopi')

@section('content')
<section class="luxury-auth-section">
    <div class="luxury-auth-card" style="max-width: 500px;">
        <div class="luxury-auth-header">
            <div class="luxury-auth-icon-wrap">
                @include('partials.icon', ['name' => 'flower', 'size' => 27])
            </div>
            <h1 class="luxury-auth-title">Đăng ký tài khoản</h1>
            <p class="luxury-auth-subtitle">Trở thành thành viên Soopi để tận hưởng đặc quyền mua sắm</p>
        </div>

        @if ($errors->any())
            <div class="luxury-auth-alert alert-danger" role="alert">
                <span>⚠️</span>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                @if($errors->has('email'))
                    <div style="margin-top:8px"><a href="{{ route('login') }}">Đăng nhập để tiếp tục hoặc gửi lại email xác thực →</a></div>
                @endif
            </div>
        @endif

        <form class="luxury-auth-form" action="{{ route('register') }}" method="POST">
            @csrf

            <div class="luxury-form-group">
                <label for="name" class="luxury-form-label">
                    <span>Họ và tên</span>
                </label>
                <div class="luxury-input-wrap">
                    <span class="luxury-input-icon">@include('partials.icon', ['name' => 'user', 'size' => 17])</span>
                    <input type="text"
                           name="name" autocomplete="name"
                           id="name"
                           class="luxury-form-input"
                           placeholder="Nhập họ và tên của bạn"
                           required
                           value="{{ old('name') }}"
                           autofocus>
                </div>
            </div>

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
                           value="{{ old('email') }}">
                </div>
                <span class="luxury-form-hint">Email này sẽ nhận liên kết xác thực tài khoản.</span>
            </div>

            <div class="luxury-form-group">
                <label for="password" class="luxury-form-label">
                    <span>Mật khẩu</span>
                </label>
                <div class="luxury-input-wrap">
                    <span class="luxury-input-icon">@include('partials.icon', ['name' => 'lock', 'size' => 17])</span>
                    <input type="password"
                           name="password" autocomplete="new-password"
                           id="password"
                           class="luxury-form-input"
                           placeholder="Tối thiểu 6 ký tự"
                           required>
                </div>
            </div>

            <div class="luxury-form-group">
                <label for="password_confirmation" class="luxury-form-label">
                    <span>Xác nhận mật khẩu</span>
                </label>
                <div class="luxury-input-wrap">
                    <span class="luxury-input-icon">@include('partials.icon', ['name' => 'shield', 'size' => 17])</span>
                    <input type="password"
                           name="password_confirmation" autocomplete="new-password"
                           id="password_confirmation"
                           class="luxury-form-input"
                           placeholder="Nhập lại mật khẩu"
                           required>
                </div>
            </div>

            <button type="submit" class="luxury-auth-btn">
                <span>Đăng ký ngay</span>
                <span>→</span>
            </button>
        </form>

        <div class="luxury-auth-footer">
            Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập ngay</a>
        </div>
    </div>
</section>
@endsection
