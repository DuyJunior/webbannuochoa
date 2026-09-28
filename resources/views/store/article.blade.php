@extends('layouts.store')
@section('title', $article->title.' · Soopi Journal')
@section('content')
<article class="store-container ht-article-page">
    <nav class="luxury-breadcrumb" aria-label="Đường dẫn"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('store.journal') }}">Cẩm nang</a><span>/</span><span>{{ $article->title }}</span></nav>
    <header><span class="ht-eyebrow">THE SOOPI JOURNAL</span><h1>{{ $article->title }}</h1><p>{{ $article->excerpt }}</p><small>{{ $article->created_at->format('d/m/Y') }}</small></header>
    @if($article->image_url)<img class="ht-article-image" src="{{ asset($article->image_url) }}" alt="Minh họa: {{ $article->title }}">@endif
    <div class="ht-article-body">{!! nl2br(e($article->body)) !!}</div>
    <div class="ht-article-next"><p>Bạn muốn tìm mùi hương phù hợp với mình?</p><a class="ht-button" href="{{ route('store.finder') }}">Khám phá ngay →</a><a class="ht-text-link" href="{{ route('store.journal') }}">Xem các bài viết khác</a></div>
</article>
@endsection
