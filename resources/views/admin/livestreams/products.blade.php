@if($products->isEmpty())
    <p class="live-product-empty">Chưa có sản phẩm. Thêm nước hoa để khách xem và mua ngay trong buổi live.</p>
@else
    <div class="live-studio-product-list">
        @foreach($products as $product)
            <article class="live-studio-product-item {{ $product->id === $livestream->pinned_perfume_id ? 'live-studio-product-item--pinned' : '' }}">
                @if($product->image_src)<img src="{{ $product->image_src }}" alt="" loading="lazy">@else<span class="live-product-fallback">@include('partials.brand-mark', ['size' => '1em'])</span>@endif
                <div><strong>{{ $product->name }}</strong><small>{{ $product->brand }} · {{ number_format((float) ($product->sale_price ?? $product->price), 0, ',', '.') }}đ</small></div>
                @if($livestream->status !== 'ended' && !($readOnly ?? false))<button type="button" class="live-admin-btn {{ $product->id === $livestream->pinned_perfume_id ? 'live-admin-btn--pinned' : '' }}" data-pin-id="{{ $product->id === $livestream->pinned_perfume_id ? '' : $product->id }}">{{ $product->id === $livestream->pinned_perfume_id ? ' Đang ghim' : 'Ghim' }}</button>@endif
                @if($livestream->status !== 'ended' && !($readOnly ?? false))<button type="button" class="live-admin-btn live-admin-btn--danger" data-remove-url="{{ route('admin.livestreams.products.destroy', [$livestream, $product]) }}">Gỡ</button>@endif
            </article>
        @endforeach
    </div>
@endif
