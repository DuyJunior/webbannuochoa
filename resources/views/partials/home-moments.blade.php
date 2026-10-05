@php
    $moments = [
        ['key'=>'morning', 'time'=>'08:00', 'label'=>__('Một sáng thanh lịch'), 'title'=>__('Ngày mới.\nMột dấu ấn nhẹ nhàng.'), 'copy'=>__('Nắng qua rèm cửa. Một chút hương, vừa đủ để bắt đầu.'), 'slug'=>'chanel-chance-eau-tendre', 'image'=>'images/gallery/chanel.webp', 'occasion'=>'hang-ngay', 'style'=>'tuoi'],
        ['key'=>'date', 'time'=>'17:30', 'label'=>__('Một cuộc hẹn'), 'title'=>__('Có những lần gặp,\nmuốn nhớ thật lâu.'), 'copy'=>__('Ánh chiều mềm lại. Để hương thơm kể tiếp câu chuyện của bạn.'), 'slug'=>'miss-dior-blooming-bouquet', 'image'=>'images/gallery/miss-dior.webp', 'occasion'=>'hen-ho', 'style'=>'hoa'],
        ['key'=>'evening', 'time'=>'21:00', 'label'=>__('Một tối đáng nhớ'), 'title'=>__('Khi đêm xuống,\ncá tính lên tiếng.'), 'copy'=>__('Một sắc hương trầm ấm. Một sự hiện diện rất riêng.'), 'slug'=>'tom-ford-rose-prick-edp', 'image'=>'images/products/tom-ford-rose-prick.jpg', 'occasion'=>'tiec', 'style'=>'am'],
    ];
@endphp
<section class="store-container soopi-moments" id="bo-suu-tap" aria-labelledby="moments-title">
    <header class="moments-heading"><span class="atelier-kicker">SOOPI / THE MOMENTS COLLECTION</span><h2 id="moments-title">{{ __('Bạn muốn được nhớ đến,') }}<br><em>{{ __('trong khoảnh khắc nào?') }}</em></h2><p>{{ __('Một ngày. Nhiều phiên bản của bạn.') }}</p></header>
    <div class="moment-selector" aria-label="{{ __('Chọn khoảnh khắc') }}">
        @foreach($moments as $moment)<a href="#moment-{{ $moment['key'] }}" data-moment-choice="{{ $moment['key'] }}"><span>{{ $moment['time'] }}</span>{{ $moment['label'] }}<span aria-hidden="true">↗</span></a>@endforeach
    </div>
    <div class="moment-stage">
        @foreach($moments as $moment)
            @php($pick = $perfumes->firstWhere('slug', $moment['slug']))
            <article class="moment-scene moment-{{ $moment['key'] }}" id="moment-{{ $moment['key'] }}" data-moment-panel="{{ $moment['key'] }}" aria-label="{{ $moment['label'] }}">
                <div class="moment-copy"><span class="atelier-kicker">{{ $moment['time'] }} / {{ $moment['label'] }}</span><h3>@foreach(explode('\n', $moment['title']) as $line){{ $line }}@unless($loop->last)<br>@endunless @endforeach</h3><p>{{ $moment['copy'] }}</p>
                    @if($pick)<a class="atelier-button" href="{{ route('perfumes.show', $pick) }}">{{ __('Khám phá mùi hương này') }} <span aria-hidden="true">↗</span></a><div class="moment-product"><span>{{ $pick->brand }}</span><strong>{{ $pick->localized_name }}</strong><span>{{ number_format($pick->sale_price ?? $pick->price, 0, ',', '.') }}₫</span></div>@endif
                    <a class="atelier-link" href="{{ route('store.finder', ['occasion'=>$moment['occasion'], 'style'=>$moment['style']]) }}">{{ __('Tìm hương cho khoảnh khắc này ↗') }}</a>
                </div>
                <div class="moment-image"><img src="{{ asset($moment['image']) }}" alt="{{ $pick?->localized_name ?? 'Cảm hứng hương thơm cho '.$moment['label'] }}" width="960" height="960" loading="lazy" decoding="async"><span class="moment-image-caption">SOOPI / {{ $moment['time'] }}</span></div>
            </article>
        @endforeach
    </div>
    <nav class="moment-categories" aria-label="{{ __('Bộ sưu tập nước hoa') }}"><span>{{ __('Khám phá theo bộ sưu tập') }}</span><a href="{{ route('home',['gender'=>'nu']) }}#san-pham">{{ __('Dành cho nàng ↗') }}</a><a href="{{ route('home',['gender'=>'nam']) }}#san-pham">{{ __('Dành cho chàng ↗') }}</a><a href="{{ route('home',['gender'=>'unisex']) }}#san-pham">{{ __('Không giới hạn ↗') }}</a></nav>
    <div class="atelier-brands" aria-label="{{ __('Thương hiệu') }}">@foreach(['DIOR','CHANEL','TOM FORD','NARCISO','ARMANI'] as $brand)<a href="{{ route('home',['search'=>$brand]) }}#san-pham">{{ $brand }}</a>@endforeach</div>
