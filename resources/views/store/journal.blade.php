@extends('layouts.store')
@section('title', 'Cẩm nang nước hoa · Soopi')
@section('content')
<section class="store-container ht-feature-page ht-journal-page">
    <span class="ht-eyebrow">THE SOOPI JOURNAL</span><h1>Cẩm nang <em>mùi hương.</em></h1>
    <p>Những chia sẻ giúp bạn chọn, sử dụng và giữ gìn nước hoa tốt hơn.</p>
    <div class="ht-journal-grid">
        @forelse($articles as $article)
        <article class="ht-feature-panel">
            @if($article->image_url)<a href="{{ route('store.article', $article->slug) }}"><img src="{{ asset($article->image_url) }}" alt="{{ $article->title }}" loading="lazy"></a>@endif
            <span>{{ $article->created_at->format('d/m/Y') }} · SOOPI JOURNAL</span>
            <h2><a href="{{ route('store.article', $article->slug) }}">{{ $article->title }}</a></h2>
            <p>{{ $article->excerpt }}</p>
            <a href="{{ route('store.article', $article->slug) }}">Đọc bài viết →</a>
        </article>
        @empty<div class="ht-feature-panel"><p>Chưa có bài viết nào.</p></div>@endforelse
    </div>
    <div class="mt-4">{{ $articles->links() }}</div>
</section>
@endsection
