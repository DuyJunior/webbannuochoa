@php
    $bloomProduct = $perfumes->first(fn ($item) => str_contains(mb_strtolower($item->name), 'miss dior'));
@endphp
<section class="ht-hero bloom-hero petal-hero cinema-hero" id="hoa-huong" data-bloom data-bloom-intro="still" data-mood="rose" aria-labelledby="bloom-title">
    <div class="bloom-ambient" aria-hidden="true"></div>
    <div class="store-container bloom-layout">
        <div class="bloom-copy">
            <span class="bloom-kicker">SOOPI / L’INSTANT PRÉCIEUX</span>
            <h1 id="bloom-title">Một mùi hương.<br><em>Muôn lớp<br> cảm xúc.</em></h1>
            <p>Một khoảnh khắc chạm vào giác quan.<br>Một dấu hương ở lại cùng bạn.</p>
            <div class="bloom-actions">
                <a class="bloom-button" href="#san-pham">Mở bộ sưu tập @include('partials.icon', ['name' => 'external', 'size' => 18])</a>
            </div>
        </div>
        <div class="bloom-art" data-bloom-stage tabindex="0" role="group" aria-label="Vườn hương tương tác. Rê chuột hoặc chạm để tạo làn gió cho cánh hoa.">
            <div class="bloom-halo" aria-hidden="true"></div>
            <div class="bloom-shadow" aria-hidden="true"></div>
            <div class="bloom-sculpture" data-bloom-sculpture>
                <img class="bloom-open" src="{{ asset('images/bloom/cinema-rose-1680.webp') }}" srcset="{{ asset('images/bloom/cinema-rose-960.webp') }} 960w, {{ asset('images/bloom/cinema-rose-1680.webp') }} 1680w" sizes="100vw" width="1672" height="941" alt="Chai Miss Dior pha lê với nơ bạc, giữa những cánh hoa hồng phấn xuyên sáng và sương hoàng hôn" fetchpriority="high" decoding="async" draggable="false">
                <img class="bloom-closed" data-bloom-closed data-src="{{ asset('images/bloom/flower-closed.webp') }}" width="1254" height="1254" alt="" aria-hidden="true" draggable="false">
            </div>
            <div class="cinema-atmosphere" aria-hidden="true"><span></span><i></i></div>
            <span class="bloom-art-hint" data-bloom-hint hidden aria-hidden="true"><span class="bloom-orbit-icon">↻</span><span>Kéo nhẹ<br>để đổi góc nhìn</span></span>
            <div class="bloom-art-caption">
                <a href="{{ $bloomProduct ? route('perfumes.show', $bloomProduct) : route('home', ['search' => 'Dior']).'#san-pham' }}">Miss Dior <em>Blooming Bouquet</em><span aria-hidden="true">↗</span></a>
            </div>
        </div>
        <div class="bloom-edition"><span>01 / HOA HƯƠNG SOOPI</span><span class="bloom-edition-line" aria-hidden="true"><i></i><i></i><i></i></span></div>
        <div class="bloom-tools" data-bloom-tools hidden>
            <button type="button" data-bloom-replay aria-label="Xem lại chuyển động điện ảnh">↻ <span>Xem lại khoảnh khắc</span></button>
            <button type="button" data-bloom-motion aria-pressed="true"><span data-motion-label>Hiệu ứng: bật</span><i aria-hidden="true"></i></button>
        </div>
    </div>
</section>
<section class="scent-discovery" aria-labelledby="scent-discovery-title">
    <div class="store-container scent-discovery-inner">
        <div class="scent-discovery-heading">
            <span class="atelier-kicker">CHẠM ĐỂ ĐỔI SẮC CÁNH HOA</span>
            <h2 id="scent-discovery-title">Bạn là sắc hương nào?</h2>
            <button class="scent-palette-view" type="button" data-palette-view>Xem hoa <span data-palette-name>hồng phấn</span> ↑</button>
        </div>
        <div class="scent-discovery-options" role="group" aria-label="Đổi màu cánh hoa phía trên">
            @foreach([
                ['style' => 'hoa', 'tone' => 'rose', 'name' => 'Dịu dàng', 'description' => 'Hoa cỏ mềm mại'],
                ['style' => 'am', 'tone' => 'velvet', 'name' => 'Cuốn hút', 'description' => 'Ấm áp & sâu lắng'],
                ['style' => 'tuoi', 'tone' => 'sage', 'name' => 'Tự do', 'description' => 'Tươi mát, nhẹ tênh'],
            ] as $mood)
            <button class="scent-discovery-link" type="button" data-bloom-mood="{{ $mood['tone'] }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-controls="hoa-huong mood-collection-title">
                <span class="scent-discovery-petal scent-discovery-petal-{{ $mood['tone'] }}" aria-hidden="true"></span>
                <span><strong>{{ $mood['name'] }}</strong><small>{{ $mood['description'] }}</small></span>
                <span class="scent-palette-check" aria-hidden="true">✓</span>
            </button>
            @endforeach
        </div>
        <span class="scent-palette-status" data-palette-status role="status" aria-live="polite"></span>
    </div>
</section>
@include('partials.mood-collection')
