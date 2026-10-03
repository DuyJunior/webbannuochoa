@php
    use Illuminate\Support\Str;
    $fragranceEditorial = \App\Services\FragranceEditorialService::forPerfume($perfume);
    $productStory = $fragranceEditorial['verified'] ? $fragranceEditorial['story'] : 'Khám phá '.$perfume->name.' của '.$perfume->brand.' cùng Soopi. Liên hệ để được tư vấn chi tiết về mùi hương.';
@endphp
@extends('layouts.store')

@section('title', $perfume->name.' · '.$perfume->brand.' · Soopi')
@section('meta_description', Str::limit($productStory, 155))
@section('meta_keywords', $perfume->name.', '.$perfume->brand.', nước hoa chính hãng, '.$perfume->concentration.', Soopi')
@if($perfume->image_src)
    @section('og_image', $perfume->image_src)
@endif

@push('styles')
@php
    $productSchema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $perfume->name,
        'description' => Str::limit($productStory, 300),
        'brand' => [
            '@type' => 'Brand',
            'name' => $perfume->brand,
        ],
        'image' => $perfume->image_src ? [$perfume->image_src] : null,
        'sku' => 'HATHU-' . $perfume->id,
        'offers' => [
            '@type' => 'Offer',
            'url' => route('perfumes.show', $perfume),
            'priceCurrency' => 'VND',
            'price' => (string) ($perfume->sale_price ?? $perfume->price),
            'availability' => $perfume->stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'seller' => [
                '@type' => 'Organization',
                'name' => 'Soopi',
            ],
        ],
        'aggregateRating' => ($averageRating > 0 && $reviews->total() > 0) ? [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $averageRating,
            'reviewCount' => (string) $reviews->total(),
        ] : null,
    ]);
