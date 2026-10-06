@extends('layouts.store')
@section('title', __('Cẩm nang nước hoa · Soopi'))
@section('content')
<div class="sj-world">
    <div class="store-container">
        @if($articles->currentPage() === 1)
            <header class="sj-cover">
                <div class="sj-cover-copy">
                    <p class="sj-kicker">SOOPI / JOURNAL OLFACTIF</p>
                    <h1>{{ __('Có những điều,') }}<br><em>{{ __('chỉ hương mới kể.') }}</em></h1>
                    <p class="sj-cover-intro">{{ __('Một mùi hương không chỉ để chọn. Mà để hiểu, để cảm, để tìm thấy một phần của mình.') }}</p>
                    <a class="sj-button" href="#loi-vao-cau-chuyen">{{ __('Mở một trang hương') }} <span aria-hidden="true">↓</span></a>
                    <div class="sj-cover-foot"><span class="sj-rule"></span><span>{{ __('ĐỌC CHẬM. CẢM NHẬN SÂU.') }}</span></div>
                </div>
                <div class="sj-cover-art">
                    <img src="{{ asset('images/journal/ritual.webp') }}" alt="{{ __('Khoảnh khắc xịt nước hoa trên cổ tay giữa ánh sáng và lụa hồng') }}" width="1200" height="800" fetchpriority="high">
                    <span class="sj-art-caption">THE SOOPI SCENT LETTERS</span>
                    <div class="sj-cover-letter"><span class="sj-kicker">{{ __('GỬI NGƯỜI YÊU HƯƠNG') }}</span><p>{{ __('Có thể bạn sẽ quên một cái tên.') }}<br><em>{{ __('Nhưng nhớ mãi một mùi hương.') }}</em></p>@include('partials.brand-mark', ['size'=>28])</div>
                </div>
            </header>
            <div class="sj-manifesto" id="loi-vao-cau-chuyen"><span>01 / {{ __('PHÒNG ĐỌC MÙI HƯƠNG') }}</span><p>{{ __('Không cần biết mọi điều về nước hoa.') }}<br><em>{{ __('Chỉ cần bắt đầu từ một chút tò mò.') }}</em></p><a href="#thu-vien-cau-chuyen">{{ __('Xem tất cả bài viết') }} ↗</a></div>
            @php
                $paths = [
                    ['slug'=>'hieu-ba-tang-huong', 'title'=>__('Bên trong một mùi hương'), 'subtitle'=>__('Từ ấn tượng đầu đến dư âm ở lại.'), 'number'=>'01'],
                    ['slug'=>'xit-nuoc-hoa-o-dau', 'title'=>__('Một nghi thức của riêng bạn'), 'subtitle'=>__('Cách dùng hương trong những ngày bình thường.'), 'number'=>'02'],
                    ['slug'=>'bao-quan-nuoc-hoa', 'title'=>__('Giữ gìn điều mình yêu'), 'subtitle'=>__('Chăm chút cho chai hương bạn đã chọn.'), 'number'=>'03'],
                ];
                $paths = array_values(array_filter($paths, fn($path) => $readingPaths->has($path['slug'])));
            @endphp
            @if(count($paths))
                <section class="sj-explorer" data-story-explorer aria-labelledby="sj-explorer-title">
                    <div class="sj-explorer-menu">
                        <p class="sj-kicker">{{ __('BẮT ĐẦU TỪ ĐIỀU BẠN TÒ MÒ') }}</p><h2 id="sj-explorer-title">{{ __('Bạn muốn hiểu') }}<br><em>{{ __('điều gì hôm nay?') }}</em></h2>
                        <div class="sj-paths" data-story-tabs aria-label="{{ __('Chọn lối đọc') }}">
                            @foreach($paths as $path)
                                <a href="#story-path-{{ $loop->index }}" class="sj-path" data-story-tab id="story-tab-{{ $loop->index }}"><span class="sj-path-number">{{ $path['number'] }}</span><span><strong>{{ $path['title'] }}</strong><small>{{ $path['subtitle'] }}</small></span><span class="sj-path-arrow" aria-hidden="true">↗</span></a>
                            @endforeach
                        </div>
                    </div>
                    <div class="sj-explorer-stories">
                        @foreach($paths as $path)
                            @php($story = $readingPaths->get($path['slug']))
                            <article class="sj-path-story" id="story-path-{{ $loop->index }}" data-story-panel aria-labelledby="story-tab-{{ $loop->index }}">
                                <a href="{{ route('store.article', $story->slug) }}" class="sj-path-photo" tabindex="-1" aria-hidden="true"><img src="{{ asset(config('journal-art.'.$story->slug, $story->image_url) ?: 'images/journal/notes.webp') }}" alt="" width="640" height="427" loading="lazy"><span>{{ $path['number'] }} / SOOPI</span></a>
                                <div class="sj-path-copy"><p class="sj-kicker">{{ __('MỘT CÂU CHUYỆN ĐỂ BẮT ĐẦU') }}</p><h3><a href="{{ route('store.article', $story->slug) }}">{{ $story->localized_title }}</a></h3><p>{{ $story->localized_excerpt }}</p><a class="sj-text-link" href="{{ route('store.article', $story->slug) }}">{{ __('Đọc câu chuyện') }} <span aria-hidden="true">↗</span></a></div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif
        <section class="sj-library" id="thu-vien-cau-chuyen" aria-labelledby="sj-library-title">
            <header class="sj-section-heading"><div><p class="sj-kicker">02 / {{ __('NHỮNG TRANG HƯƠNG') }}</p><h{{ $articles->currentPage() === 1 ? '2' : '1' }} id="sj-library-title">{{ __('Mỗi lần đọc,') }} <em>{{ __('một khám phá.') }}</em></h{{ $articles->currentPage() === 1 ? '2' : '1' }}></div><span class="sj-library-count">{{ __('Bài viết') }} <b>{{ str_pad((string)$articles->total(), 2, '0', STR_PAD_LEFT) }}</b></span></header>
            <div class="sj-library-grid {{ in_array($articles->count(), [2, 4]) ? 'sj-library-grid--paired' : '' }}">
                @forelse($articles as $article)
                    @include('store.partials.story-card', ['story'=>$article, 'number'=>$articles->firstItem() + $loop->index])
                @empty
                    <div class="sj-empty">@include('partials.brand-mark', ['size'=>36])<h3>{{ __('Những trang hương đang được viết.') }}</h3><p>{{ __('Bạn ghé lại sau nhé. Trong lúc chờ, cùng khám phá một mùi hương dành cho mình.') }}</p><a class="sj-text-link" href="{{ route('store.finder') }}">{{ __('Tìm mùi hương của bạn ↗') }}</a></div>
                @endforelse
            </div>
            <div class="sj-pagination">{{ $articles->links() }}</div>
        </section>
        @include('store.partials.story-invitation')
    </div>
</div>
@endsection
