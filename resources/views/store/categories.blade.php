@extends('layouts.store')
@section('title', __('Những bộ sưu tập mùi hương · Soopi'))
@section('content')
<section class="store-container ht-feature-page interior-categories">
    @include('partials.interior-heading', [
        'eyebrow' => 'LES COLLECTIONS',
        'heading' => __('Một thế giới hương.'),
        'accent' => __('Nhiều cách khám phá.'),
        'description' => __('Đi từ một bộ sưu tập bạn yêu, tìm dấu hương khiến bạn muốn dừng lại lâu hơn.'),
        'art' => 'images/bloom/collection-reveal.webp',
    ])
    <div class="interior-section-label"><span>{{ __('KHÁM PHÁ THEO BỘ SƯU TẬP') }}</span><span>{{ $categories->count() }} {{ __('bộ sưu tập') }}</span></div>
    <div class="interior-category-grid">
        @forelse($categories as $category)
            <a class="interior-category-link" href="{{ route('home', ['category' => $category->id]) }}#san-pham">
                <span class="interior-category-number" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <div><h2>{{ __($category->localized_name) }}</h2><p>{{ $category->perfumes_count }} {{ __('mùi hương đang có') }}</p></div>
                <span class="interior-category-arrow" aria-hidden="true">↗</span>
            </a>
        @empty
            <div class="interior-category-empty"><h2>{{ __('Bộ sưu tập đang được chuẩn bị.') }}</h2><p>{{ __('Trong lúc chờ, cùng Soopi khám phá gu hương của bạn.') }}</p><a class="ht-text-link" href="{{ route('store.finder') }}">{{ __('Tìm mùi hương của bạn ↗') }}</a></div>
        @endforelse
    </div>
    <a class="ht-text-link interior-categories-all" href="{{ route('home') }}#san-pham">{{ __('Xem tất cả nước hoa ↗') }}</a>
</section>
@endsection
