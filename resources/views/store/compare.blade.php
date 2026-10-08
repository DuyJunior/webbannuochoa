@extends('layouts.store')

@section('title', __('So Sánh Chi Tiết Nước Hoa · Độ Ngọt, Độ Tươi, Độ Lưu Hương | Soopi'))
@section('meta_description', __('So sánh trực quan các dòng nước hoa theo độ ngọt, độ tươi mát, độ tỏa hương, độ lưu hương và giá trị trên từng ml.'))

@section('content')
<div class="store-container ht-compare-page">
    @if($errors->any())
        <div class="public-flash alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
    @endif
    <header class="ht-compare-header">
        <span class="ht-badge-pill">@include('partials.icon', ['name' => 'scale', 'size' => '1em']) {{ __('BẢNG ĐỐI CHIẾU MÙI HƯƠNG') }}</span>
        <h1 class="ht-compare-title">{{ __('Đặt cạnh nhau.') }}<br><em>{{ __('Tìm điều khác biệt.') }}</em></h1>
        <p class="ht-compare-subtitle">{{ __('Đối chiếu trực quan về độ ngọt, độ tươi mát, độ bền mùi và giá trị trên mỗi ml để tìm ra chai nước hoa chân ái nhất của bạn.') }}</p>
    </header>

    @if($perfumes->isEmpty())
    @php
        $samplePairs = \App\Models\Perfume::where('is_active', true)->take(6)->get();
    @endphp
    <div class="ht-compare-empty compare-invitation">
        <div class="compare-invitation-art">
            <span class="interior-kicker">SOOPI / {{ __('HAI DẤU HƯƠNG') }}</span>
            @if($samplePairs->count() >= 2)
            <a class="compare-preview-pair" href="{{ route('store.compare', ['ids' => $samplePairs->take(2)->pluck('id')->join(',')]) }}" aria-label="{{ __('So sánh:') }} {{ $samplePairs[0]->localized_name }} & {{ $samplePairs[1]->localized_name }}">
                @foreach($samplePairs->take(2) as $sample)
                <figure>
                    @if($sample->image_src)
                    <img src="{{ $sample->image_src }}" alt="" width="220" height="280" loading="lazy">
                    @else
                    <div class="compare-preview-placeholder">@include('partials.brand-mark', ['size' => 48])</div>
                    @endif
                    <figcaption><small>{{ $sample->brand }}</small><span>{{ $sample->localized_name }}</span></figcaption>
                </figure>
                @endforeach
            </a>
            @else
            <img class="compare-invitation-still" src="{{ asset('images/journal/detail.webp') }}" alt="" width="600" height="500">
            @endif
            <span class="compare-art-caption">{{ __('Mỗi mùi hương, một cá tính.') }}</span>
        </div>
        <div class="compare-invitation-copy">
        <span class="interior-kicker">{{ __('BẮT ĐẦU KHÁM PHÁ') }}</span>
        <h3>{{ __('Bạn chưa chọn sản phẩm nào để so sánh') }}</h3>
        <p>{{ __('Chọn hai hoặc ba mùi hương. Đặt cạnh nhau để tìm ra lựa chọn hợp với bạn hơn.') }}</p>
        <ol class="compare-howto">
            <li><span>01</span>{{ __('Chọn sản phẩm bằng nút So sánh trong bộ sưu tập.') }}</li>
            <li><span>02</span>{{ __('Đối chiếu tầng hương, độ lưu hương và giá.') }}</li>
        </ol>
        
        <div class="preset-compares">
            @if($samplePairs->count() >= 2)
            <div class="preset-links">
                <a href="{{ route('store.compare', ['ids' => $samplePairs->take(2)->pluck('id')->join(',')]) }}" class="ht-button ht-button-outline">
                    {{ __('So sánh:') }} {{ $samplePairs[0]->localized_name }} vs {{ $samplePairs[1]->localized_name }}
                </a>
                @if($samplePairs->count() >= 3)
                <a href="{{ route('store.compare', ['ids' => $samplePairs->take(3)->pluck('id')->join(',')]) }}" class="ht-button ht-button-outline">
                    {{ __('So sánh bộ ba gợi ý') }}
                </a>
                @endif
            </div>
            @endif
        </div>
        <div style="margin-top: 24px;">
            <a href="{{ route('home') }}#san-pham" class="ht-button ht-button-primary">{{ __('Khám Phá Danh Mục Nước Hoa') }}</a>
        </div>
        </div>
    </div>
    @else
    <div class="ht-compare-table-wrap" tabindex="0" role="region" aria-label="{{ __('Bảng so sánh nước hoa. Cuộn ngang để xem các sản phẩm.') }}">
        <div class="ht-compare-columns" style="--col-count: {{ $perfumes->count() }}">
            {{-- Header info card for each perfume --}}
            @foreach($perfumes as $perfume)
            <div class="compare-col-card">
                <div class="col-card-top">
                    <div class="col-img-frame">
                        <img src="{{ $perfume->image_src ?: asset('images/perfume-default.jpg') }}" alt="{{ $perfume->localized_name }}">
                    </div>
                    <span class="col-brand">{{ $perfume->brand }}</span>
                    <h2 class="col-name"><a href="{{ route('perfumes.show', $perfume) }}">{{ $perfume->localized_name }}</a></h2>
                    <p class="col-meta">{{ __($perfume->category->localized_name ?? __('Nước hoa')) }} · {{ match($perfume->gender) { 'nu' => __('Nữ'), 'nam' => __('Nam'), 'unisex' => 'Unisex', default => $perfume->gender } }} · {{ $perfume->volume_ml }}ml</p>
                    
                    <div class="col-price-box">
                        <span class="price-val">{{ number_format($perfume->sale_price ?? $perfume->price, 0, ',', '.') }}₫</span>
                        @if($perfume->sale_price)
                        <del class="price-old">{{ number_format($perfume->price, 0, ',', '.') }}₫</del>
                        @endif
                    </div>

                    <div class="col-buttons">
                        <form action="{{ route('cart.add', $perfume) }}" method="POST" style="width: 100%;">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="ht-button ht-button-primary" style="width: 100%;">{{ __('Thêm Vào Giỏ') }}</button>
                        </form>
                        <a href="{{ route('perfumes.show', $perfume) }}" class="ht-button ht-button-light" style="width: 100%; text-align: center;">{{ __('Xem Chi Tiết') }}</a>
                    </div>
                </div>

                {{-- Metric Scores --}}
                <div class="col-metrics-list">
                    {{-- 1. Độ ngọt --}}
                    <div class="metric-item">
                        <div class="metric-title-row">
                            <span class="m-label">@include('partials.icon', ['name' => 'candy', 'size' => '1em']) {{ __('Độ Ngọt (Sweetness)') }}</span>
                            <span class="m-val">{{ $perfume->metric_sweetness }}/10</span>
                        </div>
                        <div class="m-bar-track">
                            <div class="m-bar-fill sweet" style="width: {{ $perfume->metric_sweetness * 10 }}%"></div>
                        </div>
                    </div>

                    {{-- 2. Độ tươi mát --}}
                    <div class="metric-item">
                        <div class="metric-title-row">
                            <span class="m-label">@include('partials.icon', ['name' => 'leaf', 'size' => '1em']) {{ __('Độ Tươi Mát (Freshness)') }}</span>
                            <span class="m-val">{{ $perfume->metric_freshness }}/10</span>
                        </div>
                        <div class="m-bar-track">
                            <div class="m-bar-fill fresh" style="width: {{ $perfume->metric_freshness * 10 }}%"></div>
                        </div>
                    </div>

                    {{-- 3. Độ lưu hương --}}
                    <div class="metric-item">
                        <div class="metric-title-row">
                            <span class="m-label">@include('partials.icon', ['name' => 'clock', 'size' => '1em']) {{ __('Độ Lưu Hương') }}</span>
                            <span class="m-val">{{ __($perfume->metric_longevity_hours) }}</span>
                        </div>
                        <div class="m-bar-track">
                            <div class="m-bar-fill longevity" style="width: {{ $perfume->metric_longevity_percent }}%"></div>
                        </div>
                    </div>

                    {{-- 4. Độ tỏa hương --}}
                    <div class="metric-item">
                        <div class="metric-title-row">
                            <span class="m-label">@include('partials.icon', ['name' => 'sparkles', 'size' => '1em']) {{ __('Độ Tỏa Hương') }}</span>
                            <span class="m-val">{{ __($perfume->metric_sillage) }}</span>
                        </div>
                    </div>

                    {{-- 5. Giá trị trên mỗi ml --}}
                    <div class="metric-item highlight-box">
                        <div class="metric-title-row">
                            <span class="m-label">@include('partials.icon', ['name' => 'cash', 'size' => '1em']) {{ __('Giá / 1ml') }}</span>
                            <span class="m-val" style="color: #c2476a; font-weight: 700;">{{ number_format($perfume->metric_price_per_ml, 0, ',', '.') }}₫/ml</span>
                        </div>
                        <span class="m-sub">{{ __('Quy đổi từ chai fullsize') }} {{ $perfume->volume_ml }}ml</span>
                    </div>

                    {{-- 6. Tầng hương nổi bật --}}
                    <div class="metric-notes-section">
                        <h4>{{ __('Tầng Hương Tiêu Biểu') }}</h4>
                        <p class="note-line"><strong>{{ __('Hương đầu:') }}</strong> {{ Str::limit(__($perfume->scent_profile['top']['notes'] ?? __('Tươi mát')), 60) }}</p>
                        <p class="note-line"><strong>{{ __('Hương giữa:') }}</strong> {{ Str::limit(__($perfume->scent_profile['heart']['notes'] ?? __('Hoa cỏ')), 60) }}</p>
                        <p class="note-line"><strong>{{ __('Hương cuối:') }}</strong> {{ Str::limit(__($perfume->scent_profile['base']['notes'] ?? __('Gỗ trầm')), 60) }}</p>
                    </div>

                    {{-- 7. Phong cách phù hợp --}}
                    <div class="metric-style-tag">
                        <span>{{ __('Phong cách:') }} <strong>{{ __($perfume->scent_profile['style']['text'] ?? __('Thanh lịch, lôi cuốn')) }}</strong></span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<style>
