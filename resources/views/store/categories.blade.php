@extends('layouts.store')
@section('title', 'Những bộ sưu tập mùi hương · Soopi')
@section('content')
<section class="store-container ht-feature-page interior-categories">
    @include('partials.interior-heading', [
        'eyebrow' => 'LES COLLECTIONS',
        'heading' => 'Một thế giới hương.',
        'accent' => 'Nhiều cách khám phá.',
        'description' => 'Đi từ một bộ sưu tập bạn yêu, tìm dấu hương khiến bạn muốn dừng lại lâu hơn.',
        'art' => 'images/bloom/collection-reveal.webp',
    ])
    <div class="interior-section-label"><span>KHÁM PHÁ THEO BỘ SƯU TẬP</span><span>{{ $categories->count() }} bộ sưu tập</span></div>
    <div class="interior-category-grid">
        @forelse($categories as $category)
            <a class="interior-category-link" href="{{ route('home', ['category' => $category->id]) }}#san-pham">
                <span class="interior-category-number" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <div><h2>{{ $category->name }}</h2><p>{{ $category->perfumes_count }} mùi hương đang có</p></div>
                <span class="interior-category-arrow" aria-hidden="true">↗</span>
            </a>
        @empty
            <div class="interior-category-empty"><h2>Bộ sưu tập đang được chuẩn bị.</h2><p>Trong lúc chờ, cùng Soopi khám phá gu hương của bạn.</p><a class="ht-text-link" href="{{ route('store.finder') }}">Tìm mùi hương của bạn ↗</a></div>
        @endforelse
    </div>
    <a class="ht-text-link interior-categories-all" href="{{ route('home') }}#san-pham">Xem tất cả nước hoa ↗</a>
</section>
@endsection
