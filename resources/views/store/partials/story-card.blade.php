@php
    $image = config('journal-art.'.$story->slug, $story->image_url) ?: 'images/journal/detail.webp';
    $minutes = max(1, (int) ceil(preg_match_all('/[\p{L}\p{N}]+/u', $story->localized_body ?? '') / 200));
@endphp
<article class="sj-story-card">
    <a href="{{ route('store.article', $story->slug) }}" class="sj-story-photo" tabindex="-1" aria-hidden="true"><img src="{{ asset($image) }}" alt="" width="640" height="480" loading="lazy" decoding="async"><span class="sj-story-number">{{ str_pad((string)$number, 2, '0', STR_PAD_LEFT) }}</span><span class="sj-photo-arrow" aria-hidden="true">↗</span></a>
    <div class="sj-story-meta"><time datetime="{{ $story->created_at->toDateString() }}">{{ $story->created_at->format('d.m.Y') }}</time><span>{{ $minutes }} {{ __('phút đọc') }}</span></div>
    <h3><a href="{{ route('store.article', $story->slug) }}">{{ $story->localized_title }}</a></h3>
    <p>{{ $story->localized_excerpt }}</p>
    <a class="sj-text-link" href="{{ route('store.article', $story->slug) }}">{{ __('Đọc câu chuyện') }} <span aria-hidden="true">↗</span></a>
</article>
