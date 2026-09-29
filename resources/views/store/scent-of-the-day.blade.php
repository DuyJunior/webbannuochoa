@extends('layouts.store')

@section('title', 'Mùi Hương Hôm Nay (Scent of the Day) · ' . $perfume->name . ' | Soopi')
@section('meta_description', 'Khám phá nốt hương được Soopi tuyển chọn cho ngày hôm nay: ' . $perfume->name . ' kèm ưu đãi độc quyền giảm 10% với mã ' . $todayCode)

@section('content')
<div class="store-container ht-sotd-page">
    <div class="ht-sotd-date-badge">
        <span>@include('partials.icon', ['name' => 'calendar', 'size' => '1em']) {{ now()->locale('vi')->isoFormat('dddd, [ngày] D [tháng] M, YYYY') }}</span>
    </div>

    <div class="ht-sotd-hero-card">
        <div class="sotd-image-col">
            <div class="sotd-img-frame">
                <img src="{{ $perfume->image_src ?: asset('images/perfume-default.jpg') }}" alt="{{ $perfume->name }}" class="sotd-main-img">
                <span class="sotd-daily-ribbon">@include('partials.icon', ['name' => 'star', 'size' => '1em']) MÙI HƯƠNG HÔM NAY</span>
            </div>
            <div class="sotd-coupon-box">
                <span class="coupon-label">ĐẶC QUYỀN TRONG NGÀY HÔM NAY:</span>
                <div class="coupon-code-pill">
                    <span class="code" id="sotdCoupon">{{ $todayCode }}</span>
                    <button type="button" class="copy-btn" id="copySotdBtn">Sao chép</button>
                </div>
                <p class="coupon-hint">Giảm thêm 10% trực tiếp khi nhập mã này tại bước thanh toán.</p>
            </div>
        </div>

        <div class="sotd-content-col">
            <div class="sotd-quote-box">
                <span class="quote-mark">“</span>
                <p class="quote-text">{{ $quote }}</p>
                <span class="quote-author">— Soopi Inspiration</span>
            </div>

            <span class="sotd-brand">{{ $perfume->brand }}</span>
            <h1 class="sotd-title">{{ $perfume->name }}</h1>
            <p class="sotd-meta">{{ $perfume->category->name ?? 'Nước hoa cao cấp' }} · {{ ucfirst($perfume->gender) }} · Chai {{ $perfume->volume_ml }}ml</p>

            <div class="sotd-story">
                <h3>Vì sao đây là mùi hương lý tưởng cho hôm nay?</h3>
                <p>{{ $perfume->description ?: 'Một sự pha trộn tinh tế giữa các nốt hương đầu tươi mát và tầng hương cuối ấm áp, lưu giữ cảm xúc trọn vẹn suốt ngày dài.' }}</p>
            </div>

            {{-- 3 Pyramid Notes --}}
            <div class="sotd-pyramid-grid">
                <div class="pyramid-item">
                    <span class="pyramid-icon">@include('partials.icon', ['name' => 'citrus', 'size' => '1em'])</span>
                    <strong>Hương Đầu</strong>
                    <p>{{ Str::limit($perfume->scent_profile['top']['notes'] ?? 'Tươi mát, thanh khiết', 45) }}</p>
                </div>
                <div class="pyramid-item">
                    <span class="pyramid-icon">@include('partials.icon', ['name' => 'flower', 'size' => '1em'])</span>
                    <strong>Hương Giữa</strong>
                    <p>{{ Str::limit($perfume->scent_profile['heart']['notes'] ?? 'Hoa cỏ kiều diễm', 45) }}</p>
                </div>
                <div class="pyramid-item">
                    <span class="pyramid-icon">@include('partials.icon', ['name' => 'tree', 'size' => '1em'])</span>
                    <strong>Hương Cuối</strong>
                    <p>{{ Str::limit($perfume->scent_profile['base']['notes'] ?? 'Gỗ trầm sâu lắng', 45) }}</p>
                </div>
            </div>

            <div class="sotd-pricing-action">
                <div class="sotd-price-wrap">
                    @php
                        $regularPrice = $perfume->sale_price ?? $perfume->price;
                        $dealPrice = round($regularPrice * 0.9 / 1000) * 1000;
                    @endphp
                    <span class="deal-label">Giá độc quyền hôm nay (với mã {{ $todayCode }}):</span>
                    <div class="price-numbers">
                        <span class="deal-price">{{ number_format($dealPrice, 0, ',', '.') }}₫</span>
                        <del class="original-price">{{ number_format($regularPrice, 0, ',', '.') }}₫</del>
                    </div>
                </div>

                <div class="sotd-buttons">
                    <form action="{{ route('cart.add', $perfume) }}" method="POST" style="display:inline;">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="ht-button ht-button-primary ht-button-lg">
                            Đặt Mua Ngay Hôm Nay
                        </button>
                    </form>
                    <a href="{{ route('perfumes.show', $perfume) }}" class="ht-button ht-button-outline">
                        Xem Chi Tiết Mùi Hương
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ht-sotd-page {
    padding: 40px 20px 80px;
    max-width: 1040px;
    margin: 0 auto;
}
.ht-sotd-date-badge {
    text-align: center;
    margin-bottom: 24px;
}
.ht-sotd-date-badge span {
    background: #fff0f5;
    color: #c2476a;
    font-size: 13px;
    font-weight: 700;
    padding: 6px 16px;
    border-radius: 20px;
    border: 1px solid #fbcfe8;
    display: inline-block;
}
.ht-sotd-hero-card {
    background: #ffffff;
    border: 1px solid #fce7f3;
    border-radius: 28px;
    padding: 36px;
    box-shadow: 0 16px 45px rgba(194, 71, 106, 0.09);
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 40px;
    align-items: center;
}
@media (max-width: 820px) {
    .ht-sotd-hero-card {
        grid-template-columns: 1fr;
        padding: 24px;
    }
}
.sotd-image-col {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.sotd-img-frame {
    position: relative;
    background: radial-gradient(circle, #fff1f5 0%, #fae8f0 100%);
    border-radius: 22px;
    padding: 30px;
    text-align: center;
    border: 1px solid #fbcfe8;
}
.sotd-main-img {
    max-height: 280px;
    object-fit: contain;
    transition: transform 0.3s ease;
}
.sotd-main-img:hover { transform: scale(1.05); }
.sotd-daily-ribbon {
    position: absolute;
    top: 14px;
    left: 14px;
    background: linear-gradient(135deg, #c2476a, #e11d48);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 12px;
    letter-spacing: 1px;
}
.sotd-coupon-box {
    background: #fff8f9;
    border: 1.5px dashed #f472b6;
    border-radius: 16px;
    padding: 16px;
    text-align: center;
}
.coupon-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #c2476a;
    margin-bottom: 8px;
}
.coupon-code-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid #fbcfe8;
    border-radius: 10px;
    padding: 6px 12px;
}
.coupon-code-pill .code {
    font: 700 18px 'Courier New', monospace;
    letter-spacing: 2px;
    color: #c2476a;
}
.copy-btn {
    background: #c2476a;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}
.coupon-hint {
    font-size: 12px;
    color: #8b6b7a;
    margin: 8px 0 0;
}
.sotd-quote-box {
    background: #faf4f7;
    border-radius: 16px;
    padding: 16px 20px;
    margin-bottom: 20px;
    position: relative;
}
.quote-text {
    font: italic 15.5px Georgia, serif;
    color: #553e4c;
    margin: 0 0 6px;
    line-height: 1.5;
}
.quote-author {
    font-size: 12px;
    color: #9d7b8d;
    display: block;
}
.sotd-brand {
    font-size: 12px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #c2476a;
    font-weight: 700;
}
.sotd-title {
    font: 600 clamp(24px, 4vw, 36px) 'Playfair Display', Georgia, serif;
    color: #2b1f26;
    margin: 4px 0 6px;
}
.sotd-meta {
    font-size: 14px;
    color: #8b7782;
    margin-bottom: 18px;
}
.sotd-story h3 {
    font: 600 16px Georgia, serif;
    color: #3b2832;
    margin: 0 0 6px;
}
.sotd-story p {
    font-size: 14px;
    color: #634f5b;
    line-height: 1.6;
    margin: 0 0 20px;
}
.sotd-pyramid-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}
@media (max-width: 540px) {
    .sotd-pyramid-grid { grid-template-columns: 1fr; }
}
.pyramid-item {
    background: #fff0f5;
    border: 1px solid #fce7f3;
    border-radius: 12px;
    padding: 12px;
    text-align: center;
}
.pyramid-icon { font-size: 20px; display: block; margin-bottom: 4px; }
.pyramid-item strong { font-size: 13px; color: #3b2832; display: block; margin-bottom: 4px; }
.pyramid-item p { font-size: 11.5px; color: #785a6a; margin: 0; line-height: 1.35; }
.sotd-pricing-action {
    border-top: 1px solid #f8e8ee;
    padding-top: 20px;
}
.deal-label { font-size: 13px; color: #8b6b7a; display: block; margin-bottom: 4px; }
.price-numbers { display: flex; align-items: baseline; gap: 12px; margin-bottom: 16px; }
.deal-price { font-size: 28px; font-weight: 700; color: #c2476a; }
.original-price { font-size: 16px; color: #a8949f; }
.sotd-buttons { display: flex; gap: 12px; flex-wrap: wrap; }
.ht-button-lg { padding: 14px 28px !important; font-size: 16px !important; }
</style>

@push('scripts')
<script>
document.getElementById('copySotdBtn').addEventListener('click', function () {
    const code = document.getElementById('sotdCoupon').textContent.trim();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(code);
    }
    this.textContent = 'Đã chép!';
    setTimeout(() => { this.textContent = 'Sao chép'; }, 2000);
});
</script>
@endpush
@endsection
