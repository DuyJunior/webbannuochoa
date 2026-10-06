@extends('layouts.store')
@section('title', $article->localized_title.' · Soopi Journal')
@section('meta_description', $article->localized_excerpt)
@section('content')
@php
    $storyImage = config('journal-art.'.$article->slug, $article->image_url) ?: 'images/journal/detail.webp';
    $readingMinutes = max(1, (int) ceil(preg_match_all('/[\p{L}\p{N}]+/u', $article->localized_body ?? '') / 200));
    $paragraphs = preg_split('/\R\s*\R/u', trim($article->localized_body ?? ''), -1, PREG_SPLIT_NO_EMPTY);
@endphp
<div class="sj-world sj-reading-world" data-story-reader>
    <article class="store-container sj-article">
        <nav class="sj-breadcrumb" aria-label="{{ __('Đường dẫn') }}"><a href="{{ route('store.journal') }}">← {{ __('Phòng đọc Soopi') }}</a><span aria-hidden="true">/</span><span>{{ __('Câu chuyện') }}</span></nav>
        <header class="sj-article-heading">
            <div><p class="sj-kicker">SOOPI / THE SCENT LETTERS</p><h1>{{ $article->localized_title }}</h1></div>
            <div class="sj-article-intro"><p>{{ $article->localized_excerpt }}</p><div class="sj-story-meta"><time datetime="{{ $article->created_at->toDateString() }}">{{ $article->created_at->format('d.m.Y') }}</time><span>{{ $readingMinutes }} {{ __('phút đọc') }}</span></div><a class="sj-text-link" href="#noi-dung-bai-viet">{{ __('Bắt đầu câu chuyện ↓') }}</a></div>
        </header>
        <figure class="sj-article-cover"><img src="{{ asset($storyImage) }}" alt="{{ __('Minh họa: :title', ['title'=>$article->localized_title]) }}" width="1200" height="800" fetchpriority="high"><figcaption><span>SOOPI JOURNAL</span><span>{{ __('MỘT GÓC ĐỌC CHẬM') }}</span></figcaption></figure>
        <div class="sj-reading-layout" id="noi-dung-bai-viet">
            <aside class="sj-reading-rail">
                <div class="sj-reader-seal">@include('partials.brand-mark', ['size'=>34])<span>THE ART<br>OF SCENT</span></div>
                <p>{{ __('Dành một chút thời gian.') }}<br><em>{{ __('Cho điều khiến bạn tò mò.') }}</em></p>
                <div class="sj-reading-tools" data-reading-tools hidden>
                    <label for="sj-reading-progress">{{ __('Hành trình đọc') }} <span data-reading-percent>0%</span></label>
                    <progress id="sj-reading-progress" max="100" value="0">0%</progress>
                    <div class="sj-font-controls"><span>{{ __('Cỡ chữ') }}</span><div><button type="button" data-reading-size="-1" aria-label="{{ __('Giảm cỡ chữ') }}" aria-controls="story-prose">A−</button><button type="button" data-reading-size="1" aria-label="{{ __('Tăng cỡ chữ') }}" aria-controls="story-prose">A+</button></div></div>
                </div>
                <a class="sj-text-link" href="{{ route('store.journal') }}">{{ __('← Tất cả câu chuyện') }}</a>
            </aside>
            <div class="sj-prose" id="story-prose" data-story-prose>
                @foreach($paragraphs as $paragraph)<p>{!! nl2br(e($paragraph)) !!}</p>@endforeach
                <div class="sj-prose-end" aria-hidden="true"><span></span>@include('partials.brand-mark', ['size'=>24])<span></span></div>
                <p class="sj-editorial-signature">{{ __('Gửi bạn, từ Soopi.') }}</p>
            </div>
        </div>
        @if($relatedArticles->isNotEmpty())
            <section class="sj-related" aria-labelledby="sj-related-title"><header class="sj-section-heading"><div><p class="sj-kicker">{{ __('ĐỪNG KHÉP TRANG VỘI') }}</p><h2 id="sj-related-title">{{ __('Còn một điều') }} <em>{{ __('đáng để khám phá.') }}</em></h2></div><a class="sj-text-link" href="{{ route('store.journal') }}">{{ __('Xem tất cả bài viết') }} ↗</a></header><div class="sj-library-grid">@foreach($relatedArticles as $story)@include('store.partials.story-card', ['story'=>$story, 'number'=>$loop->iteration])@endforeach</div></section>
        @endif
        @include('store.partials.story-invitation')
    </article>
</div>
@endsection
