@extends('layouts.store')
@section('title', 'Cẩm nang nước hoa · Soopi')
@section('content')
<section class="store-container ht-feature-page ht-journal-page">
    @include('partials.interior-heading', [
        'eyebrow' => 'JOURNAL OLFACTIF', 'heading' => 'Mùi hương có hình.', 'accent' => 'Cảm xúc có tiếng.',
        'description' => 'Một góc đọc chậm. Những câu chuyện giúp bạn hiểu, yêu và sống cùng mùi hương.',
        'art' => 'images/journal/ritual.webp',
    ])
    <div class="interior-section-label"><span>NHỮNG CÂU CHUYỆN TỪ SOOPI</span><span>{{ $articles->total() }} bài viết</span></div>
    <div class="ht-journal-grid">
        @forelse($articles as $article)
        <article class="ht-feature-panel interior-journal-card">
            @php($storyImage = config('journal-art.'.$article->slug, $article->image_url))
            @if($storyImage)<a href="{{ route('store.article', $article->slug) }}"><img src="{{ asset($storyImage) }}" alt="{{ $article->title }}" loading="lazy"></a>@endif
            <span>{{ $article->created_at->format('d/m/Y') }} · SOOPI JOURNAL</span>
            <h2><a href="{{ route('store.article', $article->slug) }}">{{ $article->title }}</a></h2>
            <p>{{ $article->excerpt }}</p>
            <a href="{{ route('store.article', $article->slug) }}">Đọc câu chuyện ↗</a>
        </article>
        @empty<div class="ht-feature-panel"><p>Chưa có bài viết nào.</p></div>@endforelse
    </div>
    <div class="mt-4">{{ $articles->links() }}</div>
</section>
@endsection