@endphp
<script type="application/ld+json">
{!! json_encode($productSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
    @php
        $editorialImage = config('scent-gallery.artwork.'.$perfume->slug);
    @endphp
    <section class="store-container luxury-product-page">
        {{-- Breadcrumb --}}
        <nav class="luxury-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('home') }}#san-pham">{{ $perfume->category?->name ?? 'Nước hoa' }}</a>
            <span>/</span>
            <span class="current">{{ $perfume->name }}</span>
        </nav>

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger purchase-alert" role="alert" style="margin-bottom: 20px;">
                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
            </div>
        @endif

        <div class="luxury-product-grid">
            {{-- Visual Gallery --}}
            <div class="luxury-gallery-column {{ $perfume->shopPhotos->isNotEmpty() ? 'has-shop-photos' : '' }}">
                <div class="luxury-main-visual">
                    @if ($editorialImage || $perfume->image_src)
                        <img id="mainProductImage" src="{{ $editorialImage ? asset($editorialImage) : $perfume->image_src }}" alt="{{ $perfume->name }}" fetchpriority="high">
                    @else
                        <div class="luxury-bottle-placeholder">
                            <span>{{ mb_substr($perfume->brand, 0, 1) }}</span>
                            <small>{{ $perfume->brand }}</small>
                        </div>
                    @endif

                    @if ($perfume->price > 0 && $perfume->sale_price !== null && $perfume->sale_price < $perfume->price)
                        <span class="luxury-badge-sale">ƯU ĐÃI -{{ round((($perfume->price - $perfume->sale_price) / $perfume->price) * 100) }}%</span>
                    @endif

                    <div class="luxury-authenticity-tag">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                        Cam kết nước hoa chính hãng
                    </div>
                </div>

                @if($editorialImage && $perfume->image_src)
                <div class="interior-product-views" role="group" aria-label="Chọn hình sản phẩm">
                    <button type="button" data-product-image="{{ asset($editorialImage) }}" aria-pressed="true"><img src="{{ asset($editorialImage) }}" alt="" width="48" height="48">Góc nghệ thuật</button>
                    <button type="button" data-product-image="{{ $perfume->image_src }}" aria-pressed="false"><img src="{{ $perfume->image_src }}" alt="" width="48" height="48">Ảnh sản phẩm</button>
                </div>
                @endif
                <nav class="interior-product-chapters" aria-label="Khám phá sản phẩm"><a href="#scent-story-title">01 / Câu chuyện</a><a href="#danh-gia">02 / Cảm nhận</a><a href="{{ route('store.finder') }}">03 / Chọn hương ↗</a></nav>
                {{-- Live Viewers & Social Proof --}}
                <p class="ht-product-advice">Một mùi hương riêng, một dấu ấn khó quên.</p>

                @include('partials.shop-product-photos')

                {{-- Highlights Box --}}
                <div class="luxury-guarantees-grid">
                    <div class="guarantee-item">
                        <div class="guarantee-icon">@include('partials.icon', ['name' => 'truck', 'size' => '1em'])</div>
                        <div>
                            <strong>Giao hàng toàn quốc</strong>
                            <small>Đóng gói 3 lớp chống vỡ</small>
                        </div>
                    </div>
                    <div class="guarantee-item">
                        <div class="guarantee-icon">@include('partials.icon', ['name' => 'chat', 'size' => '1em'])</div>
                        <div>
                            <strong>Hỗ trợ sau mua</strong>
                            <small>Tư vấn khi cần hỗ trợ</small>
                        </div>
                    </div>
                    <div class="guarantee-item">
                        <div class="guarantee-icon">@include('partials.icon', ['name' => 'shield', 'size' => '1em'])</div>
                        <div>
                            <strong>Cam kết chính hãng</strong>
                            <small>Thông tin sản phẩm rõ ràng</small>
                        </div>
                    </div>
                    <div class="guarantee-item">
                        <div class="guarantee-icon">@include('partials.icon', ['name' => 'gift', 'size' => '1em'])</div>
                        <div>
                            <strong>Tư vấn chọn mùi</strong>
                            <small>Gợi ý theo sở thích của bạn</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Product Information & Purchase Form --}}
            <div class="luxury-info-column">
                <div class="luxury-brand-kicker">{{ $perfume->brand }}</div>
                <h1 class="luxury-product-title">{{ $perfume->name }}</h1>

                <div class="luxury-category-tags">
                    <span class="tag-pill">{{ $perfume->category?->name ?? 'Nước hoa' }}</span>
                    <span class="tag-pill">{{ ['nam' => 'Dành cho Nam', 'nu' => 'Dành cho Nữ', 'unisex' => 'Unisex - Mọi giới tính'][$perfume->gender] }}</span>
                    @if($perfume->concentration)<span class="tag-pill">{{ $perfume->concentration }}</span>@endif
                    @php
                        $directVid = $perfume->videos()->where('is_active', true)->whereIn('placement', ['product', 'all'])->orderBy('sort_order')->first();
                        $showVidUrl = $directVid ? $directVid->embed_url : $perfume->embed_video_url;
                    @endphp
                    @if($showVidUrl)
                    <button type="button" class="tag-pill tag-pill-video js-open-video"
                        data-title="{{ $directVid ? $directVid->title : 'Review & Cận Cảnh ' . $perfume->name }}"
                        data-embed="{{ $showVidUrl }}"
                        data-desc="{{ $directVid ? $directVid->description : 'Khám phá sản phẩm cùng Soopi.' }}"
                        data-views="{{ $directVid ? $directVid->formatted_views : '' }}"
                        data-perfume-name="{{ $perfume->name }}"
                        data-perfume-brand="{{ $perfume->brand }}"
                        data-perfume-price="{{ number_format($perfume->price) }}đ"
                        data-perfume-url="{{ route('perfumes.show', $perfume) }}"
                        data-perfume-img="{{ $perfume->image_src }}">
                        @include('partials.icon', ['name' => 'video', 'size' => '1em']) Xem video sản phẩm
                    </button>
                    @endif
                </div>

                {{-- Price Display --}}
                @php
                    $baseFullPrice = (float) ($perfume->sale_price ?? $perfume->price);
                    $originalFullPrice = (float) $perfume->price;
                    $volume100 = (int) ($perfume->volume_ml ?: 100);
                    
                    // Tính giá theo dung tích đẹp mắt
                    $price10ml = app(\App\Services\CartQuoteService::class)->unitPrice($perfume, 10);
                    $price50ml = app(\App\Services\CartQuoteService::class)->unitPrice($perfume, 50);
                    $priceFull = $baseFullPrice;

                    // Số lượng tồn kho theo từng dung tích riêng biệt
                    $stock10ml = $perfume->getStockForVolume(10);
                    $stock50ml = $perfume->getStockForVolume(50);
                    $stockFull = (int) $perfume->stock_100ml;
                @endphp

                <div class="luxury-price-box">
                    <div class="current-price" id="displayPrice">{{ number_format($priceFull, 0, ',', '.') }}₫</div>
                    @if ($perfume->sale_price !== null && $perfume->sale_price < $perfume->price)
                        <del class="old-price" id="displayOldPrice">{{ number_format($originalFullPrice, 0, ',', '.') }}₫</del>
                        <span class="save-tag" id="displaySaveTag">Tiết kiệm {{ number_format($originalFullPrice - $priceFull, 0, ',', '.') }}₫</span>
                    @endif
                </div>

                <div class="luxury-stock-status {{ $stockFull <= 5 && $stockFull > 0 ? 'low' : ($stockFull <= 0 ? 'out-of-stock' : '') }}" id="luxuryStockStatus">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                    <span id="stockStatusText">{{ $stockFull > 0 ? 'Còn hàng trong kho ('.$stockFull.' chai '.$volume100.'ml Fullbox sẵn sàng giao)' : 'Tạm thời hết hàng dung tích '.$volume100.'ml' }}</span>
                </div>

                <form class="luxury-purchase-form" method="POST" action="{{ route('cart.add', $perfume) }}" id="purchaseForm">
                    @csrf
                    
                    {{-- Dung tích (Volume Options) --}}
                    <div class="form-option-section">
                        <div class="option-header">
                            <label class="option-title">Chọn Dung Tích:</label>
                            <span class="option-sub" id="volumeSelectedLabel">{{ $volume100 }}ml (Fullbox Nguyên Seal) · Còn {{ $stockFull }} chai</span>
                        </div>
                        <div class="volume-options-grid">
                            @if($volume100 !== 10)
                            {{-- Option 10ml Chiết --}}
                            <label class="volume-card-option" data-volume="10" data-stock="{{ $stock10ml }}" data-desc="Chiết Travel Spray" data-price="{{ $price10ml }}" data-oldprice="{{ $price10ml }}">
                                <input type="radio" name="volume_ml" value="10">
                                <div class="volume-card-badge">Dùng thử</div>
                                <div class="volume-card-size">10ml</div>
                                <div class="volume-card-desc">Chiết Travel Spray</div>
                                <div class="volume-card-price">{{ number_format($price10ml, 0, ',', '.') }}₫</div>
                                <div class="volume-card-stock {{ $stock10ml <= 5 && $stock10ml > 0 ? 'stock-low' : ($stock10ml <= 0 ? 'stock-out' : '') }}">
                                    @if($stock10ml > 0)
                                        <span class="stock-dot"></span> Còn {{ $stock10ml }} chai
                                    @else
                                        <span class="stock-dot" style="background:#ef4444;"></span> Tạm hết
                                    @endif
                                </div>
                            </label>

                            @endif
                            @if($volume100 !== 50)
                            {{-- Option 50ml --}}
                            <label class="volume-card-option" data-volume="50" data-stock="{{ $stock50ml }}" data-desc="Chai Vừa Phải" data-price="{{ $price50ml }}" data-oldprice="{{ $price50ml }}">
                                <input type="radio" name="volume_ml" value="50">
                                <div class="volume-card-badge">Phổ biến</div>
                                <div class="volume-card-size">50ml</div>
                                <div class="volume-card-desc">Chai Vừa Phải</div>
                                <div class="volume-card-price">{{ number_format($price50ml, 0, ',', '.') }}₫</div>
                                <div class="volume-card-stock {{ $stock50ml <= 5 && $stock50ml > 0 ? 'stock-low' : ($stock50ml <= 0 ? 'stock-out' : '') }}">
                                    @if($stock50ml > 0)
                                        <span class="stock-dot"></span> Còn {{ $stock50ml }} chai
                                    @else
                                        <span class="stock-dot" style="background:#ef4444;"></span> Tạm hết
                                    @endif
                                </div>
                            </label>

                            {{-- Option Fullsize (Default) --}}
                            @endif
                            <label class="volume-card-option active" data-volume="{{ $volume100 }}" data-stock="{{ $stockFull }}" data-desc="Fullbox Nguyên Seal" data-price="{{ $priceFull }}" data-oldprice="{{ $originalFullPrice }}">
                                <input type="radio" name="volume_ml" value="{{ $volume100 }}" checked>
                                <div class="volume-card-badge best-seller">Nguyên hộp</div>
                                <div class="volume-card-size">{{ $volume100 }}ml</div>
                                <div class="volume-card-desc">Fullbox Nguyên Seal</div>
                                <div class="volume-card-price">{{ number_format($priceFull, 0, ',', '.') }}₫</div>
                                <div class="volume-card-stock {{ $stockFull <= 5 && $stockFull > 0 ? 'stock-low' : ($stockFull <= 0 ? 'stock-out' : '') }}">
                                    @if($stockFull > 0)
                                        <span class="stock-dot"></span> Còn {{ $stockFull }} chai
                                    @else
                                        <span class="stock-dot" style="background:#ef4444;"></span> Tạm hết
                                    @endif
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Dịch Vụ Đi Kèm & Quà Tặng (Add-ons / Options) --}}
                    <div class="form-option-section">
                        <label class="option-title">Dịch Vụ & Tuỳ Chọn Cao Cấp:</label>
                        <div class="addon-options-list">
                            <label class="addon-card-option">
                                <input type="checkbox" name="addon_gift" id="addonGift" value="1" onchange="calculateTotalPrice()">
                                <div class="addon-icon">@include('partials.icon', ['name' => 'gift', 'size' => '1em'])</div>
                                <div class="addon-text">
                                    <strong>Gói quà Luxury & Thiệp chúc mừng</strong>
                                    <small>Hộp quà cao cấp thắt nơ lụa thủ công + thiệp viết tay theo yêu cầu (+50.000₫)</small>
                                </div>
                            </label>

                            <label class="addon-card-option">
                                <input type="checkbox" name="addon_engrave" id="addonEngrave" value="1" onchange="toggleEngraveField()">
                                <div class="addon-icon">@include('partials.icon', ['name' => 'pen', 'size' => '1em'])</div>
                                <div class="addon-text">
                                    <strong>Khắc tên / Lời chúc Laser lên thân chai</strong>
                                    <small>Cá nhân hóa dấu ấn riêng (Miễn phí quà tặng)</small>
                                </div>
                            </label>
                            
                            <div class="engrave-input-box" id="engraveInputBox" style="display: none;">
                                <input type="text" name="engrave_text" maxlength="30" placeholder="Nhập tên hoặc lời chúc muốn khắc (tối đa 30 ký tự)...">
                            </div>
                        </div>
                    </div>

                    {{-- Số lượng & Action Buttons --}}
                    <div class="form-option-section">
                        <label class="option-title">Số Lượng:</label>
                        <div class="quantity-and-action-row">
                            <div class="luxury-quantity-picker" data-quantity-picker>
                                <button type="button" data-quantity-minus aria-label="Giảm số lượng">−</button>
                                <input type="number" name="quantity" id="productQuantity" value="1" min="1" max="{{ max(1, $perfume->stock) }}" aria-label="Số lượng sản phẩm">
                                <button type="button" data-quantity-plus aria-label="Tăng số lượng">+</button>
                            </div>

                            <div class="subtotal-indicator">
                                <span>Tạm tính: </span>
                                <strong id="dynamicSubtotal">{{ number_format($priceFull, 0, ',', '.') }}₫</strong>
                            </div>
                        </div>
                    </div>

                    <div class="luxury-purchase-actions">
                        <button class="luxury-add-cart-btn" type="submit" @disabled($perfume->stock < 1)>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                            Thêm vào giỏ hàng
                        </button>
                        <button class="luxury-buy-now-btn" type="submit" name="buy_now" value="1" @disabled($perfume->stock < 1)>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                            Mua ngay
                        </button>
                    </div>
                </form>

                <div class="purchase-assurance" aria-label="Giao hàng và hỗ trợ mua hàng">
                    <div>@include('partials.icon', ['name'=>'truck','size'=>19])<span><strong>Giao hàng toàn quốc</strong><small>Phí vận chuyển được tính khi thanh toán.</small></span></div>
                    <a href="{{ route('store.faq') }}#doi-tra">@include('partials.icon', ['name'=>'shield','size'=>19])<span><strong>Đổi trả {{ config('storefront.return_days') }} ngày</strong><small>Theo điều kiện đổi trả · Xem chính sách ↗</small></span></a>
                    <a href="{{ config('storefront.zalo_url') }}" target="_blank" rel="noopener">@include('partials.icon', ['name'=>'chat','size'=>19])<span><strong>Hỏi Soopi trước khi chọn</strong><small>Zalo {{ config('storefront.zalo_phone') }} ↗</small></span></a>
                </div>
                <div class="ht-product-utilities">
                    @auth
                    <form method="POST" action="{{ route('store.wishlist.toggle', $perfume) }}" data-wishlist="{{ $perfume->id }}" data-wishlist-saved="{{ $isFavorite ? 'true' : 'false' }}" data-wishlist-name="{{ $perfume->name }}" data-wishlist-login="{{ route('login') }}">@csrf<button type="submit" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}" aria-label="{{ $isFavorite ? 'Bỏ yêu thích' : 'Yêu thích' }} {{ $perfume->name }}">@include('partials.icon', ['name' => 'heart', 'size' => '1em']) <span data-wishlist-label>{{ $isFavorite ? 'Đã yêu thích' : 'Lưu yêu thích' }}</span></button></form>
                    <button type="button" id="openWardrobeModalBtn" class="ht-utility-btn">@include('partials.icon', ['name' => 'gem', 'size' => '1em']) {{ $inWardrobe ? ' Đã trong Tủ hương' : '+ Tủ nước hoa' }}</button>
                    @if($perfume->stock <= 0)
                    <form method="POST" action="{{ route('store.stock-alert', $perfume) }}">@csrf<button type="submit">Báo khi có hàng</button></form>
                    @endif
                    @endauth
                    @guest
                    <a href="{{ route('login') }}">@include('partials.icon', ['name' => 'heart', 'size' => '1em']) Đăng nhập lưu yêu thích</a>
                    <a href="{{ route('login') }}">@include('partials.icon', ['name' => 'gem', 'size' => '1em']) Thêm vào Tủ hương</a>
                    @endguest
                    <button type="button" id="openGiftModalBtn" class="ht-utility-btn">@include('partials.icon', ['name' => 'gift', 'size' => '1em']) Gửi tặng bạn bè</button>
                    <a href="{{ route('store.compare', ['ids' => $perfume->id]) }}">@include('partials.icon', ['name' => 'scale', 'size' => '1em']) So sánh sản phẩm</a>
                </div>
                {{-- Thông số kỹ thuật nhanh --}}
                <div class="luxury-specs-card">
                    <h3>Thông Tin Chi Tiết</h3>
                    <div class="specs-grid">
                        <div class="spec-row">
                            <span class="spec-label">Thương hiệu</span>
                            <span class="spec-value">{{ $perfume->brand }}</span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">Nồng độ</span>
                            <span class="spec-value">{{ $perfume->concentration ?: 'Đang cập nhật' }}</span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">Dung tích chọn</span>
                            <span class="spec-value" id="specVolumeValue">{{ $volume100 }}ml</span>
                        </div>
                        <div class="spec-row">
                            <span class="spec-label">Giới tính</span>
                            <span class="spec-value">{{ ['nam' => 'Nam giới', 'nu' => 'Nữ giới', 'unisex' => 'Unisex'][$perfume->gender] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('partials.fragrance-notes')

        {{-- ── NỔI BẬT: SHORTS & VIDEO REVIEW CẬN CẢNH MÙI HƯƠNG ── --}}
        @php
            $perfumeVideos = \App\Models\Video::where('is_active', true)
                ->where(function($q) use ($perfume) {
                    $q->where('perfume_id', $perfume->id)
                      ->orWhere('placement', 'product')
                      ->orWhere('placement', 'all');
                })
                ->orderByRaw('CASE WHEN perfume_id = ? THEN 0 ELSE 1 END', [$perfume->id])
                ->orderBy('sort_order')
                ->take(4)
                ->get();
        @endphp
        @if($perfumeVideos->isNotEmpty())
        <section class="ht-video-review-section" id="video-review">
            <div class="ht-section-heading">
                <div>
                    <span class="ht-eyebrow">REVIEW TRỰC DIỆN</span>
                    <h2>Video Trải Nghiệm & Cận Cảnh Mùi Hương</h2>
                    <p>Chiêm ngưỡng thiết kế chai thực tế, kiểm tra độ tỏa hương và vòi xịt phun sương.</p>
                </div>
            </div>
            <div class="ht-video-grid">
                @foreach($perfumeVideos as $pvid)
                <div class="ht-video-card js-open-video"
                     data-title="{{ $pvid->title }}"
                     data-embed="{{ $pvid->embed_url }}"
                     data-desc="{{ $pvid->description }}"
                     data-views="{{ $pvid->formatted_views }}"
                     data-perfume-name="{{ $pvid->perfume ? $pvid->perfume->name : $perfume->name }}"
                     data-perfume-brand="{{ $pvid->perfume ? $pvid->perfume->brand : $perfume->brand }}"
                     data-perfume-price="{{ number_format(($pvid->perfume ?: $perfume)->price) . 'đ' }}"
                     data-perfume-url="{{ route('perfumes.show', $pvid->perfume ?: $perfume) }}"
                     data-perfume-img="{{ ($pvid->perfume ?: $perfume)->image_src }}"
                     style="cursor: pointer;"
                     role="button"
                     tabindex="0">
                    <div class="video-preview-wrap">
                        <img src="{{ $pvid->thumbnail_src ?: $perfume->image_src }}" alt="{{ $pvid->title }}">
                        <div class="video-play-overlay">
                            <span class="play-icon">@include('partials.icon', ['name' => 'play', 'size' => '1em'])</span>
                            <span class="video-duration">{{ $pvid->duration ?: '0:45' }}</span>
                        </div>
                    </div>
                    <h4>{{ $pvid->title }}</h4>
                    <p>{{ $pvid->description ?: 'Chuyên gia mùi hương của Soopi đánh giá chi tiết độ lưu hương thực tế trên da.' }}</p>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        <section class="ht-reviews" id="danh-gia">
            <div class="ht-section-heading"><div><span class="ht-eyebrow">CẢM NHẬN THỰC TẾ</span><h2>Đánh giá từ khách hàng</h2><p>{{ $reviews->total() }} đánh giá · {{ $averageRating ?: 'Chưa có điểm' }}{{ $averageRating ? '/5 sao' : '' }}</p></div></div>
            <div class="ht-review-grid">
                <div>
                    @forelse($reviews as $review)
                    <article class="ht-review-card"><div><strong>{{ $review->user?->name ?? 'Khách hàng' }}</strong><span role="img" aria-label="{{ $review->rating }} trên 5 sao">@for($star = 1; $star <= 5; $star++)@include('partials.icon', ['name' => $star <= $review->rating ? 'star-filled' : 'star', 'size' => '1em'])@endfor</span></div><p>{{ $review->body }}</p>@if($review->image_path)<img src="{{ asset($review->image_path) }}" alt="Ảnh do khách hàng chia sẻ" loading="lazy">@endif<small>{{ $review->created_at->format('d/m/Y') }}</small></article>
                    @empty<p>Chưa có đánh giá nào. Hãy là người đầu tiên chia sẻ cảm nhận.</p>@endforelse
                    {{ $reviews->links() }}
                </div>
                <div class="ht-feature-panel">
                    <h3>Chia sẻ cảm nhận của bạn</h3>
                    @auth
                    <form method="POST" action="{{ route('store.review', $perfume) }}" enctype="multipart/form-data" class="ht-review-form">@csrf
                        <label>Đánh giá<select name="rating" required><option value="5">5 sao · Rất thích</option><option value="4">4 sao · Hài lòng</option><option value="3">3 sao · Khá</option><option value="2">2 sao · Chưa hợp</option><option value="1">1 sao · Không hợp</option></select></label>
                        <label>Cảm nhận<textarea name="body" minlength="10" maxlength="2000" rows="5" required placeholder="Bạn cảm nhận mùi hương như thế nào?">{{ old('body') }}</textarea></label>
                        <label>Ảnh trải nghiệm (không bắt buộc)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label>
                        <button class="ht-button" type="submit">Gửi đánh giá</button>
                    </form>
                    @else<a class="ht-button" href="{{ route('login') }}">Đăng nhập để đánh giá</a>@endauth
                </div>
            </div>
        </section>
    </section>

    @include('partials.gift-bundle')

    {{-- ── RELATED PRODUCTS ── --}}
    @if($related->isNotEmpty())
    <section class="store-container ht-related-section" aria-labelledby="related-title">
        <div class="ht-section-heading">
            <div>
                <span class="ht-eyebrow">HƯƠNG THƠM TƯƠNG TỰ</span>
                <h2 id="related-title">Có thể bạn cũng thích</h2>
            </div>
        </div>
        <div class="ht-related-grid">
            @foreach($related as $rel)
            <a href="{{ route('perfumes.show', $rel) }}" class="ht-related-card">
                <div class="ht-related-img">
                    @if($rel->image_src)
                        <img src="{{ $rel->image_src }}" alt="{{ $rel->name }}" loading="lazy">
                    @else
                        <span class="ht-related-placeholder">{{ mb_substr($rel->brand, 0, 1) }}</span>
                    @endif
                    @if($rel->sale_price !== null)
                        <span class="ht-related-badge">-{{ round((($rel->price - $rel->sale_price) / $rel->price) * 100) }}%</span>
                    @endif
                </div>
                <div class="ht-related-info">
                    <span class="ht-related-brand">{{ $rel->brand }}</span>
                    <strong class="ht-related-name">{{ $rel->name }}</strong>
                    <div class="ht-related-price">
                        <span class="ht-related-current">{{ number_format((float)($rel->sale_price ?? $rel->price), 0, ',', '.') }}₫</span>
                        @if($rel->sale_price !== null)
                            <del class="ht-related-old">{{ number_format((float)$rel->price, 0, ',', '.') }}₫</del>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ── RECENTLY VIEWED PRODUCTS ── --}}
    @if(isset($recentlyViewed) && $recentlyViewed->isNotEmpty())
    <section class="store-container ht-related-section ht-recent-section" aria-labelledby="recent-title">
        <div class="ht-section-heading">
            <div>
                <span class="ht-eyebrow">LỊCH SỬ DUYỆT CỦA BẠN</span>
                <h2 id="recent-title">Sản phẩm bạn vừa xem gần đây</h2>
            </div>
        </div>
        <div class="ht-related-grid">
            @foreach($recentlyViewed as $recent)
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

    {{-- MODAL 1: THÊM VÀO TỦ NƯỚC HOA CÁ NHÂN --}}
    <div id="htWardrobeModal" class="ht-popup-backdrop" hidden>
        <div class="ht-popup-modal">
            <button type="button" class="ht-popup-close-x" aria-label="Đóng tủ hương" onclick="document.getElementById('htWardrobeModal').hidden=true">@include('partials.icon', ['name' => 'close', 'size' => '1em'])</button>
            <div class="modal-icon">@include('partials.icon', ['name' => 'gem', 'size' => '1em'])</div>
            <h3>Lưu Vào Tủ Nước Hoa Cá Nhân</h3>
            <p>Chọn dịp bạn cảm thấy phù hợp nhất để dùng mùi hương <strong>{{ $perfume->name }}</strong>:</p>
            @auth
            <form action="{{ route('store.wardrobe.add') }}" method="POST">
                @csrf
                <input type="hidden" name="perfume_id" value="{{ $perfume->id }}">
                <div class="occasion-select-grid">
                    <label class="occ-radio"><input type="radio" name="occasion" value="work" checked> <span>@include('partials.icon', ['name' => 'briefcase', 'size' => '1em']) Đi làm & Công sở</span></label>
                    <label class="occ-radio"><input type="radio" name="occasion" value="date"> <span>@include('partials.icon', ['name' => 'glass', 'size' => '1em']) Hẹn hò & Lãng mạn</span></label>
                    <label class="occ-radio"><input type="radio" name="occasion" value="party"> <span>@include('partials.icon', ['name' => 'crown', 'size' => '1em']) Đi tiệc & Dạ hội</span></label>
                    <label class="occ-radio"><input type="radio" name="occasion" value="casual"> <span>@include('partials.icon', ['name' => 'leaf', 'size' => '1em']) Thường ngày & Dạo phố</span></label>
                </div>
                <div style="margin-top: 14px;">
                    <label style="font-size: 13px; color: #55444e; display: block; margin-bottom: 6px;">Ghi chú cảm xúc của bạn (không bắt buộc):</label>
                    <input type="text" name="notes" class="form-control" placeholder="VD: Mùi này tuyệt nhất vào ngày mưa hoặc trời lạnh..." style="width: 100%; border: 1px solid #f3d4e0; border-radius: 8px; padding: 10px;">
                </div>
                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="submit" class="ht-button ht-button-primary" style="flex: 1;">Lưu Vào Tủ Hương</button>
                    <a href="{{ route('store.wardrobe') }}" class="ht-button ht-button-outline">Xem Tủ Hương</a>
                </div>
            </form>
            @else
            <p><a href="{{ route('login') }}" class="ht-button ht-button-primary">Đăng Nhập Để Lưu Tủ Nước Hoa</a></p>
            @endauth
        </div>
    </div>

    {{-- MODAL 2: GỬI TẶNG BẠN BÈ --}}
    <div id="htGiftModal" class="ht-popup-backdrop" hidden>
        <div class="ht-popup-modal">
            <button type="button" class="ht-popup-close-x" aria-label="Đóng quà tặng" onclick="document.getElementById('htGiftModal').hidden=true">@include('partials.icon', ['name' => 'close', 'size' => '1em'])</button>
            <div class="modal-icon">@include('partials.icon', ['name' => 'gift', 'size' => '1em'])</div>
            <h3>Gửi Tặng Món Quà Này Cho Bạn Bè</h3>
            <p>Tạo link thiệp điện tử kèm lời nhắn gửi trao để gửi qua Zalo, Messenger hoặc SMS:</p>
            <div class="gift-form-fields">
                <label>Tên của bạn (Người gửi):</label>
                <input type="text" id="giftFromInput" value="{{ Auth::user()->name ?? 'Người bạn thân' }}" class="form-input">
                <label>Tên người nhận:</label>
                <input type="text" id="giftToInput" placeholder="VD: Mai Lan" class="form-input">
                <label>Lời chúc / Nhắn nhủ:</label>
                <textarea id="giftMsgInput" rows="3" class="form-input">Chúc bạn luôn ngát hương thơm và rạng rỡ mỗi ngày nhé!</textarea>
                <div style="margin-top: 14px;">
                    <button type="button" id="generateGiftLinkBtn" class="ht-button ht-button-primary" style="width: 100%;"><span data-gift-link-label>Tạo & Sao Chép Link Tặng Quà</span> @include('partials.icon', ['name' => 'link', 'size' => '1em'])</button>
                </div>
                <div id="giftLinkOutputWrap" style="display: none; margin-top: 12px; background: #fff0f5; padding: 10px; border-radius: 8px; font-size: 12px;">
                    <strong style="color: #c2476a;">@include('partials.icon', ['name' => 'check', 'size' => '1em']) Đã sao chép link!</strong>
                    <p style="word-break: break-all; margin: 4px 0 0;" id="giftLinkOutputText"></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let currentBasePrice = {{ $priceFull }};
            let currentOldPrice = {{ $originalFullPrice }};
            let currentVolume = {{ $volume100 }};

            const volumeCards = document.querySelectorAll('.volume-card-option');
            const displayPrice = document.getElementById('displayPrice');
            const displayOldPrice = document.getElementById('displayOldPrice');
            const displaySaveTag = document.getElementById('displaySaveTag');
            const volumeSelectedLabel = document.getElementById('volumeSelectedLabel');
            const specVolumeValue = document.getElementById('specVolumeValue');
            const quantityInput = document.getElementById('productQuantity');
            const dynamicSubtotal = document.getElementById('dynamicSubtotal');
            const addonGift = document.getElementById('addonGift');

            function formatCurrency(amount) {
                return new Intl.NumberFormat('vi-VN').format(amount) + '₫';
            }

            window.calculateTotalPrice = function() {
                const qty = parseInt(quantityInput.value) || 1;
                const giftPrice = addonGift && addonGift.checked ? 50000 : 0;
                
                const unitPriceWithAddon = currentBasePrice + giftPrice;
                const totalPrice = unitPriceWithAddon * qty;

                displayPrice.textContent = formatCurrency(unitPriceWithAddon);
                if (displayOldPrice) {
                    displayOldPrice.textContent = formatCurrency(currentOldPrice + giftPrice);
                    displayOldPrice.style.display = currentOldPrice > currentBasePrice ? '' : 'none';
                }
                if (displaySaveTag) {
                    displaySaveTag.style.display = currentOldPrice > currentBasePrice ? '' : 'none';
                    if (currentOldPrice > currentBasePrice) displaySaveTag.textContent = 'Tiết kiệm ' + formatCurrency(currentOldPrice - currentBasePrice);
                }

                if (dynamicSubtotal) {
                    dynamicSubtotal.textContent = formatCurrency(totalPrice);
                }
            };

            window.toggleEngraveField = function() {
                const engraveBox = document.getElementById('engraveInputBox');
                const isChecked = document.getElementById('addonEngrave').checked;
                if (engraveBox) {
                    engraveBox.style.display = isChecked ? 'block' : 'none';
                    if (isChecked) {
                        engraveBox.querySelector('input')?.focus();
                    }
                }
            };

            const stockStatusContainer = document.getElementById('luxuryStockStatus');
            const stockStatusText = document.getElementById('stockStatusText');
            const btnAddCart = document.querySelector('.luxury-add-cart-btn');
            const btnBuyNow = document.querySelector('.luxury-buy-now-btn');

            volumeCards.forEach(card => {
                const updateVolume = function () {
                    volumeCards.forEach(c => c.classList.remove('active'));
                    this.classList.add('active');

                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;

                    currentVolume = this.getAttribute('data-volume');
                    currentBasePrice = parseFloat(this.getAttribute('data-price')) || {{ $priceFull }};
                    currentOldPrice = parseFloat(this.getAttribute('data-oldprice')) || currentBasePrice;
                    const stockNum = parseInt(this.getAttribute('data-stock')) || 0;
                    const desc = this.getAttribute('data-desc') || this.querySelector('.volume-card-desc')?.textContent || '';

                    if (volumeSelectedLabel) {
                        volumeSelectedLabel.textContent = `${currentVolume}ml (${desc}) · Còn ${stockNum} chai`;
                    }
                    if (specVolumeValue) {
                        specVolumeValue.textContent = `${currentVolume}ml`;
                    }

                    // Cập nhật trạng thái và số lượng còn lại cho dung tích đang bấm chọn
                    if (stockStatusText) {
                        if (stockNum > 0) {
                            stockStatusText.textContent = `Còn hàng trong kho (${stockNum} chai ${currentVolume}ml ${desc} sẵn sàng giao)`;
                        } else {
                            stockStatusText.textContent = `Tạm thời hết hàng dung tích ${currentVolume}ml`;
                        }
                    }

                    if (stockStatusContainer) {
                        if (stockNum <= 0) {
                            stockStatusContainer.className = 'luxury-stock-status out-of-stock';
                        } else if (stockNum <= 5) {
                            stockStatusContainer.className = 'luxury-stock-status low';
                        } else {
                            stockStatusContainer.className = 'luxury-stock-status';
                        }
                    }

                    // Giới hạn số lượng mua theo tồn kho của dung tích đó
                    if (quantityInput) {
                        quantityInput.max = Math.max(1, stockNum);
                        if (parseInt(quantityInput.value) > stockNum && stockNum > 0) {
                            quantityInput.value = stockNum;
                        }
                    }

                    if (btnAddCart) {
                        btnAddCart.disabled = stockNum < 1;
                    }
                    if (btnBuyNow) {
                        btnBuyNow.disabled = stockNum < 1;
                    }

                    calculateTotalPrice();
                };
                card.addEventListener('click', updateVolume);
                card.querySelector('input[type="radio"]')?.addEventListener('change', () => updateVolume.call(card));
            });

            // Lắng nghe khi bấm tăng giảm số lượng
            document.addEventListener('click', function(e) {
                if (e.target.closest('[data-quantity-minus], [data-quantity-plus]')) {
                    setTimeout(calculateTotalPrice, 50);
                }
            });

            if (quantityInput) {
                quantityInput.addEventListener('input', calculateTotalPrice);
            }


            calculateTotalPrice();

            // Modal Tủ Nước Hoa
            const wBtn = document.getElementById('openWardrobeModalBtn');
            const wModal = document.getElementById('htWardrobeModal');
            if (wBtn && wModal) {
                wBtn.addEventListener('click', () => { wModal.hidden = false; });
                wModal.addEventListener('click', (e) => { if (e.target === wModal) wModal.hidden = true; });
            }

            // Modal Gửi Tặng Bạn Bè
            const gBtn = document.getElementById('openGiftModalBtn');
            const gModal = document.getElementById('htGiftModal');
            const genGiftBtn = document.getElementById('generateGiftLinkBtn');
            const giftOutWrap = document.getElementById('giftLinkOutputWrap');
            const giftOutText = document.getElementById('giftLinkOutputText');

            if (gBtn && gModal) {
                gBtn.addEventListener('click', () => { gModal.hidden = false; });
                gModal.addEventListener('click', (e) => { if (e.target === gModal) gModal.hidden = true; });
            }

            if (genGiftBtn) {
                genGiftBtn.addEventListener('click', function () {
                    const from = encodeURIComponent(document.getElementById('giftFromInput').value || 'Bạn thân');
                    const to = encodeURIComponent(document.getElementById('giftToInput').value || 'Người nhận');
                    const msg = encodeURIComponent(document.getElementById('giftMsgInput').value || '');
                    const baseUrl = "{{ route('store.gift-share') }}";
                    const fullUrl = `${baseUrl}?id={{ $perfume->id }}&from=${from}&to=${to}&msg=${msg}`;

                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(fullUrl);
                    }
                    giftOutText.textContent = fullUrl;
                    giftOutWrap.style.display = 'block';
                    genGiftBtn.querySelector('[data-gift-link-label]').textContent = 'Đã Sao Chép Link Tặng Quà!';
                    setTimeout(() => { genGiftBtn.querySelector('[data-gift-link-label]').textContent = 'Tạo & Sao Chép Link Tặng Quà'; }, 3000);
                });
            }
        });
    </script>

    <style>
        .ht-utility-btn {
            background: none;
            border: 1px solid #fce7f3;
            color: #be185d;
            border-radius: 20px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ht-utility-btn:hover { background: #fdf2f8; border-color: #f472b6; }

        /* Bundle Section */
        .ht-bundle-section { margin: 40px auto; }
        .ht-bundle-card {
            background: linear-gradient(135deg, #fff5f8, #fdf2f8);
            border: 2px solid #fbcfe8;
            border-radius: 24px;
            padding: 30px;
            position: relative;
            box-shadow: 0 10px 30px rgba(194, 71, 106, 0.08);
        }
        .bundle-badge {
            position: absolute;
            top: -12px;
            left: 30px;
            background: #be185d;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 12px;
            letter-spacing: 1px;
        }
        .bundle-content {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 30px;
            align-items: center;
        }
        @media (max-width: 768px) { .bundle-content { grid-template-columns: 1fr; } }
        .bundle-items-visual {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: #fff;
            border-radius: 18px;
            padding: 20px 12px;
            border: 1px dashed #f472b6;
        }
        .bundle-item { text-align: center; }
        .bundle-item img { height: 90px; object-fit: contain; }
        .bundle-item span { display: block; font-size: 11px; color: #715865; margin-top: 4px; }
        .bundle-plus { font-size: 20px; font-weight: 700; color: #be185d; }
        .vial-thumb, .box-thumb { font-size: 36px; height: 90px; display: flex; align-items: center; justify-content: center; }
        .bundle-info h3 { font: 600 20px var(--ht-serif); color: #2b1f26; margin: 0 0 6px; }
        .bundle-info p { font-size: 13.5px; color: #664d5a; line-height: 1.5; margin: 0 0 16px; }
        .bundle-pricing { display: flex; align-items: baseline; gap: 10px; margin-bottom: 16px; }
        .bundle-price { font-size: 24px; font-weight: 700; color: #be185d; }
        .bundle-old { font-size: 14px; color: #a8949f; }
        .bundle-save { background: #fee2e2; color: #991b1b; font-size: 11.5px; font-weight: 700; padding: 2px 8px; border-radius: 6px; }

        /* Tag Pill Video */
        .tag-pill-video {
            background: linear-gradient(135deg, #ffe4e6, #fce7f3) !important;
            color: #db2777 !important;
            font-weight: 800 !important;
            border: 1px solid rgba(219, 39, 119, 0.35) !important;
            box-shadow: 0 3px 12px rgba(219, 39, 119, 0.18) !important;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px !important;
            border-radius: 20px;
            transition: all .2s ease;
        }
        .tag-pill-video:hover {
            background: linear-gradient(135deg, #f472b6, #db2777) !important;
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(219, 39, 119, 0.35) !important;
        }

        /* Video Review Shorts */
        .ht-video-review-section {
            margin: 44px auto;
            padding: 30px 26px 34px;
            background: linear-gradient(180deg, #fff7f9 0%, #ffffff 100%);
            border-radius: 24px;
            border: 1px solid #fce7f3;
            box-shadow: 0 8px 30px rgba(232, 114, 138, 0.08);
        }
        .ht-video-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-top: 22px; }
        .ht-video-card {
            background: #ffffff;
            border: 1px solid #fce7f3;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(0,0,0,0.03);
            padding: 14px;
            transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
            cursor: pointer;
        }
        .ht-video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 32px rgba(219, 39, 119, 0.12);
            border-color: #f472b6;
        }
        .video-preview-wrap {
            position: relative;
            background: #faf4f7;
            border-radius: 14px;
            overflow: hidden;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .video-preview-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
        .ht-video-card:hover .video-preview-wrap img { transform: scale(1.05); }
        .video-play-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0.1) 0%, rgba(0,0,0,0.5) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.25s;
        }
        .ht-video-card:hover .video-play-overlay {
            background: linear-gradient(180deg, rgba(0,0,0,0.15) 0%, rgba(0,0,0,0.65) 100%);
        }
        .play-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #e8728a, #db2777);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 4px 18px rgba(219, 39, 119, 0.5);
            transition: transform .2s ease;
        }
        .ht-video-card:hover .play-icon {
            transform: scale(1.15);
        }
        .video-duration {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(4px);
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
        }
        .ht-video-card h4 { font: 700 15px var(--ht-serif); color: #1e293b; margin: 0 0 6px; line-height: 1.4; }
        .ht-video-card:hover h4 { color: #db2777; }
        .ht-video-card p { font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.5; }

        /* Modals */
        .ht-popup-backdrop {
            position: fixed;
            inset: 0;
            z-index: 999999;
            background: rgba(20, 10, 15, 0.65);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .ht-popup-backdrop[hidden] { display: none !important; }
        .ht-popup-modal {
            background: #ffffff;
            border-radius: 24px;
            max-width: 460px;
            width: 100%;
            padding: 30px 24px;
            position: relative;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
            border: 2px solid #fbcfe8;
            text-align: center;
        }
        .ht-popup-close-x {
            position: absolute;
            top: 14px;
            right: 16px;
            background: #fdf2f8;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            font-size: 14px;
            cursor: pointer;
            color: #be185d;
        }
        .modal-icon { font-size: 36px; margin-bottom: 8px; }
        .ht-popup-modal h3 { font: 600 20px var(--ht-serif); color: #2b1f26; margin: 0 0 6px; }
        .ht-popup-modal p { font-size: 13.5px; color: #6d5b64; margin: 0 0 18px; line-height: 1.5; }
        .occasion-select-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; text-align: left; }
        .occ-radio {
            border: 1px solid #f3d4e0;
            border-radius: 10px;
            padding: 10px;
            cursor: pointer;
            font-size: 12.5px;
            color: #4a3540;
            display: flex;
            align-items: center;
            gap: 6px;
            background: #fffafa;
        }
        .occ-radio input:checked + span { font-weight: 700; color: #be185d; }
        .gift-form-fields { text-align: left; display: flex; flex-direction: column; gap: 8px; font-size: 13px; color: #4a3540; }
        .form-input { width: 100%; border: 1px solid #f3d4e0; border-radius: 8px; padding: 9px 12px; font-size: 13.5px; }

        .volume-card-stock {
            font-size: 11.5px;
            font-weight: 600;
            color: #059669;
            margin-top: 6px;
            padding-top: 5px;
            border-top: 1px dashed #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.2s ease;
        }
        .volume-card-option.active .volume-card-stock {
            border-top-color: #cbd5e1;
            font-weight: 700;
        }
        .volume-card-stock.stock-low { color: #d97706; }
        .volume-card-stock.stock-out { color: #dc2626; }
        .stock-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #10b981;
            display: inline-block;
            flex-shrink: 0;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
        }
        .volume-card-stock.stock-low .stock-dot {
            background-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
        }
        .luxury-stock-status.out-of-stock { color: #dc2626; }
        .luxury-stock-status svg { flex-shrink: 0; }
    </style>
@endsection
