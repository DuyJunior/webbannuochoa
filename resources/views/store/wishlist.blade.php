@extends('layouts.store')
@section('title', 'Mùi hương yêu thích · Soopi')
@section('content')
<section class="store-container ht-feature-page">
    <span class="ht-eyebrow">BỘ SƯU TẬP CỦA BẠN</span><h1>Mùi hương <em>yêu thích.</em></h1>
    @if($perfumes->isEmpty())<div class="ht-feature-panel"><p>Bạn chưa lưu mùi hương nào.</p><a class="ht-button" href="{{ route('home') }}#san-pham">Khám phá nước hoa</a></div>
    @else<div class="ht-feature-grid">
        @foreach($perfumes as $perfume)
        <article class="ht-feature-product">
            <a href="{{ route('perfumes.show', $perfume) }}">@if($perfume->image_src)<img src="{{ $perfume->image_src }}" alt="{{ $perfume->name }}" loading="lazy">@endif<span>{{ $perfume->brand }}</span><h2>{{ $perfume->name }}</h2></a>
            <p>{{ $perfume->stock > 0 ? 'Đang có hàng' : 'Tạm hết hàng' }}</p>
            <strong>{{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}₫</strong>
            @if($perfume->stock <= 0 && !in_array($perfume->id, $alerts))<form method="POST" action="{{ route('store.stock-alert', $perfume) }}">@csrf<button type="submit">Báo khi có hàng</button></form>@endif
            <form method="POST" action="{{ route('store.wishlist.toggle', $perfume) }}">@csrf<button type="submit">Bỏ yêu thích</button></form>
        </article>
        @endforeach
    </div>@endif
</section>
@endsection
