@extends('layouts.store')
@section('title', __('Cẩm nang nước hoa · Soopi'))
@section('content')
<section class="store-container ht-feature-page ht-journal-page">
    @include('partials.interior-heading', [
        'eyebrow' => 'JOURNAL OLFACTIF', 'heading' => __('Mùi hương có hình.'), 'accent' => __('Cảm xúc có tiếng.'),
        'description' => __('Một góc đọc chậm. Những câu chuyện giúp bạn hiểu, yêu và sống cùng mùi hương.'),
        'art' => 'images/journal/ritual.webp',
    ])
    <div class="interior-section-label"><span>{{ __('NHỮNG CÂU CHUYỆN TỪ SOOPI') }}</span><span>{{ $articles->total() }} {{ __('bài viết') }}</span></div>
    <div class="ht-journal-grid">
        @forelse($articles as $article)
        <article class="ht-feature-panel interior-journal-card">
            @php($storyImage = config('journal-art.'.$article->slug, $article->image_url))
            @if($storyImage)<a href="{{ route('store.article', $article->slug) }}"><img src="{{ asset($storyImage) }}" alt="{{ $article->localized_title }}" loading="lazy"></a>@endif
            <span>{{ $article->created_at->format('d/m/Y') }} · SOOPI JOURNAL</span>
            <h2><a href="{{ route('store.article', $article->slug) }}">{{ $article->localized_title }}</a></h2>
            <p>{{ $article->localized_excerpt }}</p>
            <a href="{{ route('store.article', $article->slug) }}">{{ __('Đọc câu chuyện ↗') }}</a>
        </article>
        @empty<div class="ht-feature-panel"><p>{{ __('Chưa có bài viết nào.') }}</p></div>@endforelse
    </div>
    <div class="mt-4">{{ $articles->links() }}</div>
</section>
@endsection
