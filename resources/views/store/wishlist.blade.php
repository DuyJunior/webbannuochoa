@extends('layouts.store')
@section('title', 'Mùi hương yêu thích · Soopi')
@section('content')
<section class="store-container ht-feature-page saved-scents" data-wishlist-page>
    <header class="saved-scents-heading"><div><span class="ht-eyebrow">BỘ SƯU TẬP CỦA BẠN</span><h1>Mùi hương <em>yêu thích.</em></h1><p>Giữ lại những hương thơm khiến bạn muốn quay về.</p></div><a class="saved-explore" href="{{ route('home') }}#san-pham">Khám phá thêm ↗</a></header>
    <div class="ht-feature-panel saved-empty" data-wishlist-empty @if($perfumes->isNotEmpty()) hidden @endif>@include('partials.brand-mark', ['size'=>42])<h2>Một khoảng dành cho hương bạn yêu.</h2><p>Lưu mùi hương bằng biểu tượng trái tim để dễ tìm lại tại đây.</p><a class="ht-button" href="{{ route('home') }}#san-pham">Khám phá nước hoa</a></div>
    <div class="ht-feature-grid saved-scents-grid" data-wishlist-list @if($perfumes->isEmpty()) hidden @endif>
        @foreach($perfumes as $perfume)
        @php
            $availableVolume = $perfume->firstAvailableVolume();
            $displayVolume = $availableVolume ?? (int) ($perfume->volume_ml ?: 100);
            $displayPrice = app(\App\Services\CartQuoteService::class)->unitPrice($perfume, $displayVolume);
        @endphp
        <article class="ht-feature-product saved-scent" data-wishlist-card="{{ $perfume->id }}">
            <div class="saved-scent-visual"><a href="{{ route('perfumes.show', $perfume) }}" aria-label="Xem {{ $perfume->name }}">@if($perfume->image_src)<img src="{{ $perfume->image_src }}" alt="{{ $perfume->name }}" loading="lazy" width="400" height="480">@else<span class="saved-scent-placeholder">@include('partials.brand-mark', ['size'=>64])</span>@endif</a>
                <form method="POST" action="{{ route('store.wishlist.toggle', $perfume) }}" data-wishlist="{{ $perfume->id }}" data-wishlist-saved="true" data-wishlist-name="{{ $perfume->name }}" data-wishlist-login="{{ route('login') }}">@csrf<button type="submit" aria-pressed="true" aria-label="Bỏ yêu thích {{ $perfume->name }}" title="Bỏ yêu thích">@include('partials.icon', ['name'=>'heart','size'=>20])</button></form>
            </div>
            <div class="saved-scent-copy"><span class="saved-scent-brand">{{ $perfume->brand }}</span><h2><a href="{{ route('perfumes.show', $perfume) }}">{{ $perfume->name }}</a></h2>
                <p class="saved-scent-status {{ $availableVolume !== null ? 'is-available' : '' }}">{{ $availableVolume !== null ? 'Có sẵn · '.$displayVolume.' ml' : 'Tạm hết các dung tích' }}</p>
                <div class="saved-scent-bottom"><strong>{{ number_format($displayPrice, 0, ',', '.') }}₫</strong><span>{{ $displayVolume }} ml</span></div>
                <button type="button" class="saved-scent-choose" data-quick-view="{{ route('perfumes.quick-view', $perfume) }}" aria-haspopup="dialog">Xem & chọn dung tích <span aria-hidden="true">↗</span></button>
                @if($availableVolume === null && !in_array($perfume->id, $alerts))<form class="saved-scent-alert" method="POST" action="{{ route('store.stock-alert', $perfume) }}">@csrf<button type="submit">Báo tôi khi có hàng</button></form>@endif
            </div>
        </article>
        @endforeach
    </div>
</section>
@endsection
