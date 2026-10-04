@extends('layouts.store')
@section('title', $article->localized_title.' · Soopi Journal')
@section('meta_description', $article->localized_excerpt)
@section('content')
@php
    $storyImage = config('journal-art.'.$article->slug, $article->image_url);
    $readingMinutes = max(1, (int) ceil(preg_match_all('/[\p{L}\p{N}]+/u', $article->localized_body ?? '') / 200));
    $paragraphs = preg_split('/\R\s*\R/u', trim($article->localized_body ?? ''), -1, PREG_SPLIT_NO_EMPTY);
@endphp
<article class="store-container ht-article-page interior-article">
    <nav class="luxury-breadcrumb" aria-label="{{ __('Đường dẫn') }}"><a href="{{ route('home') }}">{{ __('Trang chủ') }}</a><span>/</span><a href="{{ route('store.journal') }}">{{ __('Cẩm nang') }}</a><span>/</span><span>{{ $article->localized_title }}</span></nav>
    <header class="interior-article-heading">
        <span class="interior-kicker">SOOPI / JOURNAL OLFACTIF</span>
        <h1>{{ $article->localized_title }}</h1>
        <p>{{ $article->localized_excerpt }}</p>
        <div class="interior-article-meta"><span>{{ $article->created_at->format('d.m.Y') }}</span><span>{{ $readingMinutes }} {{ __('phút đọc') }}</span><a href="#noi-dung-bai-viet">{{ __('Bắt đầu câu chuyện ↓') }}</a></div>
    </header>
    @if($storyImage)<figure class="interior-article-cover"><img class="ht-article-image" src="{{ asset($storyImage) }}" alt="{{ __('Minh họa: :title', ['title' => $article->localized_title]) }}" width="1200" height="800" fetchpriority="high"><figcaption>SOOPI — THE ART OF SCENT</figcaption></figure>@endif
    <div class="interior-reading-layout" id="noi-dung-bai-viet">
        <aside class="interior-reading-note"><span class="interior-kicker">{{ __('MỘT GÓC ĐỌC CHẬM') }}</span>@include('partials.brand-mark', ['size' => 48])<p>{{ __('Hiểu một mùi hương.') }}<br>{{ __('Yêu một khoảnh khắc.') }}</p><a href="{{ route('store.journal') }}">{{ __('← Tất cả câu chuyện') }}</a></aside>
        <div class="ht-article-body">@foreach($paragraphs as $paragraph)<p>{!! nl2br(e($paragraph)) !!}</p>@endforeach</div>
    </div>
    <div class="ht-article-next interior-story-ending"><div><span class="interior-kicker">{{ __('CÂU CHUYỆN TIẾP THEO LÀ CỦA BẠN') }}</span><h2>{{ __('Một mùi hương.') }}<br><em>{{ __('Một dấu ấn riêng.') }}</em></h2><p>{{ __('Khám phá lựa chọn dành cho khoảnh khắc của bạn.') }}</p></div><div><a class="ht-button" href="{{ route('store.finder') }}">{{ __('Tìm mùi hương của bạn ↗') }}</a><a class="ht-text-link" href="{{ route('store.journal') }}">{{ __('Đọc thêm câu chuyện →') }}</a></div></div>
</article>
@endsection
