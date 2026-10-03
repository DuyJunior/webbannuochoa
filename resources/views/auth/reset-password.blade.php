@extends('layouts.store')
@section('title', 'Đặt lại mật khẩu · Soopi')
@section('content')
<section class="luxury-auth-section">
    @include('partials.atelier-auth-art')
    <div class="luxury-auth-card">
        <div class="luxury-auth-header">
            <div class="luxury-auth-icon-wrap">@include('partials.brand-mark', ['size' => 32])</div>
            <h1 class="luxury-auth-title">Mật khẩu mới.</h1>
            <p class="luxury-auth-subtitle">Một bước để trở lại với những mùi hương bạn yêu thích.</p>
        </div>
        @if(!$validToken)
            <div class="luxury-auth-alert alert-danger" role="alert">{{ $invalidLinkMessage }}</div>
            <a class="luxury-auth-btn" href="{{ route('password.request') }}">Yêu cầu liên kết mới <span aria-hidden="true">→</span></a>
        @else
            <form class="luxury-auth-form" method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="luxury-form-group">
                    <label for="reset-email" class="luxury-form-label">Email tài khoản</label>
                    <input id="reset-email" type="email" name="email" value="{{ $email }}" class="luxury-form-input no-icon" autocomplete="username" readonly required>
                </div>
                <div class="luxury-form-group">
                    <label for="reset-password" class="luxury-form-label">Mật khẩu mới</label>
                    <input id="reset-password" type="password" name="password" class="luxury-form-input no-icon" autocomplete="new-password" minlength="8" required autofocus aria-describedby="reset-password-help @error('password') reset-password-error @enderror" @error('password') aria-invalid="true" @enderror>
                    <span id="reset-password-help" class="luxury-form-hint">Ít nhất 8 ký tự, gồm chữ và số.</span>
                    @error('password')<div id="reset-password-error" class="luxury-auth-alert alert-danger" role="alert">{{ $message }}</div>@enderror
                </div>
                <div class="luxury-form-group">
                    <label for="reset-password-confirmation" class="luxury-form-label">Nhập lại mật khẩu mới</label>
                    <input id="reset-password-confirmation" type="password" name="password_confirmation" class="luxury-form-input no-icon" autocomplete="new-password" minlength="8" required>
                </div>
                <button type="submit" class="luxury-auth-btn">Lưu mật khẩu mới <span aria-hidden="true">→</span></button>
                <p class="luxury-form-hint">Sau khi lưu, bạn cần đăng nhập lại bằng mật khẩu mới.</p>
            </form>
        @endif
        <div class="luxury-auth-footer"><a href="{{ route('login') }}">← Quay lại đăng nhập</a></div>
    </div>
</section>
@endsection
