@extends('layouts.store')

@section('title', 'Món Quà Mùi Hương Dành Tặng ' . $recipientName . ' · Từ ' . $senderName . ' | Soopi')
@section('meta_description', 'Một món quà mùi hương ngọt ngào và thiệp chúc mừng được gửi trao từ ' . $senderName . ' dành riêng cho ' . $recipientName)

@section('content')
<div class="store-container ht-gift-page">
    <div class="ht-gift-envelope-card">
        <div class="envelope-top-bar">
            <span>@include('partials.icon', ['name' => 'mail', 'size' => '1em']) BẠN VỪA NHẬN ĐƯỢC MỘT MÓN QUÀ MÙI HƯƠNG</span>
        </div>

        <div class="ht-gift-letter">
            <div class="letter-stamp">
                <span>SOOPI<br>PERFUME</span>
            </div>
            <div class="letter-header">
                <span class="to-label">Gửi người thương mến,</span>
                <h2 class="recipient-name">{{ $recipientName }}</h2>
            </div>

            <div class="letter-body">
                <p class="letter-message">{{ $message }}</p>
                <div class="letter-signature">
                    <span class="from-label">Thân gửi từ,</span>
                    <strong class="sender-name">{{ $senderName }}</strong>
                </div>
            </div>
        </div>

        <div class="ht-gift-product-box">
            <div class="gift-prod-img">
                <img src="{{ $perfume->image_src ?: asset('images/perfume-default.jpg') }}" alt="{{ $perfume->name }}" loading="lazy">
            </div>
            <div class="gift-prod-info">
                <span class="gift-prod-brand">{{ $perfume->brand }}</span>
                <h3 class="gift-prod-title">{{ $perfume->name }}</h3>
                <p class="gift-prod-meta">{{ $perfume->category->name ?? 'Nước hoa' }} · Chai {{ $perfume->volume_ml }}ml · {{ ucfirst($perfume->gender) }}</p>
                <p class="gift-prod-desc">{{ Str::limit(strip_tags($perfume->description), 140) }}</p>

                <div class="gift-prod-action">
                    <form action="{{ route('cart.add', $perfume) }}" method="POST">
                        @csrf
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="addon_gift" value="1">
                        <button type="submit" class="ht-button ht-button-primary ht-button-lg">
                            @include('partials.icon', ['name' => 'gift', 'size' => '1em']) Nhận Món Quà & Đặt Giao Về Địa Chỉ Của Bạn
                        </button>
                    </form>
                    <a href="{{ route('perfumes.show', $perfume) }}" class="ht-button ht-button-light">
                        Xem Chi Tiết Chai Nước Hoa Này
                    </a>
                </div>
            </div>
        </div>

        <div class="ht-gift-create-own">
            <h3>Bạn cũng muốn gửi tặng một món quà mùi hương cho bạn bè?</h3>
            <p>Chọn bất kỳ chai nước hoa nào tại Soopi và bấm nút "Gửi tặng bạn bè" để tự tay soạn thiệp chúc mừng nhé!</p>
            <a href="{{ route('home') }}" class="ht-button ht-button-outline">Khám Phá Cửa Hàng</a>
        </div>
    </div>
</div>

<style>
.ht-gift-page {
    padding: 40px 20px 80px;
    max-width: 900px;
    margin: 0 auto;
}
.ht-gift-envelope-card {
    background: #fff;
    border: 1px solid #fce7f3;
    border-radius: 28px;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(194, 71, 106, 0.12);
}
.envelope-top-bar {
    background: linear-gradient(135deg, #c2476a, #e11d48);
    color: #fff;
    text-align: center;
    padding: 12px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 1.5px;
}
.ht-gift-letter {
    background: #fffdfa;
    border-bottom: 2px dashed #f4d0de;
    padding: 40px 48px;
    position: relative;
}
@media (max-width: 600px) {
    .ht-gift-letter { padding: 24px 20px; }
}
.letter-stamp {
    position: absolute;
    top: 30px;
    right: 36px;
    border: 2px dashed #c2476a;
    border-radius: 8px;
    padding: 6px 12px;
    font: 700 10px 'Courier New', monospace;
    color: #c2476a;
    letter-spacing: 1px;
    text-align: center;
    transform: rotate(6deg);
}
.to-label {
    font: italic 15px Georgia, serif;
    color: #8b6b7a;
}
.recipient-name {
    font: 600 clamp(24px, 4vw, 34px) 'Playfair Display', Georgia, serif;
    color: #3b2832;
    margin: 4px 0 16px;
}
.letter-message {
    font: italic 17px Georgia, serif;
    color: #4a3540;
    line-height: 1.7;
    margin: 0 0 24px;
    background: #fff8fb;
    padding: 20px;
    border-radius: 14px;
    border-left: 3px solid #c2476a;
}
.letter-signature {
    text-align: right;
}
.from-label {
    font: italic 14px Georgia, serif;
    color: #8b6b7a;
    display: block;
}
.sender-name {
    font: 600 20px 'Playfair Display', Georgia, serif;
    color: #c2476a;
    display: block;
}
.ht-gift-product-box {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 30px;
    padding: 36px 40px;
    background: #ffffff;
    align-items: center;
}
@media (max-width: 720px) {
    .ht-gift-product-box {
        grid-template-columns: 1fr;
        padding: 24px 20px;
    }
}
.gift-prod-img {
    text-align: center;
    background: #fdf2f8;
    border-radius: 18px;
    padding: 20px;
}
.gift-prod-img img {
    max-height: 200px;
    object-fit: contain;
}
.gift-prod-brand {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: #c2476a;
    font-weight: 700;
}
.gift-prod-title {
    font: 600 clamp(22px, 3.5vw, 28px) Georgia, serif;
    color: #2b1f26;
    margin: 4px 0 6px;
}
.gift-prod-meta {
    font-size: 13.5px;
    color: #8b7782;
    margin-bottom: 12px;
}
.gift-prod-desc {
    font-size: 14px;
    color: #55444e;
    line-height: 1.55;
    margin-bottom: 20px;
}
.gift-prod-action {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.ht-gift-create-own {
    background: #faf4f7;
    text-align: center;
    padding: 28px 20px;
    border-top: 1px solid #f8e8ee;
}
.ht-gift-create-own h3 {
    font: 600 18px Georgia, serif;
    color: #3b2832;
    margin: 0 0 6px;
}
.ht-gift-create-own p {
    font-size: 14px;
    color: #715865;
    margin: 0 auto 16px;
    max-width: 520px;
}
</style>
@endsection
