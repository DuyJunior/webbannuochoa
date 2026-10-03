@php
    $stories = [
        ['slug' => 'xit-nuoc-hoa-o-dau', 'image' => 'ritual', 'category' => __('NGHI THỨC MÙI HƯƠNG'), 'title' => __('Một chạm. Cả ngày thương nhớ.'), 'alt' => __('Ánh sáng xuyên qua làn sương nước hoa trên nền lụa hồng'), 'description' => __('Khám phá cách dùng hương, nhẹ nhàng và vừa đủ.')],
        ['slug' => 'hieu-ba-tang-huong', 'image' => 'notes', 'category' => __('TẦNG HƯƠNG'), 'title' => __('Bên trong một mùi hương.'), 'alt' => __('Hoa hồng, cam bergamot và chai nước hoa trên đế pha lê'), 'description' => __('Từ ấn tượng đầu đến dư âm ở lại.')],
        ['slug' => 'bao-quan-nuoc-hoa', 'image' => 'detail', 'category' => __('GÓC NHÌN GẦN'), 'title' => __('Đẹp từ chi tiết nhỏ.'), 'alt' => __('Chi tiết nắp bạc và thân chai thủy tinh giữa những nếp lụa'), 'description' => __('Chăm chút để giữ trọn chai hương bạn yêu.')],
    ];
@endphp
<section class="store-container soopi-journal" id="nhat-ky-mui-huong" aria-labelledby="journal-heading">
    <header class="journal-heading">
        <div>
            <span class="journal-eyebrow">SOOPI / JOURNAL OLFACTIF</span>
            <h2 id="journal-heading">{{ __('Mùi hương có hình.') }}<br><em>{{ __('Cảm xúc có tiếng.') }}</em></h2>
        </div>
        <div class="journal-intro">
            <p>{{ __('Những câu chuyện nhỏ, để hiểu một mùi hương sâu hơn.') }}</p>
            <a href="{{ route('store.journal') }}">{{ __('Khám phá tất cả') }} <span aria-hidden="true">↗</span></a>
        </div>
    </header>

    <div class="journal-stories">
        @foreach($stories as $story)
            @php($article = $journalArticles->get($story['slug']))
            <a class="journal-story {{ $loop->first ? 'journal-story-featured' : 'journal-story-side' }}"
               href="{{ $article ? route('store.article', $article) : route('store.journal') }}">
                <div class="journal-photo">
                    <img src="{{ asset('images/journal/'.$story['image'].'.webp') }}" alt="{{ $story['alt'] }}"
                         width="{{ $loop->first ? 1200 : 640 }}" height="{{ $loop->first ? 800 : 640 }}" loading="lazy" decoding="async">
                    <span class="journal-read"><span>{{ $article ? __('Đọc câu chuyện') : __('Khám phá cẩm nang') }}</span><span aria-hidden="true">↗</span></span>
                </div>
                <div class="journal-story-copy">
                    <span class="journal-eyebrow">0{{ $loop->iteration }} / {{ $story['category'] }}</span>
                    <h3>{{ $story['title'] }}</h3>
                    <p>{{ $story['description'] }}</p>
                </div>
            </a>
        @endforeach
    </div>

    @if($homeVideos->isNotEmpty())
        <section class="journal-video-library" id="video-soopi" aria-labelledby="journal-video-heading">
            <header class="journal-video-heading">
                <div><span class="journal-eyebrow">SOOPI / VIDEO STORIES</span><h2 id="journal-video-heading">{{ __('Nhìn gần hơn.') }}<br><em>{{ __('Cảm nhận nhiều hơn.') }}</em></h2></div>
                <div class="journal-video-intro"><span class="journal-library-count">{{ str_pad((string) $homeVideos->count(), 2, '0', STR_PAD_LEFT) }} {{ __('VIDEO TỪ SOOPI') }}</span><p>{{ __('Chọn một thước phim.') }} <br>{{ __('Khám phá câu chuyện phía sau.') }}</p></div>
            </header>
            <div class="journal-video-grid">
                @foreach($homeVideos as $vid)
                    <a class="journal-video js-open-video" href="{{ $vid->embed_url }}"
                       data-title="{{ $vid->localized_title }}" data-embed="{{ $vid->embed_url }}" data-desc="{{ $vid->localized_description }}" data-views="{{ $vid->formatted_views }}"
                       data-perfume-name="{{ $vid->perfume?->name }}" data-perfume-brand="{{ $vid->perfume?->brand }}"
                       data-perfume-price="{{ $vid->perfume ? number_format($vid->perfume->sale_price ?? $vid->perfume->price, 0, ',', '.').'₫' : '' }}"
                       data-perfume-url="{{ $vid->perfume ? route('perfumes.show', $vid->perfume) : '' }}" data-perfume-img="{{ $vid->perfume?->image_src }}">
                        <div class="journal-video-thumb"><img src="{{ $vid->thumbnail_src }}" alt="" width="360" height="450" loading="lazy" decoding="async"><span class="journal-video-number" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span class="journal-play" aria-hidden="true">@include('partials.icon', ['name' => 'play', 'size' => 22])</span>@if($vid->duration)<span class="journal-duration">{{ $vid->duration }}</span>@endif<span class="journal-watch" aria-hidden="true">{{ __('Xem video') }} <span>↗</span></span></div>
                        <span class="journal-eyebrow">{{ $vid->perfume?->brand ?: 'SOOPI' }}</span><h3>{{ $vid->localized_title }}</h3>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <div class="journal-live" aria-labelledby="journal-live-heading">
        <div class="journal-live-art"><img src="{{ asset('images/journal/live.webp') }}" alt="{{ __('Chai nước hoa hồng bên vòng pha lê và cánh lụa') }}" width="960" height="640" loading="lazy" decoding="async"></div>
        <div class="journal-live-copy">
            <span class="journal-eyebrow">SOOPI / LIVE STUDIO</span>
            <h3 id="journal-live-heading">{{ __('Một cuộc hẹn.') }}<br>{{ __('Muôn tầng hương.') }}</h3>
            <p>{{ __('Cùng khám phá và trò chuyện về mùi hương bạn yêu.') }}</p>
            <span class="journal-live-status" data-journal-live-status @unless($onAir) hidden @endunless><i aria-hidden="true"></i> {{ __('Đang trực tiếp') }}</span>
        </div>
        <div class="journal-live-action">
            <a href="{{ route('livestream.show') }}"><span data-journal-live-label>{{ $onAir ? __('Vào xem trực tiếp') : __('Xem lịch live') }}</span> <span aria-hidden="true">↗</span></a>
            <p>{{ __('Gặp nhau trong thế giới hương') }}</p>
        </div>
    </div>

    <div class="journal-signature" aria-hidden="true"><span></span>@include('partials.brand-mark', ['size' => 30])<span></span></div>
</section>
