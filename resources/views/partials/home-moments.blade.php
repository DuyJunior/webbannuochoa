@php
    $moments = [
        ['key'=>'morning', 'time'=>'08:00', 'label'=>'Một sáng thanh lịch', 'title'=>'Ngày mới.\nMột dấu ấn nhẹ nhàng.', 'copy'=>'Nắng qua rèm cửa. Một chút hương, vừa đủ để bắt đầu.', 'slug'=>'chanel-chance-eau-tendre', 'image'=>'images/gallery/chanel.webp', 'occasion'=>'hang-ngay', 'style'=>'tuoi'],
        ['key'=>'date', 'time'=>'17:30', 'label'=>'Một cuộc hẹn', 'title'=>'Có những lần gặp,\nmuốn nhớ thật lâu.', 'copy'=>'Ánh chiều mềm lại. Để hương thơm kể tiếp câu chuyện của bạn.', 'slug'=>'miss-dior-blooming-bouquet', 'image'=>'images/gallery/miss-dior.webp', 'occasion'=>'hen-ho', 'style'=>'hoa'],
        ['key'=>'evening', 'time'=>'21:00', 'label'=>'Một tối đáng nhớ', 'title'=>'Khi đêm xuống,\ncá tính lên tiếng.', 'copy'=>'Một sắc hương trầm ấm. Một sự hiện diện rất riêng.', 'slug'=>'tom-ford-rose-prick-edp', 'image'=>'images/products/tom-ford-rose-prick.jpg', 'occasion'=>'tiec', 'style'=>'am'],
    ];
    $sampleSelection = $perfumes->where('stock', '>', 0)->take(8);
@endphp
<section class="store-container soopi-moments" id="bo-suu-tap" aria-labelledby="moments-title">
    <header class="moments-heading"><span class="atelier-kicker">SOOPI / THE MOMENTS COLLECTION</span><h2 id="moments-title">Bạn muốn được nhớ đến,<br><em>trong khoảnh khắc nào?</em></h2><p>Một ngày. Nhiều phiên bản của bạn.</p></header>
    <div class="moment-selector" aria-label="Chọn khoảnh khắc">
        @foreach($moments as $moment)<a href="#moment-{{ $moment['key'] }}" data-moment-choice="{{ $moment['key'] }}"><span>{{ $moment['time'] }}</span>{{ $moment['label'] }}<span aria-hidden="true">↗</span></a>@endforeach
    </div>
    <div class="moment-stage">
        @foreach($moments as $moment)
            @php($pick = $perfumes->firstWhere('slug', $moment['slug']))
            <article class="moment-scene moment-{{ $moment['key'] }}" id="moment-{{ $moment['key'] }}" data-moment-panel="{{ $moment['key'] }}" aria-label="{{ $moment['label'] }}">
                <div class="moment-copy"><span class="atelier-kicker">{{ $moment['time'] }} / {{ $moment['label'] }}</span><h3>@foreach(explode('\n', $moment['title']) as $line){{ $line }}@unless($loop->last)<br>@endunless @endforeach</h3><p>{{ $moment['copy'] }}</p>
                    @if($pick)<a class="atelier-button" href="{{ route('perfumes.show', $pick) }}">Khám phá mùi hương này <span aria-hidden="true">↗</span></a><div class="moment-product"><span>{{ $pick->brand }}</span><strong>{{ $pick->name }}</strong><span>{{ number_format($pick->sale_price ?? $pick->price, 0, ',', '.') }}₫</span></div>@endif
                    <a class="atelier-link" href="{{ route('store.finder', ['occasion'=>$moment['occasion'], 'style'=>$moment['style']]) }}">Tìm hương cho khoảnh khắc này ↗</a>
                </div>
                <div class="moment-image"><img src="{{ asset($moment['image']) }}" alt="{{ $pick?->name ?? 'Cảm hứng hương thơm cho '.$moment['label'] }}" width="960" height="960" loading="lazy" decoding="async"><span class="moment-image-caption">SOOPI / {{ $moment['time'] }}</span></div>
            </article>
        @endforeach
    </div>
    <nav class="moment-categories" aria-label="Bộ sưu tập nước hoa"><span>Khám phá theo bộ sưu tập</span><a href="{{ route('home',['gender'=>'nu']) }}#san-pham">Dành cho nàng ↗</a><a href="{{ route('home',['gender'=>'nam']) }}#san-pham">Dành cho chàng ↗</a><a href="{{ route('home',['gender'=>'unisex']) }}#san-pham">Không giới hạn ↗</a></nav>
    <div class="atelier-brands" aria-label="Thương hiệu">@foreach(['DIOR','CHANEL','TOM FORD','NARCISO','ARMANI'] as $brand)<a href="{{ route('home',['search'=>$brand]) }}#san-pham">{{ $brand }}</a>@endforeach</div>
