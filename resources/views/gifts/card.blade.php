@extends('gifts.layout')
@section('gift-content')
<section class="gx-card-page gx-lock" data-gift-share data-url="{{ route('gifts.open', $gift->token) }}" data-qr-error="{{ __('Chưa tạo được QR. Hãy tải lại trang; bạn vẫn có thể sao chép đường dẫn.') }}">
    <div class="gx-print-card">@include('partials.brand-mark', ['size' => 42])<span class="gx-kicker">A LITTLE SOMETHING, JUST FOR YOU</span><h2>{{ __('Dành riêng cho') }}<br><em>{{ $gift->recipient_name }}</em></h2><canvas data-gift-qr aria-label="{{ __('QR mở quà') }}" role="img"></canvas><p>{{ __('Quét QR. Nhập mã riêng. Mở một lời thương.') }}</p><small>{{ __('Từ') }} {{ $gift->sender_name }}</small></div>
    <button class="gx-button" type="button" data-print-gift hidden>{{ __('In thiệp') }}</button><p data-share-status role="status"></p><a href="{{ route('gifts.edit', $gift) }}">{{ __('Quay lại chỉnh sửa') }}</a>
</section>
@endsection
