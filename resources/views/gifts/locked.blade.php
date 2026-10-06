@extends('gifts.layout')
@section('gift-content')
<section class="gx-lock"><div class="gx-envelope" aria-hidden="true">@include('partials.brand-mark', ['size' => 58])</div><span class="gx-kicker">SOME WORDS ARE ONLY FOR YOU</span><h1>{{ __('Một món quà đang chờ bạn.') }}</h1><p>{{ __('Nhập mã 6 chữ số người tặng gửi riêng để mở lời nhắn.') }}</p>
    @if($errors->any())<div class="gx-notice gx-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('gifts.unlock', $token) }}">@csrf<label for="gift-pin">{{ __('Mã mở quà') }}</label><input id="gift-pin" name="pin" type="password" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" required autocomplete="off" aria-describedby="gift-pin-help"><button class="gx-button" type="submit">{{ __('Mở món quà của bạn') }} <span aria-hidden="true">↗</span></button></form><small id="gift-pin-help">{{ __('Chưa có mã? Hãy hỏi người đã gửi thiệp cho bạn.') }}</small>
</section>
@endsection
