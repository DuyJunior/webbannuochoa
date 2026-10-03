@extends('layouts.store')
@section('title', 'Quên mật khẩu · Soopi')
@section('content')
<section class="luxury-auth-section">
    @include('partials.atelier-auth-art')
    <div class="luxury-auth-card">
        <div class="luxury-auth-header">
            <div class="luxury-auth-icon-wrap">@include('partials.brand-mark', ['size' => 32])</div>
            <h1 class="luxury-auth-title">Quên mật khẩu?</h1>
            <p class="luxury-auth-subtitle">Nhập email tài khoản để nhận liên kết đặt lại mật khẩu.</p>
        </div>
        @if(session('status'))<div class="luxury-auth-alert alert-success" role="status">{{ session('status') }}</div>@endif
        <form class="luxury-auth-form" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="luxury-form-group">
                <label for="recovery-email" class="luxury-form-label">Email tài khoản</label>
                <div class="luxury-input-wrap">
                    <span class="luxury-input-icon">@include('partials.icon', ['name' => 'mail', 'size' => 17])</span>
                    <input id="recovery-email" type="email" name="email" class="luxury-form-input" value="{{ is_string(old('email')) ? old('email') : '' }}" autocomplete="email" maxlength="255" placeholder="email@cuaban.com" required autofocus aria-describedby="recovery-email-help @error('email') recovery-email-error @enderror" @error('email') aria-invalid="true" @enderror>
                </div>
                <span id="recovery-email-help" class="luxury-form-hint">Liên kết chỉ dùng một lần và có hiệu lực {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60) }} phút từ lúc yêu cầu.</span>
                @error('email')<div id="recovery-email-error" class="luxury-auth-alert alert-danger" role="alert">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="luxury-auth-btn">Gửi liên kết đặt lại <span aria-hidden="true">→</span></button>
        </form>
        <div class="luxury-auth-footer"><a href="{{ route('login') }}">← Quay lại đăng nhập</a></div>
    </div>
</section>
@endsection