.ht-compare-page {
    padding: 40px 20px 80px;
    max-width: 1160px;
    margin: 0 auto;
}
.ht-compare-header {
    text-align: center;
    margin-bottom: 36px;
}
.ht-compare-title {
    font: 400 clamp(28px, 5vw, 40px) 'Playfair Display', 'Soopi Serif', Georgia, serif;
    color: #2b1f26;
    margin: 12px 0 10px;
}
.ht-compare-title em { color: #c2476a; font-style: italic; }
.ht-compare-subtitle {
    color: #6d5b64;
    font-size: 16px;
    max-width: 680px;
    margin: 0 auto;
    line-height: 1.6;
}
.ht-compare-empty {
    text-align: center;
    background: #fffafa;
    border: 1px dashed #f3d4e0;
    border-radius: 24px;
    padding: 60px 20px;
}
.empty-icon { font-size: 48px; margin-bottom: 12px; }
.ht-compare-empty h3 {
    font: 600 22px var(--ht-serif);
    color: #3b2832;
    margin: 0 0 8px;
}
.ht-compare-empty p {
    color: #7b6672;
    max-width: 520px;
    margin: 0 auto 20px;
}
.preset-links {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}
.ht-compare-table-wrap {
    overflow-x: auto;
    padding-bottom: 20px;
}
.ht-compare-columns {
    display: grid;
    grid-template-columns: repeat(var(--col-count, 3), minmax(300px, 1fr));
    gap: 24px;
    align-items: start;
}
.compare-col-card {
    background: #ffffff;
    border: 1px solid #fce7f3;
    border-radius: 24px;
    padding: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
    display: flex;
    flex-direction: column;
}
.col-card-top {
    text-align: center;
    border-bottom: 1px solid #f8e8ee;
    padding-bottom: 22px;
    margin-bottom: 20px;
}
.col-img-frame {
    background: #faf4f7;
    border-radius: 16px;
    padding: 20px;
    margin-bottom: 14px;
}
.col-img-frame img {
    height: 160px;
    object-fit: contain;
}
.col-brand {
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #c2476a;
    font-weight: 700;
}
.col-name {
    font: 600 20px var(--ht-serif);
    margin: 4px 0 6px;
}
.col-name a { color: #2b1f26; text-decoration: none; }
.col-meta { font-size: 13px; color: #8b7782; margin-bottom: 12px; }
.col-price-box { margin-bottom: 16px; }
.price-val { font-size: 22px; font-weight: 700; color: #c2476a; margin-right: 8px; }
.price-old { font-size: 14px; color: #a8949f; }
.col-buttons { display: flex; flex-direction: column; gap: 8px; }

/* Metrics */
.col-metrics-list { display: flex; flex-direction: column; gap: 16px; }
.metric-item { display: flex; flex-direction: column; gap: 6px; }
.metric-title-row { display: flex; justify-content: space-between; font-size: 13px; color: #4a3540; font-weight: 600; }
.m-bar-track {
    height: 8px;
    background: #fdf2f8;
    border-radius: 10px;
    overflow: hidden;
}
.m-bar-fill {
    height: 100%;
    border-radius: 10px;
    transition: width 0.4s ease;
}
.m-bar-fill.sweet { background: linear-gradient(90deg, #fbbf24, #f43f5e); }
.m-bar-fill.fresh { background: linear-gradient(90deg, #38bdf8, #10b981); }
.m-bar-fill.longevity { background: linear-gradient(90deg, #f59e0b, #d97706); }
.highlight-box {
    background: #fff8fb;
    border: 1px solid #fce7f3;
    border-radius: 12px;
    padding: 10px 14px;
}
.m-sub { font-size: 11px; color: #8b6b7a; }
.metric-notes-section {
    background: #faf5f8;
    border-radius: 12px;
    padding: 14px;
    font-size: 12.5px;
    color: #4a3540;
}
.metric-notes-section h4 {
    font-size: 13px;
    margin: 0 0 8px;
    color: #3b2832;
}
.note-line { margin: 0 0 6px; line-height: 1.4; }
.note-line:last-child { margin: 0; }
.metric-style-tag {
    background: #fff0f5;
    border-left: 3px solid #c2476a;
    padding: 8px 12px;
    font-size: 12.5px;
    color: #6d4254;
    border-radius: 4px;
}
</style>
@endsection
