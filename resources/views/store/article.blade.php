@extends('layouts.store')
@section('title', $article->title.' · Soopi Journal')
@section('content')
@php
    $storyImage = config('journal-art.'.$article->slug, $article->image_url);
    $readingMinutes = max(1, (int) ceil(preg_match_all('/[\p{L}\p{N}]+/u', $article->body ?? '') / 200));
    $paragraphs = preg_split('/\R\s*\R/u', trim($article->body ?? ''), -1, PREG_SPLIT_NO_EMPTY);
@endphp
<article class="store-container ht-article-page interior-article">
    <nav class="luxury-breadcrumb" aria-label="Đường dẫn"><a href="{{ route('home') }}">Trang chủ</a><span>/</span><a href="{{ route('store.journal') }}">Cẩm nang</a><span>/</span><span>{{ $article->title }}</span></nav>
    <header class="interior-article-heading">
        <span class="interior-kicker">SOOPI / JOURNAL OLFACTIF</span>
        <h1>{{ $article->title }}</h1>
        <p>{{ $article->excerpt }}</p>
        <div class="interior-article-meta"><span>{{ $article->created_at->format('d.m.Y') }}</span><span>{{ $readingMinutes }} phút đọc</span><a href="#noi-dung-bai-viet">Bắt đầu câu chuyện ↓</a></div>
    </header>
    @if($storyImage)<figure class="interior-article-cover"><img class="ht-article-image" src="{{ asset($storyImage) }}" alt="Minh họa: {{ $article->title }}" width="1200" height="800" fetchpriority="high"><figcaption>SOOPI — THE ART OF SCENT</figcaption></figure>@endif
    <div class="interior-reading-layout" id="noi-dung-bai-viet">
        <aside class="interior-reading-note"><span class="interior-kicker">MỘT GÓC ĐỌC CHẬM</span>@include('partials.icon', ['name' => 'flower', 'size' => 48])<p>Hiểu một mùi hương.<br>Yêu một khoảnh khắc.</p><a href="{{ route('store.journal') }}">← Tất cả câu chuyện</a></aside>
        <div class="ht-article-body">@foreach($paragraphs as $paragraph)<p>{!! nl2br(e($paragraph)) !!}</p>@endforeach</div>
    </div>
    <div class="ht-article-next interior-story-ending"><div><span class="interior-kicker">CÂU CHUYỆN TIẾP THEO LÀ CỦA BẠN</span><h2>Một mùi hương.<br><em>Một dấu ấn riêng.</em></h2><p>Khám phá lựa chọn dành cho khoảnh khắc của bạn.</p></div><div><a class="ht-button" href="{{ route('store.finder') }}">Tìm mùi hương của bạn ↗</a><a class="ht-text-link" href="{{ route('store.journal') }}">Đọc thêm câu chuyện →</a></div></div>
</article>
@endsection
