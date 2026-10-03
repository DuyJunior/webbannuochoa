@extends('layouts.store')
@section('title', __('Tài khoản của tôi · Soopi'))
@section('content')
<section class="store-container account-settings">
    <header>
        @include('partials.brand-logo', ['class' => 'account-brand-logo'])
        <p class="interior-kicker">{{ __('TÀI KHOẢN') }}</p>
        <h1>{{ __('Thông tin của tôi') }}</h1>
        <p>{{ __('Cập nhật tên hiển thị và quản lý mật khẩu đăng nhập.') }}</p>
    </header>
    @if($errors->any())<div role="alert" class="account-notice"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="account-grid">
        <form method="POST" action="{{ route('account.update') }}">
            @csrf @method('PATCH')
            <h2>{{ __('Hồ sơ cá nhân') }}</h2>
            <label for="account-name">{{ __('Tên hiển thị') }}</label>
            <input id="account-name" name="name" value="{{ is_string(old('name')) ? old('name') : $user->name }}" maxlength="100" autocomplete="name" required>
            <label for="account-email">{{ __('Email đăng nhập') }}</label>
            <input id="account-email" type="email" value="{{ $user->email }}" readonly aria-describedby="email-note">
            <small id="email-note">{{ __('Email chỉ đọc để giữ nguyên trạng thái xác minh tài khoản.') }}</small>
            <button class="ht-button ht-button-primary" type="submit">{{ __('Lưu thông tin') }}</button>
        </form>
        <form method="POST" action="{{ route('account.password') }}">
            @csrf @method('PUT')
            <h2>{{ __('Đổi mật khẩu') }}</h2>
            <label for="current-password">{{ __('Mật khẩu hiện tại') }}</label>
            <input id="current-password" type="password" name="current_password" autocomplete="current-password" required>
            <label for="new-password">{{ __('Mật khẩu mới') }}</label>
            <input id="new-password" type="password" name="password" minlength="8" autocomplete="new-password" aria-describedby="password-note" required>
            <small id="password-note">{{ __('Ít nhất 8 ký tự, có chữ và số, khác mật khẩu hiện tại.') }}</small>
            <label for="confirm-password">{{ __('Nhập lại mật khẩu mới') }}</label>
            <input id="confirm-password" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
            <button class="ht-button ht-button-primary" type="submit">{{ __('Đổi mật khẩu') }}</button>
        </form>
    </div>
    <p><a href="{{ route('orders.index') }}">{{ __('Xem đơn mua của tôi →') }}</a></p>
</section>
<style>
.account-settings{max-width:1000px;padding-top:40px;padding-bottom:64px}.account-settings header{margin-bottom:24px}.account-settings h1{font-size:32px}.account-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px}.account-grid form{background:#fff;border:1px solid #e7d8c5;border-radius:16px;padding:24px;display:flex;flex-direction:column;gap:12px}.account-grid h2{font-size:21px}.account-grid input{padding:12px;border:1px solid #b7ada0;border-radius:8px;width:100%;box-sizing:border-box}.account-grid input:focus{outline:2px solid #145a43;outline-offset:2px}.account-grid input[readonly]{background:#f5f3ef}.account-grid small{color:#555}.account-grid button{margin-top:12px}.account-notice{padding:16px;background:#f4eee4;border-radius:8px}@media(max-width:700px){.account-grid{grid-template-columns:1fr}}
</style>
@endsection
