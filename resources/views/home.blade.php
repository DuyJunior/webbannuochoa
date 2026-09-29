@extends('layouts.store')

@section('title', 'Soopi · Hương thơm của riêng bạn')

@section('content')
@php
    $isFiltered = request()->hasAny(['search', 'gender', 'category', 'sort', 'min_price', 'max_price', 'concentration', 'note', 'style', 'longevity']);
    $selectedCategory = request('category') ? $categories->firstWhere('id', (int) request('category')) : null;
    $collectionTitle = request()->filled('search') ? 'Mùi hương bạn đang tìm'
        : ($selectedCategory?->name ?? match (request('gender')) {
            'nam' => 'Nước hoa dành cho chàng',
            'nu' => 'Nước hoa dành cho nàng',
            'unisex' => 'Hương thơm không giới hạn',
            default => 'Nước hoa nổi bật',
        });
@endphp

<section id="home-live-banner" class="store-container ht-home-live" data-state-url="{{ route('livestream.state') }}" data-current-id="{{ $onAir ? $livestream->id : '' }}" @unless($onAir) hidden @endunless aria-live="polite">
    <div>
        <span class="ht-home-live-badge">● ĐANG TRỰC TIẾP</span>
        <strong>{{ $onAir ? $livestream->title : 'Soopi đang livestream' }}</strong>
        <span>Xem và trò chuyện cùng Soopi ngay trên website.</span>
    </div>
    <a class="ht-button" href="{{ route('livestream.show') }}">Xem livestream →</a>
</section>

@if(!$isFiltered)
<section class="ht-hero">
    <div class="store-container ht-hero-grid">
        <div class="ht-hero-copy">
            <span class="ht-eyebrow"><span class="ht-small-line"></span> THẾ GIỚI HƯƠNG THƠM CỦA SOOPI</span>
            <h1>Một chút hương,<br>một chút <em>thương.</em></h1>
            <p>Để mỗi ngày đều có một dấu ấn thật riêng.<br>Khám phá những mùi hương được yêu, dành cho phiên bản đẹp nhất của bạn.</p>
            <div class="ht-hero-actions">
                <a class="ht-button" href="{{ route('store.finder') }}">Tìm hương của bạn @include('partials.icon', ['name' => 'arrow', 'size' => 18])</a>
                <a class="ht-text-link" href="#bo-suu-tap">Xem bộ sưu tập</a>
            </div>
            <div class="ht-hero-note">@include('partials.icon', ['name' => 'flower', 'size' => 27])<span>Không chỉ là nước hoa.<br><strong>Là cảm xúc bạn mang theo.</strong></span></div>
        </div>
        <div class="ht-hero-visual">
            <div class="ht-hero-photo">
                <img src="{{ asset('images/products/miss-dior-blooming.jpg') }}" alt="Chai Miss Dior hồng dịu giữa hoa hồng và lụa mềm" width="720" height="720" fetchpriority="high">
                <a class="ht-photo-label" href="{{ route('home', ['search' => 'Dior']) }}#san-pham"><span>HƯƠNG HOA ĐƯỢC YÊU<STRONG>Miss Dior Blooming Bouquet</STRONG></span>@include('partials.icon', ['name' => 'arrow'])</a>
            </div>
            <span class="ht-hero-side-note">A LITTLE SCENT, A LITTLE LOVE.</span>
            <div class="ht-photo-caption"><span>THE SOOPI EDIT</span><span>Nhẹ nhàng. Tinh tế. Rất riêng.</span></div>
        </div>
    </div>
</section>
<div class="store-container ht-benefits" aria-label="Cam kết của cửa hàng">
    <div>@include('partials.icon', ['name' => 'shield', 'size' => 25])<span><strong>Nước hoa chính hãng</strong><small>An tâm với từng lựa chọn</small></span></div>
    <div>@include('partials.icon', ['name' => 'gift', 'size' => 25])<span><strong>Gói trọn yêu thương</strong><small>Chăm chút từng món quà</small></span></div>
    <div>@include('partials.icon', ['name' => 'truck', 'size' => 25])<span><strong>Giao hàng tận nơi</strong><small>Đóng gói an toàn, cẩn thận</small></span></div>
    <div>@include('partials.icon', ['name' => 'heart', 'size' => 25])<span><strong>Tư vấn tận tâm</strong><small>Cùng bạn chọn hương phù hợp</small></span></div>
