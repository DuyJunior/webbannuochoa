@extends('layouts.store')

@section('title', __('Tủ Nước Hoa Của ') . $user->name . ' | Soopi')
@section('meta_description', __('Khám phá bộ sưu tập mùi hương cá nhân tinh tế của ') . $user->name . __(' tại Soopi.'))

@section('content')
<div class="store-container ht-wardrobe-share-page">
    <header class="ht-wardrobe-share-hero">
        <span class="ht-badge-pill">@include('partials.brand-mark', ['size' => 22, 'class' => 'interior-inline-brand-mark']) {{ __('TỦ NƯỚC HOA CHIA SẺ') }}</span>
        <h1 class="ht-share-title">{{ __('Ghé Thăm Tủ Nước Hoa Của') }} <em>{{ $user->name }}</em></h1>
        <p class="ht-share-sub">{{ __('Dưới đây là những nốt hương yêu thích được :name tuyển chọn và phân loại cẩn thận theo từng khoảnh khắc cuộc sống.', ['name' => $user->name]) }}</p>
        <div class="share-hero-actions">
            <a href="{{ route('store.wardrobe') }}" class="ht-button ht-button-outline">{{ __('Tạo Tủ Nước Hoa Của Riêng Bạn') }}</a>
            <a href="{{ route('home') }}" class="ht-button ht-button-primary">{{ __('Khám Phá Cửa Hàng Soopi') }}</a>
        </div>
    </header>

    @if($wardrobeItems->isEmpty())
    <div class="ht-share-empty">
        <p>{{ __('Tủ của') }} {{ $user->name }} {{ __('hiện chưa có mùi hương đang mở bán để hiển thị.') }}</p>
    </div>
    @else
    <div class="ht-share-grid">
        @foreach($wardrobeItems as $item)
        <article class="ht-share-card">
            <div class="card-tag">@include('partials.icon', ['name' => $item->occasion_icon, 'size' => '1em']) {{ __($item->occasion_label) }}</div>
            <div class="card-img">
                <img src="{{ $item->perfume->image_src ?: asset('images/perfume-default.jpg') }}" alt="{{ $item->perfume->localized_name }}" loading="lazy">
            </div>
            <div class="card-info">
                <span class="brand">{{ $item->perfume->brand }}</span>
                <h3 class="name"><a href="{{ route('perfumes.show', $item->perfume) }}">{{ $item->perfume->localized_name }}</a></h3>
                <p class="specs">{{ $item->perfume->category->localized_name ?? __('Nước hoa') }} · {{ match($item->perfume->gender) { 'nu' => __('Nữ'), 'nam' => __('Nam'), 'unisex' => 'Unisex', default => $item->perfume->gender } }}</p>
                
                @if($item->notes)
                <div class="user-quote">“{{ $item->notes }}”</div>
                @endif

                <div class="scent-brief">
                    <span>@include('partials.icon', ['name' => 'flower', 'size' => '1em']) {{ Str::limit(__($item->perfume->scent_profile['top']['notes'] ?? __('Tươi mát')), 35) }}</span>
                    <span>@include('partials.icon', ['name' => 'leaf', 'size' => '1em']) {{ __('Độ lưu:') }} {{ __($item->perfume->scent_profile['longevity']['text'] ?? '8h') }}</span>
                </div>

                <div class="card-foot">
                    <span class="price">{{ number_format($item->perfume->sale_price ?? $item->perfume->price, 0, ',', '.') }}₫</span>
                    <a href="{{ route('perfumes.show', $item->perfume) }}" class="ht-button ht-button-primary btn-sm">{{ __('Xem & Mua Mùi Này') }}</a>
                </div>
            </div>
        </article>
        @endforeach
    </div>
    @endif
</div>

<style>
.ht-wardrobe-share-page {
    padding: 40px 20px 80px;
    max-width: 1060px;
    margin: 0 auto;
}
.ht-wardrobe-share-hero {
    text-align: center;
    background: linear-gradient(135deg, #fff0f5, #ffe4e6);
    border-radius: 24px;
    padding: 40px 20px;
    border: 1px solid #fbcfe8;
    margin-bottom: 40px;
}
.ht-share-title {
    font: 400 clamp(26px, 4.5vw, 36px) 'Playfair Display', 'Soopi Serif', Georgia, serif;
    color: #2b1f26;
    margin: 12px 0 8px;
}
.ht-share-title em { color: #c2476a; font-style: italic; }
.ht-share-sub {
    color: #664d5a;
    font-size: 15.5px;
    max-width: 600px;
    margin: 0 auto 20px;
    line-height: 1.55;
}
.share-hero-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}
.ht-share-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}
.ht-share-card {
    background: #fff;
    border: 1px solid #fce7f3;
    border-radius: 20px;
    padding: 22px;
    position: relative;
    box-shadow: 0 6px 20px rgba(0,0,0,0.03);
    display: flex;
    flex-direction: column;
}
.card-tag {
    position: absolute;
    top: 16px;
    left: 16px;
    background: #fff0f5;
    color: #c2476a;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 12px;
    border: 1px solid #fbcfe8;
}
.card-img {
    text-align: center;
    background: #faf4f7;
    border-radius: 14px;
    padding: 16px;
    margin: 30px 0 16px;
}
.card-img img {
    height: 130px;
    object-fit: contain;
}
.card-info {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}
.brand {
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #c2476a;
    font-weight: 700;
}
.name {
    font: 600 18px var(--ht-serif);
    margin: 4px 0;
}
.name a { color: #2b1f26; text-decoration: none; }
.specs { font-size: 12.5px; color: #8b7782; margin-bottom: 10px; }
.user-quote {
    background: #fff8eb;
    border-left: 3px solid #d97706;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12.5px;
    color: #78350f;
    font-style: italic;
    margin-bottom: 12px;
}
.scent-brief {
    font-size: 12px;
    color: #55444e;
    background: #fdf6f9;
    padding: 8px 10px;
    border-radius: 8px;
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.card-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f8e8ee;
    padding-top: 14px;
    margin-top: auto;
}
.card-foot .price {
    font-size: 17px;
    font-weight: 700;
    color: #c2476a;
}
.btn-sm { padding: 8px 14px !important; font-size: 13px !important; }
</style>
@endsection
