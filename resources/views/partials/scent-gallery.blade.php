<section class="store-container ht-products-section scent-gallery" id="san-pham" aria-labelledby="scent-heading">
    <header class="scent-heading">
        <div>
            <span class="scent-eyebrow">SOOPI / LA GALERIE DES SENTEURS</span>
            @if($isFiltered)
                <h2 id="scent-heading">{{ $collectionTitle }}</h2>
                <p class="scent-result">@if(request()->filled('search'))Kết quả cho “{{ request('search') }}” · @endif{{ $perfumes->count() }} mùi hương phù hợp</p>
            @else
                <h2 id="scent-heading">Hương thơm.<br><em>Một dấu ấn riêng.</em></h2>
            @endif
        </div>
        <div class="scent-heading-note">@include('partials.icon', ['name' => 'flower', 'size' => 60])<p>Những mùi hương được chọn để trở thành một phần của bạn.</p><span>COLLECTION 01</span></div>
    </header>
    <div class="scent-toolbar">
        <nav class="scent-categories" aria-label="Chọn bộ sưu tập">
            @foreach(['' => 'Tất cả', 'nu' => 'Dành cho nàng', 'nam' => 'Dành cho chàng', 'unisex' => 'Unisex'] as $gender => $label)
                <a @class(['active' => request('gender', '') === $gender]) @if(request('gender', '') === $gender) aria-current="page" @endif href="{{ route('home', array_merge(request()->except('gender'), $gender ? ['gender' => $gender] : [])) }}#san-pham">{{ $label }}</a>
            @endforeach
        </nav>
        <details class="scent-sort">
            <summary>{{ ['sale' => 'Đang ưu đãi', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần'][request('sort')] ?? 'Mới nhất' }} <span aria-hidden="true">⌄</span></summary>
            <nav aria-label="Sắp xếp sản phẩm">
                @foreach(['' => 'Mới nhất', 'sale' => 'Đang ưu đãi', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần'] as $sort => $label)
                    <a href="{{ route('home', array_merge(request()->except('sort'), $sort ? ['sort' => $sort] : [])) }}#san-pham">{{ $label }}</a>
                @endforeach
            </nav>
        </details>
        <details class="scent-filters" @if(request()->anyFilled(['note', 'style', 'concentration', 'longevity', 'min_price', 'max_price'])) open @endif>
            <summary><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/><circle cx="8" cy="6" r="2" fill="currentColor"/><circle cx="16" cy="12" r="2" fill="currentColor"/><circle cx="10" cy="18" r="2" fill="currentColor"/></svg>Bộ lọc</summary>
            <form class="ht-filter-form" method="GET" action="{{ route('home') }}#san-pham">
                @foreach(['search', 'gender', 'category', 'sort'] as $filter)
                    @if(request()->filled($filter))<input type="hidden" name="{{ $filter }}" value="{{ request($filter) }}">@endif
                @endforeach
                <label>Hương nổi bật<input name="note" type="search" value="{{ request('note') }}" placeholder="Hoa hồng, vanilla, gỗ..."></label>
                <label>Phong cách<select name="style"><option value="">Tất cả</option>@foreach(['Dịu dàng', 'Lịch lãm', 'Thanh lịch'] as $style)<option value="{{ $style }}" @selected(request('style') === $style)>{{ $style }}</option>@endforeach</select></label>
                <label>Nồng độ<select name="concentration"><option value="">Tất cả</option>@foreach(['EDT', 'EDP', 'Parfum'] as $concentration)<option value="{{ $concentration }}" @selected(request('concentration') === $concentration)>{{ $concentration }}</option>@endforeach</select></label>
                <label>Lưu hương ước tính<select name="longevity"><option value="">Tất cả</option><option value="light" @selected(request('longevity') === 'light')>Nhẹ · khoảng 6–8 giờ</option><option value="medium" @selected(request('longevity') === 'medium')>Vừa · khoảng 8–10 giờ</option><option value="strong" @selected(request('longevity') === 'strong')>Đậm · từ 10 giờ</option></select></label>
                <label>Giá từ<input name="min_price" type="number" min="0" value="{{ request('min_price') }}" placeholder="0 ₫"></label>
                <label>Đến<input name="max_price" type="number" min="0" value="{{ request('max_price') }}" placeholder="Không giới hạn"></label>
                <button class="ht-button" type="submit">Lọc sản phẩm</button>
            </form>
        </details>
    </div>
    @if($isFiltered)<div class="scent-active-filters"><span>{{ $perfumes->count() }} kết quả</span><a href="{{ route('home') }}#san-pham">Xóa bộ lọc ↗</a></div>@endif
    @if($errors->any())<div class="public-flash alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if($galleryFeatured)
        <div @class(['scent-exhibition', 'scent-exhibition-solo' => $gallerySelection->isEmpty()])>
            @include('partials.scent-product', ['perfume' => $galleryFeatured, 'featured' => true])
            @if($gallerySelection->isNotEmpty())
                <div class="scent-selection">@foreach($gallerySelection as $perfume)@include('partials.scent-product', ['featured' => false])@endforeach</div>
            @endif
        </div>
        <aside class="scent-concierge">
            <img src="{{ asset('images/bloom/petal-rose.webp') }}" alt="" width="180" height="120" loading="lazy">
            <div><h3>Mùi hương nào kể câu chuyện của bạn?</h3><p>Khám phá lựa chọn dành riêng cho bạn.</p></div>
            <a href="{{ route('store.finder') }}">Tìm gu hương <span aria-hidden="true">↗</span></a>
        </aside>
        @if($galleryRemaining->isNotEmpty())
            <details class="scent-more" id="toan-bo-san-pham">
                <summary><span>SOOPI — THE ART OF SCENT</span><span class="scent-more-closed">Xem toàn bộ bộ sưu tập <span aria-hidden="true">↓</span></span><span class="scent-more-open">Thu gọn bộ sưu tập ↑</span></summary>
                <div class="scent-catalog">@foreach($galleryRemaining as $perfume)@include('partials.scent-product', ['featured' => false])@endforeach</div>
            </details>
        @endif
    @else
        <div class="scent-catalog">
            @forelse($perfumes as $perfume)
                @include('partials.scent-product', ['featured' => false])
            @empty
                <div class="scent-empty">@include('partials.icon', ['name' => 'search', 'size' => 42])<h3>Chưa tìm thấy mùi hương phù hợp</h3><p>Thử một tên nước hoa, thương hiệu hoặc danh mục khác nhé.</p><a href="{{ route('home') }}#san-pham">Khám phá tất cả nước hoa ↗</a></div>
            @endforelse
        </div>
    @endif
</section>
