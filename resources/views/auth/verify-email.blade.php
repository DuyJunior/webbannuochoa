@extends('layouts.store')
@section('title', __('Xác thực Email · Soopi'))
@section('content')
<section class="otp-page" aria-labelledby="otp-title">
    <div class="otp-shell">
        <aside class="otp-story" aria-label="Soopi Perfume Studio">
            <img src="{{ asset('images/journal/detail.webp') }}" alt="" width="640" height="640">
            <span class="otp-story-brand">SOOPI / YOUR SCENT STORY</span>
            <div>
                @include('partials.brand-mark', ['size' => 38, 'light' => true])
                <h2>{{ __('Chỉ một bước nữa.') }}</h2>
                <p>{{ __('Lưu những mùi hương bạn yêu. Bắt đầu câu chuyện của riêng bạn cùng Soopi.') }}</p>
            </div>
        </aside>
        <div class="otp-panel">
            <div class="otp-step"><span>{{ __('02 / XÁC THỰC TÀI KHOẢN') }}</span><span class="otp-seal">@include('partials.icon', ['name' => 'mail', 'size' => 20])</span></div>
            <h1 id="otp-title">{{ __('Xác thực Email') }}</h1>
            <p class="otp-lead">{{ __('Nhập mã OTP gồm 6 chữ số được gửi đến') }}</p>
            <div class="otp-address">@include('partials.icon', ['name' => 'mail', 'size' => 16])<span>{{ auth()->user()->email }}</span></div>
            @if($errors->any())
                <div class="otp-feedback otp-feedback-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
            @endif
            <form method="POST" action="{{ route('verification.confirm') }}" class="otp-form" data-otp-submit data-busy-label="{{ __('Đang xác thực…') }}">
                @csrf
                <label for="verification-code">{{ __('Mã xác thực OTP') }}</label>
                <input id="verification-code" name="code" type="text" class="otp-code-input" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required aria-describedby="otp-help" @if($errors->has('code')) aria-invalid="true" @endif placeholder="000000">
                <div class="otp-code-help" id="otp-help"><span>{{ __('6 chữ số') }}</span><span>{{ __('Hiệu lực trong 10 phút') }}</span></div>
                <button type="submit" class="otp-primary"><span data-otp-button-label>{{ __('Xác nhận mã OTP') }}</span><span aria-hidden="true">→</span></button>
            </form>
            <div class="otp-resend">
                <span>{{ __('Chưa nhận được mã?') }}</span>
                <form method="POST" action="{{ route('verification.send') }}" data-otp-resend data-retry-after="{{ $retryAfter }}" data-otp-submit data-busy-label="{{ __('Đang gửi mã…') }}">
                    @csrf
                    <button type="submit"><span data-otp-button-label>{{ __('Gửi lại mã OTP') }}</span><span data-otp-countdown aria-hidden="true"></span></button>
                </form>
            </div>
            <div class="otp-guidance"><strong>{{ __('Một lưu ý nhỏ') }}</strong><p>{{ __('Kiểm tra cả mục Thư rác và Quảng cáo. Chỉ dùng mã trong email mới nhất và không chia sẻ mã với người khác.') }}</p></div>
            <form method="POST" action="{{ route('logout') }}" class="otp-leave">@csrf<button type="submit">{{ __('Dùng tài khoản khác') }}</button></form>
        </div>
    </div>
</section>
@endsection