</section>

<section class="store-container soopi-gifting" id="chon-qua" aria-labelledby="gifting-title">
    <div class="gifting-visual"><img src="{{ asset('images/bloom/gift-atelier.webp') }}" alt="Chai nước hoa và hộp quà trắng với dải lụa hồng" width="1440" height="1080" loading="lazy"><div class="gifting-card"><span>GỬI MỘT CHÚT THƯƠNG</span><p data-gift-note-preview>Mong mỗi ngày của bạn đều có một điều dịu dàng.</p><small>with love, Soopi</small></div></div>
    <div class="gifting-copy"><span class="atelier-kicker">SOOPI / THE ART OF GIVING</span><h2 id="gifting-title">Có những điều,<br><em>hương thơm nói hộ.</em></h2><p>Chọn một món quà bắt đầu từ người bạn thương.</p>
        <form action="{{ route('store.finder') }}" method="GET" class="atelier-gift-form">
            <label><span>01 / DÀNH CHO AI?</span><select name="gender"><option value="">Người tôi thương</option><option value="nu">Dành cho nàng</option><option value="nam">Dành cho chàng</option><option value="unisex">Yêu những điều khác biệt</option></select></label>
            <label><span>02 / KHOẢNH KHẮC NÀO?</span><select name="occasion"><option value="hen-ho">Một cuộc hẹn / kỷ niệm</option><option value="hang-ngay">Một món quà mỗi ngày</option><option value="tiec">Sinh nhật / dịp đặc biệt</option></select></label>
            <label><span>03 / NGÂN SÁCH</span><select name="max_price"><option value="">Không giới hạn</option><option value="2000000">Đến 2.000.000₫</option><option value="4000000">Đến 4.000.000₫</option><option value="6000000">Đến 6.000.000₫</option></select></label>
            <label class="gifting-note-label"><span>MỘT LỜI NHẮN CỦA BẠN</span><input type="text" maxlength="120" data-gift-note placeholder="Viết một lời nhắn dịu dàng…" aria-describedby="gift-note-hint"><small id="gift-note-hint">Xem trước trên thiệp, chưa gửi kèm đơn hàng.</small></label>
            <button class="atelier-button" type="submit">Tìm món quà phù hợp <span aria-hidden="true">↗</span></button>
        </form>
    </div>
</section>

<section class="store-container soopi-sampling" id="khoang-thu-huong" aria-labelledby="sampling-title">
    <header><div><span class="atelier-kicker">SOOPI / YOUR SCENT WARDROBE</span><h2 id="sampling-title">Thử một chút.<br><em>Yêu thật lâu.</em></h2></div><p>Chọn những mùi hương bạn tò mò.<br>Đặt vào khay, rồi cảm nhận trên làn da.</p></header>
    <form action="{{ route('store.discovery-box') }}" method="GET" data-sample-form>
        <div class="sample-workbench"><div class="sample-package"><label for="atelier-box-size">Hộp thử của bạn</label><select name="size" id="atelier-box-size"><option value="3">3 mẫu · 199.000₫</option><option value="5">5 mẫu · 299.000₫</option></select><p>Mỗi lựa chọn là một khởi đầu.</p><a class="atelier-link" href="{{ route('store.quiz') }}">Chưa biết chọn? Tìm gu hương ↗</a></div><div class="sample-tray" data-sample-tray aria-label="Các mẫu đã chọn"></div><div class="sample-checkout"><strong data-sample-price>199.000₫</strong><span data-sample-status role="status">Chọn mẫu bên dưới</span><button class="atelier-button" type="submit">Hoàn thiện hộp thử ↗</button></div></div>
        <div class="sample-picks">@forelse($sampleSelection as $sample)<label class="sample-pick"><input type="checkbox" name="samples[]" value="{{ $sample->id }}" data-sample-name="{{ $sample->name }}" data-sample-img="{{ $sample->image_src }}"><img src="{{ $sample->image_src }}" alt="" width="160" height="160" loading="lazy"><span>{{ $sample->brand }}</span><strong>{{ $sample->name }}</strong><small>Chọn mẫu <span aria-hidden="true">+</span></small></label>@empty<p>Khám phá các mẫu tại trang hộp thử.</p>@endforelse</div>
        <noscript><p>Các mẫu đã tích sẽ được chuyển sang trang hoàn thiện hộp thử.</p></noscript>
    </form>
    <a class="atelier-link sampling-all" href="{{ route('store.discovery-box') }}">Xem toàn bộ mẫu hương ↗</a>
</section>
