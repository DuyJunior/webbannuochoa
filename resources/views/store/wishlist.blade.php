@extends('layouts.store')
@section('title', 'Mùi hương yêu thích · Soopi')
@section('content')
<section class="store-container ht-feature-page" data-wishlist-page>
    <span class="ht-eyebrow">BỘ SƯU TẬP CỦA BẠN</span><h1>Mùi hương <em>yêu thích.</em></h1>
    <div class="ht-feature-panel" data-wishlist-empty @if($perfumes->isNotEmpty()) hidden @endif><p>Bạn chưa lưu mùi hương nào.</p><a class="ht-button" href="{{ route('home') }}#san-pham">Khám phá nước hoa</a></div>
    <div class="ht-feature-grid" data-wishlist-list @if($perfumes->isEmpty()) hidden @endif>
        @foreach($perfumes as $perfume)
        <article class="ht-feature-product" data-wishlist-card="{{ $perfume->id }}">
            <a href="{{ route('perfumes.show', $perfume) }}">@if($perfume->image_src)<img src="{{ $perfume->image_src }}" alt="{{ $perfume->name }}" loading="lazy">@endif<span>{{ $perfume->brand }}</span><h2>{{ $perfume->name }}</h2></a>
            <p>{{ $perfume->stock > 0 ? 'Đang có hàng' : 'Tạm hết hàng' }}</p>
            <strong>{{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}₫</strong>
            @if($perfume->stock <= 0 && !in_array($perfume->id, $alerts))<form method="POST" action="{{ route('store.stock-alert', $perfume) }}">@csrf<button type="submit">Báo khi có hàng</button></form>@endif
            <form method="POST" action="{{ route('store.wishlist.toggle', $perfume) }}" data-wishlist="{{ $perfume->id }}" data-wishlist-saved="true" data-wishlist-name="{{ $perfume->name }}" data-wishlist-login="{{ route('login') }}">@csrf<button type="submit" aria-pressed="true" aria-label="Bỏ yêu thích {{ $perfume->name }}">Bỏ yêu thích</button></form>
        </article>
        @endforeach
    </div>
</section>
@endsection