</div>

{{-- ── NỔI BẬT: SOOPI FRAGRANCE SHORTS (VIDEO TRẢI NGHIỆM 30S) ── --}}
@php
    $homeVideos = \App\Models\Video::with('perfume')->where('is_active', true)->whereIn('placement', ['home', 'all'])->orderBy('sort_order')->take(8)->get();
@endphp
@if($homeVideos->isNotEmpty())
<section class="store-container ht-shorts-showcase">
    <div class="ht-shorts-header">
        <div class="ht-shorts-title-wrap">
            <span class="ht-shorts-live-pill"><span class="pulse-dot"></span> VIDEO REVIEW 30S</span>
            <h2>Soopi Fragrance Shorts</h2>
            <p>Trải nghiệm chân thật: Unboxing, độ tỏa hương và góc quay cận cảnh từng chai nước hoa.</p>
        </div>
        <div class="ht-shorts-nav-hint">
            <span class="ht-hint-text">Chạm để xem video & đặt mua nhanh @include('partials.icon', ['name' => 'bag', 'size' => '1em'])</span>
        </div>
    </div>
    <div class="ht-shorts-grid">
        @foreach($homeVideos as $vid)
        <div class="short-card js-open-video"
             data-title="{{ $vid->title }}"
             data-embed="{{ $vid->embed_url }}"
             data-desc="{{ $vid->description }}"
             data-views="{{ $vid->formatted_views }}"
             data-perfume-name="{{ $vid->perfume ? $vid->perfume->name : '' }}"
             data-perfume-brand="{{ $vid->perfume ? $vid->perfume->brand : '' }}"
             data-perfume-price="{{ $vid->perfume ? number_format($vid->perfume->price) . 'đ' : '' }}"
             data-perfume-url="{{ $vid->perfume ? route('perfumes.show', $vid->perfume) : '' }}"
             data-perfume-img="{{ $vid->perfume ? $vid->perfume->image_src : '' }}"
             role="button"
             tabindex="0">
            <div class="short-thumb">
                <img src="{{ $vid->thumbnail_src }}" alt="{{ $vid->title }}" loading="lazy">
                <div class="short-overlay">
                    <span class="play-btn">@include('partials.icon', ['name' => 'play', 'size' => '1em'])</span>
                    <span class="views">@include('partials.icon', ['name' => 'flame', 'size' => '1em']) {{ $vid->formatted_views }}</span>
                    <span class="duration-badge">{{ $vid->duration ?: '0:45' }}</span>
                </div>
            </div>
            <div class="short-info">
                @if($vid->perfume)
                    <span class="short-brand">{{ $vid->perfume->brand }}</span>
                @endif
                <strong>{{ $vid->title }}</strong>
            </div>
        </div>
        @endforeach
    </div>
</section>
@endif

@endif

