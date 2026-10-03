<section class="mood-collection" data-mood-collection data-tone="rose" aria-labelledby="mood-collection-title">
    <div class="store-container">
        <div class="mood-collection-heading">
            <h3 id="mood-collection-title">{{ __('Từ sắc hoa đến') }} <em>{{ __('mùi hương của bạn.') }}</em></h3>
            <span data-mood-count aria-live="polite">{{ count($moodCollections['rose'] ?? []) }} {{ __('gợi ý · Dịu dàng') }}</span>
        </div>
        @foreach(['rose' => __('Dịu dàng'), 'velvet' => __('Cuốn hút'), 'sage' => __('Tự do')] as $tone => $name)
            <div class="mood-collection-grid" data-mood-panel="{{ $tone }}" data-mood-label="{{ $name }}" data-count="{{ count($moodCollections[$tone] ?? []) }}" aria-label="Gợi ý mùi hương {{ mb_strtolower($name) }}" @if(!$loop->first) hidden @endif>
                @forelse($moodCollections[$tone] ?? [] as $pick)
                    @php($item = $pick['product'])
                    <article class="mood-pick">
                        <a class="mood-pick-image" href="{{ route('perfumes.show', $item) }}" tabindex="-1" aria-hidden="true">
                            @if($item->image_src)<img src="{{ $item->image_src }}" alt="" width="128" height="156" loading="lazy">@else<span>@include('partials.brand-mark', ['size' => 42])</span>@endif
                        </a>
                        <div class="mood-pick-copy">
                            <span class="mood-pick-brand">{{ $item->brand }}</span>
                            <h4><a href="{{ route('perfumes.show', $item) }}">{{ $item->name }}</a></h4>
                            <p>{{ $pick['reason'] }}</p>
                            <a class="mood-pick-buy" href="{{ route('perfumes.show', $item) }}" data-quick-view="{{ route('perfumes.quick-view', $item) }}" data-product-name="{{ $item->name }}" aria-haspopup="dialog" aria-controls="product-quick-view" aria-label="Xem nhanh {{ $item->name }}">
                                <span>{{ number_format($pick['price'], 0, ',', '.') }}₫ <small>/ {{ $pick['volume'] }} ml</small></span><span aria-hidden="true">↗</span>
                            </a>
                        </div>
                    </article>
                @empty
                    <p class="mood-collection-empty">{{ __('Soopi đang tuyển chọn thêm mùi hương cho cảm xúc này.') }} <a href="{{ route('store.finder') }}">{{ __('Khám phá theo sở thích ↗') }}</a></p>
                @endforelse
            </div>
        @endforeach
        <p class="mood-collection-footnote">{{ __('Gợi ý của Soopi dựa trên nốt hương. Hãy thử trên da để tìm cảm nhận riêng.') }}</p>
    </div>
</section>