</section>

<section class="store-container soopi-gifting" id="chon-qua" aria-labelledby="gifting-title">
    <div class="gifting-visual"><img src="{{ asset('images/bloom/gift-atelier.webp') }}" alt="{{ __('Chai nước hoa và hộp quà trắng với dải lụa hồng') }}" width="1440" height="1080" loading="lazy"><div class="gifting-card"><span>{{ __('GỬI MỘT CHÚT THƯƠNG') }}</span><p data-gift-note-preview>{{ __('Mong mỗi ngày của bạn đều có một điều dịu dàng.') }}</p><small>with love, Soopi</small></div></div>
    <div class="gifting-copy"><span class="atelier-kicker">SOOPI / THE ART OF GIVING</span><h2 id="gifting-title">{{ __('Có những điều,') }}<br><em>{{ __('hương thơm nói hộ.') }}</em></h2><p>{{ __('Chọn một món quà bắt đầu từ người bạn thương.') }}</p>
        <form action="{{ route('store.gift-finder') }}" method="POST" class="atelier-gift-form">
            @csrf
            <label><span>{{ __('01 / DÀNH CHO AI?') }}</span><select name="gender"><option value="">{{ __('Người tôi thương') }}</option><option value="nu">{{ __('Dành cho nàng') }}</option><option value="nam">{{ __('Dành cho chàng') }}</option><option value="unisex">{{ __('Yêu những điều khác biệt') }}</option></select></label>
            <label><span>{{ __('02 / KHOẢNH KHẮC NÀO?') }}</span><select name="occasion"><option value="hen-ho">{{ __('Một cuộc hẹn / kỷ niệm') }}</option><option value="hang-ngay">{{ __('Một món quà mỗi ngày') }}</option><option value="tiec">{{ __('Sinh nhật / dịp đặc biệt') }}</option></select></label>
            <label><span>{{ __('03 / NGÂN SÁCH') }}</span><select name="max_price"><option value="">{{ __('Không giới hạn') }}</option><option value="2000000">{{ __('Đến 2.000.000₫') }}</option><option value="4000000">{{ __('Đến 4.000.000₫') }}</option><option value="6000000">{{ __('Đến 6.000.000₫') }}</option></select></label>
            <label class="gifting-note-label"><span>{{ __('MỘT LỜI NHẮN CỦA BẠN') }}</span><input type="text" name="gift_message" maxlength="120" data-gift-note placeholder="{{ __('Viết một lời nhắn dịu dàng…') }}" aria-describedby="gift-note-hint"><small id="gift-note-hint">{{ __('Lời nhắn được giữ để điền sẵn khi thanh toán. Bạn xác nhận thiệp quà trước khi đặt hàng.') }}</small></label>
            <button class="atelier-button" type="submit">{{ __('Tìm món quà phù hợp') }} <span aria-hidden="true">↗</span></button>
        </form>
    </div>
</section>

@include('partials.home-sampling')
