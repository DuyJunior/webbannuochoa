@extends('layouts.store')
@section('title', __('Xác thực Email · Soopi'))
@section('content')
<section class="luxury-auth-section">
    <div class="luxury-auth-card" style="max-width:480px;text-align:center">
        <div class="luxury-auth-header">
            <div class="luxury-auth-icon-wrap">@include('partials.brand-mark', ['size' => 32])</div>
            <h1 class="luxury-auth-title">{{ __('Xác thực Email') }}</h1>
            <p class="luxury-auth-subtitle">{{ __('Nhập mã OTP gồm 6 chữ số được gửi đến') }}<br><strong style="overflow-wrap:anywhere">{{ auth()->user()->email }}</strong></p>
        </div>
        @if(session('message'))
            <div class="luxury-auth-alert alert-success" role="status">{{ session('message') }}</div>
        @endif
        @if($errors->any())
            <div class="luxury-auth-alert alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <form method="POST" action="{{ route('verification.confirm') }}" class="luxury-auth-form">
            @csrf
            <div class="luxury-form-group">
                <label for="verification-code" class="luxury-form-label">{{ __('Mã xác thực OTP') }}</label>
                <input id="verification-code" name="code" type="text" class="luxury-form-input" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required autofocus aria-describedby="otp-help" @if($errors->has('code')) aria-invalid="true" @endif style="text-align:center;font-size:28px;letter-spacing:.35em;padding:16px;min-width:0;width:100%;box-sizing:border-box" placeholder="000000">
                <p id="otp-help" class="luxury-form-hint">{{ __('Mã có hiệu lực 10 phút. Không chia sẻ mã này với người khác.') }}</p>
            </div>
            <button type="submit" class="luxury-auth-btn">{{ __('Xác nhận mã OTP') }} <span aria-hidden="true">→</span></button>
        </form>
        <div style="display:flex;flex-direction:column;gap:14px;margin-top:24px">
            <p class="luxury-form-hint">{{ __('Chưa nhận được mã? Kiểm tra thư rác hoặc yêu cầu gửi lại. Mã mới sẽ thay thế mã cũ.') }}</p>
            <form method="POST" action="{{ route('verification.send') }}" data-otp-resend data-retry-after="{{ $retryAfter }}">
                @csrf
                <button type="submit" class="luxury-auth-secondary-btn"><span>{{ __('Gửi lại mã OTP') }}</span><span data-otp-countdown aria-hidden="true"></span></button>
            </form>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="luxury-auth-secondary-btn">{{ __('Đăng xuất') }}</button>
            </form>
        </div>
    </div>
</section>
@endsection
