@if($products->isNotEmpty())
    <div class="ht-live-shelf-heading">
        <div><span class="ht-live-eyebrow">{{ __('Gợi ý từ buổi phát') }}</span><h2>{{ __('Chọn hương ngay khi xem') }}</h2><p>{{ __('Xem chi tiết sản phẩm, video sẽ thu nhỏ và theo bạn khi mua sắm.') }}</p></div>
        <span class="ht-live-shelf-count">{{ $products->count() }} {{ __('sản phẩm') }}</span>
    </div>
    <div class="ht-live-product-grid">
        @foreach($products as $product)
            <article class="ht-live-product-card {{ $product->id === $livestream->pinned_perfume_id ? 'ht-live-product-card--pinned' : '' }}">
                <div class="ht-live-product-thumb">
                    @if($product->image_src)<img src="{{ $product->image_src }}" alt="{{ $product->name }}" loading="lazy">@else<span aria-hidden="true">@include('partials.brand-mark', ['size' => '1em'])</span>@endif
                </div>
                <div class="ht-live-product-info">
                    @if($product->id === $livestream->pinned_perfume_id)<span class="ht-live-pinned-label">@include('partials.icon', ['name' => 'sparkles', 'size' => '1em']) {{ __('Đang giới thiệu') }}</span>@endif
                    <span class="ht-live-brand">{{ $product->brand }}</span>
                    <h3>{{ $product->name }}</h3>
                    <strong>{{ number_format((float) ($product->sale_price ?? $product->price), 0, ',', '.') }}{{ __('đ') }}</strong>
                    <a class="ht-live-button" href="{{ route('livestream.products.open', [$livestream, $product]) }}" @if($onAir) data-live-follow="{{ $livestream->id }}" @endif>{{ __('Xem nước hoa →') }}</a>
                </div>
            </article>
        @endforeach
    </div>
@endif
