@extends('layouts.store')
@section('robots', 'noindex, nofollow')
@section('title', __('Quà của tôi') . ' · Soopi')
@section('content')
<section class="gx gx-page">
    <header class="gx-heading"><div><span class="gx-kicker">SOOPI / GIFT ATELIER</span><h1>{{ __('Một món quà. Một kỷ niệm.') }}</h1><p>{{ __('Lưu lời nhắn, bức ảnh và giọng nói của bạn trong một chiếc thiệp QR riêng tư.') }}</p></div><a class="gx-button" href="{{ route('gifts.create') }}">{{ __('Tạo trang quà') }} <span aria-hidden="true">↗</span></a></header>
    <div class="gx-steps"><span>01 / {{ __('Viết lời thương') }}</span><span>02 / {{ __('In hoặc gửi QR') }}</span><span>03 / {{ __('Nhận lời cảm ơn') }}</span></div>
    <div class="gx-list">
        @forelse($gifts as $gift)
            <article class="gx-list-card"><span class="gx-kicker">{{ $gift->isExpired() ? __('Đã hết hạn') : ($gift->thanked_at ? __('Đã nhận lời cảm ơn') : ($gift->opened_at ? __('Đã mở quà') : __('Chờ người nhận mở'))) }}</span><h2>{{ $gift->recipient_name }}</h2><p>{{ $gift->perfume?->localized_name ?? __('Một lời nhắn dành riêng') }}</p><small>{{ __('Hạn mở quà') }}: {{ $gift->expires_at->format('d/m/Y') }}</small><a class="gx-button gx-button-light" href="{{ route('gifts.edit', $gift) }}">{{ __('Quản lý thiệp & QR') }} <span aria-hidden="true">↗</span></a></article>
        @empty
            <div class="gx-empty">@include('partials.brand-mark', ['size' => 56])<h2>{{ __('Có những lời thương, xứng đáng được giữ lại.') }}</h2><p>{{ __('Tạo trang quà đầu tiên để người nhận mở bằng QR và mã riêng bạn gửi.') }}</p><a class="gx-button" href="{{ route('gifts.create') }}">{{ __('Bắt đầu một món quà') }}</a></div>
        @endforelse
    </div>
    {{ $gifts->links() }}
</section>
@endsection
