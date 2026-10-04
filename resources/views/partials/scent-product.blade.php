@php
    $featured = $featured ?? false;
    $artwork = config('scent-gallery.artwork', [])[$perfume->slug] ?? null;
    $picture = $artwork && is_file(public_path($artwork)) ? asset($artwork) : $perfume->image_src;
    $saved = in_array($perfume->id, $wishlistIds ?? []);
@endphp
<article @class(['gallery-piece', 'gallery-piece-featured' => $featured]) data-product-id="{{ $perfume->id }}">
    <a class="scent-visual" href="{{ route('perfumes.show', $perfume) }}" aria-label="{{ __('Xem :name', ['name' => $perfume->localized_name]) }}">
        @if($picture)
            <img src="{{ $picture }}" alt="{{ $perfume->localized_name }}{{ $artwork ? __(' — phối cảnh bộ sưu tập Soopi') : '' }}" width="{{ $featured ? 960 : 640 }}" height="{{ $featured ? 1200 : 640 }}" loading="lazy" decoding="async">
        @else
            <span class="scent-placeholder">@include('partials.brand-mark', ['size' => 72])<span>{{ $perfume->brand }}</span></span>
        @endif
    </a>
    @auth
        <form class="scent-heart" method="POST" action="{{ route('store.wishlist.toggle', $perfume) }}" data-wishlist="{{ $perfume->id }}" data-wishlist-saved="{{ $saved ? 'true' : 'false' }}" data-wishlist-name="{{ $perfume->localized_name }}" data-wishlist-login="{{ route('login') }}">
            @csrf
            <button type="submit" aria-pressed="{{ $saved ? 'true' : 'false' }}" aria-label="{{ $saved ? __('Bỏ yêu thích') : __('Yêu thích') }} {{ $perfume->localized_name }}">@include('partials.icon', ['name' => 'heart', 'size' => 21])</button>
        </form>
    @else
        <a class="scent-heart" href="{{ route('login') }}" aria-label="{{ __('Đăng nhập để lưu :name', ['name' => $perfume->localized_name]) }}">@include('partials.icon', ['name' => 'heart', 'size' => 21])</a>
    @endauth
    <div class="scent-info">
        <span class="scent-brand">{{ $perfume->brand }}</span>
        <h3><a href="{{ route('perfumes.show', $perfume) }}">{{ $perfume->localized_name }}</a></h3>
        <p class="scent-meta">{{ $perfume->volume_ml }} ml <span>·</span> {{ $perfume->concentration ?: __('Nước hoa') }}</p>
        <div class="scent-purchase">
            <div class="scent-price"><strong>{{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}₫</strong>@if($perfume->sale_price !== null && $perfume->sale_price < $perfume->price)<del>{{ number_format((float) $perfume->price, 0, ',', '.') }}₫</del>@endif</div>
            <a class="scent-buy" href="{{ route('perfumes.show', $perfume) }}" data-quick-view="{{ route('perfumes.quick-view', $perfume) }}" data-product-name="{{ $perfume->localized_name }}" aria-haspopup="dialog" aria-controls="product-quick-view" aria-label="{{ __('Chọn dung tích :name', ['name' => $perfume->localized_name]) }}"><span>{{ $featured ? __('Khám phá mùi hương') : __('Chọn dung tích') }}</span><span aria-hidden="true">↗</span></a>
        </div>
        <button type="button" class="scent-compare ht-compare-add" data-compare-id="{{ $perfume->id }}" aria-label="{{ __('Thêm :name vào so sánh', ['name' => $perfume->localized_name]) }}">{{ __('+ So sánh') }}</button>
    </div>
</article>
