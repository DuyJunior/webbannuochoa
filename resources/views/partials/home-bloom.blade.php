@php
    $bloomProduct = $perfumes->first(fn ($item) => str_contains(mb_strtolower($item->name), 'miss dior'));
@endphp
<section class="ht-hero bloom-hero" id="hoa-huong" data-bloom data-mood="rose" aria-labelledby="bloom-title">
    <div class="bloom-ambient" aria-hidden="true"></div>
    <div class="store-container bloom-layout">
        <div class="bloom-copy">
            <h1 id="bloom-title">Một mùi hương.<br><em>Muôn lớp cảm xúc.</em></h1>
            <p>Chạm để mở thế giới của bạn.</p>
            <div class="bloom-actions">
                <a class="bloom-button" href="#san-pham">Khám phá bộ sưu tập @include('partials.icon', ['name' => 'arrow', 'size' => 18])</a>
            </div>
        </div>
        <div class="bloom-art" data-bloom-stage tabindex="0" role="group" aria-label="Hoa hương tương tác. Dùng phím mũi tên để đổi góc nhìn, Escape để đặt lại.">
            <div class="bloom-halo" aria-hidden="true"></div>
            <div class="bloom-shadow" aria-hidden="true"></div>
            <div class="bloom-sculpture" data-bloom-sculpture>
                <img class="bloom-open" src="{{ asset('images/bloom/flower-open.webp') }}" width="1254" height="1254" alt="Chai Miss Dior giữa những cánh hoa lụa hồng xếp nếp" fetchpriority="high" decoding="async" draggable="false">
                <img class="bloom-petal-tint" src="{{ asset('images/bloom/flower-open.webp') }}" width="1254" height="1254" alt="" aria-hidden="true" draggable="false">
                <img class="bloom-closed" data-bloom-closed data-src="{{ asset('images/bloom/flower-closed.webp') }}" width="1254" height="1254" alt="" aria-hidden="true" draggable="false">
            </div>
            <span class="bloom-art-hint" data-bloom-hint hidden aria-hidden="true"><span class="bloom-orbit-icon">↻</span><span>Kéo nhẹ<br>để đổi góc nhìn</span></span>
            <div class="bloom-art-caption">
                <a href="{{ $bloomProduct ? route('perfumes.show', $bloomProduct) : route('home', ['search' => 'Dior']).'#san-pham' }}">Miss Dior <em>Blooming Bouquet</em><span aria-hidden="true">↗</span></a>
            </div>
        </div>
        <div class="bloom-edition"><span>01 / HOA HƯƠNG SOOPI</span><span class="bloom-edition-line" aria-hidden="true"><i></i><i></i><i></i></span></div>
        <div class="bloom-tools" data-bloom-tools hidden>
            <button type="button" data-bloom-replay aria-label="Xem lại hiệu ứng hoa hé nở">↻ <span>Hoa hé nở</span></button>
            <button type="button" data-bloom-motion aria-pressed="true"><span data-motion-label>Hiệu ứng: bật</span><i aria-hidden="true"></i></button>
        </div>
    </div>
</section>
<section class="bloom-scenes" aria-labelledby="bloom-scenes-title">
    <div class="store-container">
        <div class="bloom-scenes-heading"><h2 id="bloom-scenes-title">Một bông hoa. Ba khoảnh khắc.</h2><span aria-hidden="true"></span></div>
        <div class="bloom-scenes-grid">
            <a class="bloom-scene" href="#hoa-huong" data-bloom-scene="open">
                <div class="bloom-scene-image bloom-scene-bud"><img src="{{ asset('images/bloom/flower-closed.webp') }}" alt="Nụ hoa lụa ôm lấy chai nước hoa" width="640" height="640" loading="lazy"></div>
                <div class="bloom-scene-heading"><h3>01 / HÉ NỞ</h3><span aria-hidden="true"><i></i><i></i><i></i></span></div>
                <p>Cánh lụa mở, để lộ chai nước hoa.</p>
            </a>
            <a class="bloom-scene" href="#hoa-huong" data-bloom-scene="turn">
                <div class="bloom-scene-image bloom-scene-open"><img src="{{ asset('images/bloom/flower-open.webp') }}" alt="Góc nhìn cận cảnh hoa hương hé mở" width="640" height="640" loading="lazy"></div>
                <div class="bloom-scene-heading"><h3>02 / CHẠM & XOAY</h3><span aria-hidden="true"><i></i><i></i><i></i></span></div>
                <p>Góc nhìn đổi nhẹ theo con trỏ.</p>
            </a>
            <a class="bloom-scene" href="#san-pham">
                <div class="bloom-scene-image"><img src="{{ asset('images/bloom/collection-reveal.webp') }}" alt="Minh họa những chai nước hoa dưới cánh lụa hồng" width="640" height="640" loading="lazy"></div>
                <div class="bloom-scene-heading"><h3>03 / MỞ BỘ SƯU TẬP</h3><span aria-hidden="true"><i></i><i></i><i></i></span></div>
                <p>Cuộn để bước sang bộ sưu tập.</p>
            </a>
        </div>
    </div>
</section>
<section class="bloom-moods" aria-labelledby="bloom-moods-title">
    <div class="store-container bloom-moods-inner">
        <p class="bloom-material-note">Chất liệu cánh hoa<br>thay đổi theo cảm xúc.</p>
        <div class="bloom-mood-options" role="group" aria-label="Đổi sắc không gian hoa hương">
            <button class="bloom-mood is-selected" type="button" data-bloom-mood="rose" aria-pressed="true"><span class="bloom-swatch bloom-swatch-rose" aria-hidden="true"></span><span><small>01 / LỤA HỒNG</small><strong>Dịu dàng</strong></span><i aria-hidden="true">↗</i></button>
            <button class="bloom-mood" type="button" data-bloom-mood="velvet" aria-pressed="false"><span class="bloom-swatch bloom-swatch-velvet" aria-hidden="true"></span><span><small>02 / NHUNG ĐỎ</small><strong>Cuốn hút</strong></span><i aria-hidden="true">↗</i></button>
            <button class="bloom-mood" type="button" data-bloom-mood="sage" aria-pressed="false"><span class="bloom-swatch bloom-swatch-sage" aria-hidden="true"></span><span><small>03 / VOAN XANH</small><strong>Tự do</strong></span><i aria-hidden="true">↗</i></button>
        </div>
        <div class="bloom-moods-heading"><h2 id="bloom-moods-title">Chọn cảm xúc,<br>đổi sắc cánh hoa.</h2></div>
    </div>
    <div class="store-container bloom-mood-note"><p data-bloom-mood-note aria-live="polite">Một khoảng trời mềm mại, dành cho những điều dịu dàng.</p><a href="{{ route('store.finder') }}">Tìm hương của bạn <span aria-hidden="true">↗</span></a></div>
</section>