<section class="store-container ht-products-section" id="san-pham">
    <div class="ht-section-heading">
        <div><span class="ht-eyebrow">{{ $isFiltered ? 'KHÁM PHÁ CÙNG SOOPI' : 'NHỮNG MÙI HƯƠNG ĐÁNG THỬ' }}</span><h2>{{ $collectionTitle }}</h2>
            @if(request()->filled('search'))<p>Kết quả cho “{{ request('search') }}” · {{ $perfumes->count() }} sản phẩm hiển thị</p>
            @else<p>Mỗi mùi hương, một cách để thể hiện chính mình.</p>@endif
        </div>
        @if($isFiltered)<a class="ht-text-link" href="{{ route('home') }}#san-pham">Xem tất cả @include('partials.icon', ['name' => 'arrow', 'size' => 18])</a>
        @else<span class="ht-section-index">01 / THE COLLECTION</span>@endif
    </div>
    <nav class="ht-product-tabs" aria-label="Sắp xếp sản phẩm">
        <a @class(['active' => !request('sort')]) href="{{ route('home', request()->except('sort')) }}#san-pham">Mới nhất</a>
        <a @class(['active' => request('sort') === 'sale']) href="{{ route('home', array_merge(request()->except('sort'), ['sort' => 'sale'])) }}#san-pham">Đang ưu đãi</a>
        <a @class(['active' => request('sort') === 'price_asc']) href="{{ route('home', array_merge(request()->except('sort'), ['sort' => 'price_asc'])) }}#san-pham">Giá tăng dần</a>
        <a @class(['active' => request('sort') === 'price_desc']) href="{{ route('home', array_merge(request()->except('sort'), ['sort' => 'price_desc'])) }}#san-pham">Giá giảm dần</a>
    </nav>
    <form class="ht-filter-form" method="GET" action="{{ route('home') }}#san-pham">
        <label>Hương nổi bật<input name="note" type="search" value="{{ request('note') }}" placeholder="Hoa hồng, vanilla, gỗ..."></label>
        <label>Phong cách<select name="style"><option value="">Tất cả</option><option value="Dịu dàng" @selected(request('style') === 'Dịu dàng')>Dịu dàng</option><option value="Lịch lãm" @selected(request('style') === 'Lịch lãm')>Lịch lãm</option><option value="Thanh lịch" @selected(request('style') === 'Thanh lịch')>Thanh lịch</option></select></label>
        <label>Nồng độ<select name="concentration"><option value="">Tất cả</option><option value="EDT" @selected(request('concentration') === 'EDT')>EDT</option><option value="EDP" @selected(request('concentration') === 'EDP')>EDP</option><option value="Parfum" @selected(request('concentration') === 'Parfum')>Parfum</option></select></label>
        <label>Lưu hương ước tính<select name="longevity"><option value="">Tất cả</option><option value="light" @selected(request('longevity') === 'light')>Nhẹ · khoảng 6–8 giờ</option><option value="medium" @selected(request('longevity') === 'medium')>Vừa · khoảng 8–10 giờ</option><option value="strong" @selected(request('longevity') === 'strong')>Đậm · từ 10 giờ</option></select></label>
        <label>Giá từ<input name="min_price" type="number" min="0" value="{{ request('min_price') }}" placeholder="0 ₫"></label>
        <label>Đến<input name="max_price" type="number" min="0" value="{{ request('max_price') }}" placeholder="Không giới hạn"></label>
        <button class="ht-button" type="submit">Lọc sản phẩm</button>
    </form>
    <div class="store-products">
        @forelse($perfumes as $perfume)
        <article class="store-product-card">
            <a class="store-product-image" href="{{ route('perfumes.show', $perfume) }}" aria-label="Xem {{ $perfume->name }}">
                @if($perfume->image_src)
                    <img src="{{ $perfume->image_src }}" alt="{{ $perfume->name }}" width="600" height="600" loading="lazy" decoding="async">
                @else
                    <div class="ht-product-placeholder">@include('partials.icon', ['name' => 'flower', 'size' => 56])<span>{{ $perfume->brand }}</span></div>
                @endif
                @if($perfume->sale_price !== null && $perfume->sale_price < $perfume->price && $perfume->price > 0)
                    <small>−{{ round((1 - $perfume->sale_price / $perfume->price) * 100) }}%</small>
                @endif
                <span class="ht-product-open" aria-hidden="true">@include('partials.icon', ['name' => 'arrow', 'size' => 18])</span>
            </a>
            <div class="store-product-info">
                <span>{{ $perfume->brand }}</span>
                <h3><a href="{{ route('perfumes.show', $perfume) }}">{{ $perfume->name }}</a></h3>
                <p>{{ $perfume->volume_ml }} ml <span>·</span> {{ $perfume->concentration ?: 'Nước hoa' }}</p>
                <div class="ht-product-bottom">
                    <div class="ht-product-price"><strong>{{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}₫</strong>@if($perfume->sale_price !== null && $perfume->sale_price < $perfume->price)<del>{{ number_format((float) $perfume->price, 0, ',', '.') }}₫</del>@endif</div>
                    <a class="ht-product-detail" href="{{ route('perfumes.show', $perfume) }}" aria-label="Chọn dung tích {{ $perfume->name }}">@include('partials.icon', ['name' => 'bag', 'size' => 18])</a>
                </div>
                <button type="button" class="ht-compare-add" data-compare-id="{{ $perfume->id }}" aria-label="Thêm {{ $perfume->name }} vào so sánh">+ So sánh</button>
            </div>
        </article>
        @empty
        <div class="store-empty">@include('partials.icon', ['name' => 'search', 'size' => 42])<h3>Chưa tìm thấy mùi hương phù hợp</h3><p>Thử một tên nước hoa, thương hiệu hoặc danh mục khác nhé.</p><a class="ht-button" href="{{ route('home') }}#san-pham">Khám phá tất cả nước hoa</a></div>
        @endforelse
    </div>
</section>
<div class="ht-compare-bar" id="htCompareBar" hidden><span id="htCompareCount">0/3 sản phẩm</span><a href="{{ route('store.compare') }}" id="htCompareLink">Xem so sánh →</a><button type="button" id="htCompareClear">Xóa</button></div>

@if(!$isFiltered)
<section class="ht-collections-section" id="bo-suu-tap">
    <div class="store-container">
        <div class="ht-section-heading"><div><span class="ht-eyebrow">MÙI HƯƠNG NÓI LÊN BẠN</span><h2>Một thế giới. Nhiều sắc hương.</h2></div><span class="ht-section-index">02 / FIND YOUR SCENT</span></div>
        <div class="ht-collections">
            <a class="ht-collection-card" href="{{ route('home', ['gender' => 'nu']) }}#san-pham">
                <img src="{{ asset('images/products/delina-exclusif.jpg') }}" alt="Nước hoa Delina sắc hồng bên những cánh hoa" loading="lazy" width="600" height="600">
                <div><span>DỊU DÀNG & CUỐN HÚT</span><h3>Dành cho nàng</h3><p>Để mỗi khoảnh khắc thêm rạng rỡ.</p><span class="ht-collection-arrow">@include('partials.icon', ['name' => 'arrow'])</span></div>
            </a>
            <a class="ht-collection-card" href="{{ route('home', ['gender' => 'nam']) }}#san-pham">
                <img src="{{ asset('images/products/black-gold.jpg') }}" alt="Nước hoa sắc đen với những chi tiết vàng ấm" loading="lazy" width="600" height="600">
                <div><span>LỊCH LÃM & BẢN LĨNH</span><h3>Dành cho chàng</h3><p>Một dấu ấn khó có thể nhầm lẫn.</p><span class="ht-collection-arrow">@include('partials.icon', ['name' => 'arrow'])</span></div>
            </a>
            <a class="ht-collection-card" href="{{ route('home', ['gender' => 'unisex']) }}#san-pham">
                <img src="{{ asset('images/products/tom-ford-rose-prick.jpg') }}" alt="Chai Tom Ford Rose Prick hồng thanh lịch" loading="lazy" width="600" height="600">
                <div><span>TỰ DO & KHÁC BIỆT</span><h3>Không giới hạn</h3><p>Hương thơm dành cho mọi cá tính.</p><span class="ht-collection-arrow">@include('partials.icon', ['name' => 'arrow'])</span></div>
            </a>
        </div>
    </div>
</section>
<section class="store-container ht-brands-section" aria-label="Khám phá thương hiệu">
    <span class="ht-eyebrow">NHỮNG TÊN TUỔI LÀM NÊN CẢM XÚC</span>
    <div class="ht-brands">
        @foreach(['DIOR', 'CHANEL', 'TOM FORD', 'NARCISO', 'ARMANI'] as $brand)
            <a href="{{ route('home', ['search' => $brand]) }}#san-pham">{{ $brand }}</a>
        @endforeach
    </div>
</section>
<section class="store-container ht-story">
    <div class="ht-story-image"><img src="{{ asset('images/perfume-hero.jpg') }}" alt="Bộ sưu tập nước hoa được đặt bên những đóa hoa trắng" loading="lazy" width="900" height="600"></div>
    <div class="ht-story-copy"><span class="ht-eyebrow">TỪ SOOPI, VỚI YÊU THƯƠNG</span><h2>Một món quà nhỏ.<br>Một cảm xúc <em>thật lâu.</em></h2><p>Tặng người thương, hay dành tặng chính mình. Một chai nước hoa là lời nhắn dịu dàng, gói ghém những điều đôi khi khó nói thành lời.</p><a class="ht-button ht-button-light" href="{{ route('home', ['sort' => 'sale']) }}#san-pham">Chọn một món quà @include('partials.icon', ['name' => 'arrow', 'size' => 18])</a><span class="ht-story-signature">with love, Soopi</span></div>
</section>

{{-- ── 1. FLASH SALE COUNTDOWN (ĐỒNG HỒ ĐẾM NGƯỢC GIỚI HẠN) ── --}}
<section class="store-container ht-flash-sale-section">
    <div class="ht-flash-card">
        <div class="flash-left">
            <span class="flash-badge">@include('partials.icon', ['name' => 'bolt', 'size' => '1em']) FLASH SALE ĐẶC BIỆT · GIỚI HẠN 24H</span>
            <h2>Bộ Sưu Tập Giới Hạn Mùa Hoa</h2>
            <p>Chỉ áp dụng trong hôm nay cho 20 khách hàng đầu tiên. Tặng kèm hộp quà lụa và 02 mẫu thử 5ml.</p>
            
            <div class="countdown-timer" id="flashCountdown">
                <div class="time-block"><span class="time-num" id="cdHours">08</span><span class="time-lbl">Giờ</span></div>
                <span class="time-colon">:</span>
                <div class="time-block"><span class="time-num" id="cdMinutes">45</span><span class="time-lbl">Phút</span></div>
                <span class="time-colon">:</span>
                <div class="time-block"><span class="time-num" id="cdSeconds">19</span><span class="time-lbl">Giây</span></div>
            </div>

            <div class="flash-progress-wrap">
                <div class="progress-bar"><div class="fill" style="width: 78%"></div></div>
                <span class="progress-text">@include('partials.icon', ['name' => 'flame', 'size' => '1em']) Đã bán 78% · Chỉ còn 5 chai cuối cùng</span>
            </div>
            
            <div style="margin-top: 18px;">
                <a href="{{ route('home', ['sort' => 'sale']) }}#san-pham" class="ht-button ht-button-primary">Săn Deal Ngay Hôm Nay →</a>
            </div>
        </div>
        <div class="flash-right">
            <div class="flash-img-wrap">
                <img src="{{ asset('images/products/narciso-musc-noir-rose.jpg') }}" alt="Flash sale perfume" loading="lazy">
                <span class="flash-discount-tag">-25% OFF</span>
            </div>
        </div>
    </div>
</section>

{{-- ── 2. TRẢI NGHIỆM ĐỘC BẢN: QUIZ & HỘP THỬ MÙI BANNERS ── --}}
<section class="store-container ht-experience-banners">
    <div class="exp-banner-card quiz-card">
        <span class="exp-badge">@include('partials.icon', ['name' => 'flower', 'size' => '1em']) CHỈ MẤT 60 GIÂY</span>
        <h3>Trắc Nghiệm Chọn Hương Theo Tính Cách</h3>
        <p>Thuật toán thông minh sẽ gợi ý chai nước hoa phù hợp nhất với thời tiết, dịp dùng và thần thái của riêng bạn.</p>
        <a href="{{ route('store.quiz') }}" class="ht-button ht-button-primary">Làm Trắc Nghiệm Ngay →</a>
    </div>
    <div class="exp-banner-card box-card">
        <span class="exp-badge">@include('partials.icon', ['name' => 'gift', 'size' => '1em']) CHỌN 3 - 5 MẪU CHIẾT</span>
        <h3>Tự Thiết Kế "Hộp Thử Mùi" (Discovery Box)</h3>
        <p>Trải nghiệm trước khi mua fullbox. Tự tay chọn 3-5 ống chiết cao cấp kèm voucher hoàn tiền 100K.</p>
        <a href="{{ route('store.discovery-box') }}" class="ht-button ht-button-secondary">Thiết Kế Hộp Thử @include('partials.icon', ['name' => 'box', 'size' => '1em'])</a>
    </div>
</section>


{{-- ── 4. SẢN PHẨM VỪA XEM GẦN ĐÂY (RECENTLY VIEWED) ── --}}
@php
    $recentIds = session('recently_viewed', []);
    $recentPerfumes = !empty($recentIds) ? \App\Models\Perfume::whereIn('id', array_slice($recentIds, 0, 4))->where('is_active', true)->get() : collect();
@endphp
@if($recentPerfumes->isNotEmpty())
<section class="store-container ht-related-section ht-home-recent">
    <div class="ht-section-heading">
        <div>
            <span class="ht-eyebrow">DÀNH RIÊNG CHO BẠN</span>
            <h2>Sản phẩm bạn vừa xem gần đây</h2>
        </div>
    </div>
    <div class="ht-related-grid">
        @foreach($recentPerfumes as $recent)
        <a href="{{ route('perfumes.show', $recent) }}" class="ht-related-card">
            <div class="ht-related-img">
                <img src="{{ $recent->image_src ?: asset('images/perfume-default.jpg') }}" alt="{{ $recent->name }}" loading="lazy">
            </div>
            <div class="ht-related-info">
                <span class="ht-related-brand">{{ $recent->brand }}</span>
                <strong class="ht-related-name">{{ $recent->name }}</strong>
                <div class="ht-related-price">
                    <span class="ht-related-current">{{ number_format((float)($recent->sale_price ?? $recent->price), 0, ',', '.') }}₫</span>
                </div>
            </div>
        </a>
        @endforeach
    </div>
</section>
@endif
@endif
@push('scripts')
<script>
(() => {
 const compareBase = "{{ route('store.compare') }}";
 const selected = new Set();
 const bar = document.getElementById('htCompareBar');
 const count = document.getElementById('htCompareCount');
 const link = document.getElementById('htCompareLink');
 const buttons = document.querySelectorAll('[data-compare-id]');
 function render() {
   buttons.forEach(button => button.classList.toggle('active', selected.has(button.dataset.compareId)));
   bar.hidden = selected.size === 0;
   count.textContent = selected.size + '/3 sản phẩm';
   link.href = compareBase + '?ids=' + [...selected].join(',');
 }
 buttons.forEach(button => button.addEventListener('click', () => {
   const id = button.dataset.compareId;
   if (selected.has(id)) { selected.delete(id); }
   else if (selected.size < 3) { selected.add(id); }
   render();
 }));
 document.getElementById('htCompareClear').addEventListener('click', () => { selected.clear(); render(); });
})();

// Countdown timer for Flash Sale
(function () {
    let secondsLeft = 8 * 3600 + 45 * 60 + 19;
    const hEl = document.getElementById('cdHours');
    const mEl = document.getElementById('cdMinutes');
    const sEl = document.getElementById('cdSeconds');
    if (!hEl || !mEl || !sEl) return;

    setInterval(function () {
        if (secondsLeft <= 0) secondsLeft = 24 * 3600;
        secondsLeft--;
        const h = Math.floor(secondsLeft / 3600);
        const m = Math.floor((secondsLeft % 3600) / 60);
        const s = secondsLeft % 60;
        hEl.textContent = String(h).padStart(2, '0');
        mEl.textContent = String(m).padStart(2, '0');
        sEl.textContent = String(s).padStart(2, '0');
    }, 1000);
})();
</script>

<style>
/* Flash Sale Section */
.ht-flash-sale-section { margin: 50px auto; }
.ht-flash-card {
    background: linear-gradient(135deg, #2b1821, #481e2e);
    border-radius: 28px;
    padding: 36px 40px;
    color: #fff;
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 36px;
    align-items: center;
    box-shadow: 0 20px 50px rgba(72, 30, 46, 0.35);
    border: 1px solid #832747;
}
@media (max-width: 768px) { .ht-flash-card { grid-template-columns: 1fr; padding: 26px 20px; } }
.flash-badge {
    background: linear-gradient(90deg, #f43f5e, #be123c);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 12px;
    letter-spacing: 1px;
    display: inline-block;
    margin-bottom: 12px;
}
.flash-left h2 {
    font: 600 clamp(24px, 4vw, 34px) Georgia, serif;
    color: #fff;
    margin: 0 0 8px;
}
.flash-left p { color: #fbcfe8; font-size: 14.5px; margin: 0 0 20px; }
.countdown-timer {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
}
.time-block {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 12px;
    padding: 10px 14px;
    text-align: center;
    min-width: 60px;
}
.time-num { font: 700 24px 'Courier New', monospace; color: #fef08a; display: block; line-height: 1; }
.time-lbl { font-size: 10px; color: #fbcfe8; text-transform: uppercase; letter-spacing: 1px; }
.time-colon { font-size: 24px; font-weight: 700; color: #fbcfe8; }
.flash-progress-wrap .progress-bar {
    height: 8px;
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 6px;
    max-width: 380px;
}
.flash-progress-wrap .fill {
    height: 100%;
    background: linear-gradient(90deg, #fbbf24, #f43f5e);
    border-radius: 10px;
}
.progress-text { font-size: 12px; color: #fed7aa; font-weight: 600; }
.flash-img-wrap {
    position: relative;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.02) 100%);
    border-radius: 20px;
    padding: 24px;
    text-align: center;
}
.flash-img-wrap img { max-height: 220px; object-fit: contain; }
.flash-discount-tag {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #e11d48;
    color: #fff;
    font-size: 13px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 8px;
}

/* Experience Banners */
.ht-experience-banners {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin: 40px auto;
}
@media (max-width: 720px) { .ht-experience-banners { grid-template-columns: 1fr; } }
.exp-banner-card {
    border-radius: 24px;
    padding: 32px 28px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
}
.exp-banner-card.quiz-card {
    background: linear-gradient(135deg, #fff0f5, #ffe4e6);
    border: 1px solid #fbcfe8;
}
.exp-banner-card.box-card {
    background: linear-gradient(135deg, #fdf4f7, #f3e8ee);
    border: 1px solid #f3d4e0;
}
.exp-badge {
    display: inline-block;
    background: #c2476a;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 10px;
    align-self: flex-start;
    margin-bottom: 12px;
}
.exp-banner-card h3 { font: 600 22px Georgia, serif; color: #2b1f26; margin: 0 0 8px; }
.exp-banner-card p { font-size: 14px; color: #6d5b64; line-height: 1.55; margin: 0 0 20px; }

/* ── LUXURY FRAGRANCE SHORTS SHOWCASE ── */
.ht-shorts-showcase {
    margin: 36px auto 44px;
    padding: 32px 30px 36px;
    background: linear-gradient(180deg, #fff7f9 0%, #ffffff 100%);
    border-radius: 28px;
    border: 1px solid #fce7f3;
    box-shadow: 0 10px 36px rgba(232, 114, 138, 0.08);
}
.ht-shorts-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}
.ht-shorts-title-wrap h2 {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e1b18;
    margin: 6px 0 4px;
}
.ht-shorts-title-wrap p {
    font-size: 13.5px;
    color: #64748b;
    margin: 0;
}
.ht-shorts-live-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #ffe4e6, #fce7f3);
    color: #db2777;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1px;
    padding: 4px 12px;
    border-radius: 20px;
    border: 1px solid rgba(219, 39, 119, 0.2);
}
.ht-shorts-live-pill .pulse-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #e11d48;
    box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.7);
    animation: livePulse 1.8s infinite;
}
@keyframes livePulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(225, 29, 72, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(225, 29, 72, 0); }
}
.ht-shorts-nav-hint .ht-hint-text {
    font-size: 13px;
    font-weight: 600;
    color: #9d174d;
    background: #ffffff;
    padding: 7px 16px;
    border-radius: 20px;
    border: 1px dashed #f472b6;
    display: inline-block;
}
.ht-shorts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}
.short-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 12px;
    border: 1px solid #fce7f3;
    box-shadow: 0 4px 18px rgba(0,0,0,0.03);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
    cursor: pointer;
}
.short-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 32px rgba(219, 39, 119, 0.12);
    border-color: #f472b6;
}
.short-thumb {
    position: relative;
    border-radius: 16px;
    overflow: hidden;
    height: 260px;
    background: #faf4f7;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.short-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .35s ease;
}
.short-card:hover .short-thumb img {
    transform: scale(1.06);
}
.short-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.45) 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: background 0.25s;
}
.short-card:hover .short-overlay {
    background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.6) 100%);
}
.short-overlay .play-btn {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e8728a, #db2777);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 4px 18px rgba(219, 39, 119, 0.5);
    transition: transform .2s ease;
}
.short-card:hover .play-btn {
    transform: scale(1.15);
}
.short-overlay .views {
    position: absolute;
    bottom: 10px;
    left: 10px;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(4px);
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
}
.short-overlay .duration-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(4px);
    color: #ffffff;
    font-size: 10.5px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 6px;
}
.short-info {
    padding: 2px 4px 6px;
}
.short-brand {
    display: block;
    font-size: 10.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #db2777;
    margin-bottom: 2px;
}
.short-info strong {
    font-size: 13.5px;
    color: #1e293b;
    line-height: 1.45;
    display: block;
    font-weight: 600;
}
.short-card:hover .short-info strong {
    color: #db2777;
}
@media (max-width: 640px) {
    .ht-shorts-showcase { padding: 22px 16px; border-radius: 20px; }
    .ht-shorts-title-wrap h2 { font-size: 1.45rem; }
    .ht-shorts-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .short-thumb { height: 200px; }
}
</style>
@endpush
@endsection

@push('scripts')
<script defer src="{{ asset('js/livestream-home.js') }}"></script>
@endpush
