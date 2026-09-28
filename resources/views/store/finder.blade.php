@extends('layouts.store')
@section('title', 'Tìm hương dành cho bạn · Soopi')
@section('content')
<section class="store-container ht-feature-page">
    <span class="ht-eyebrow">MỘT CHÚT THẤU HIỂU</span>
    <h1>Tìm mùi hương <em>của riêng bạn.</em></h1>
    <p>Chọn ba điều bạn thích. Soopi sẽ gợi ý từ những chai nước hoa đang có tại cửa hàng.</p>
    <form action="{{ route('store.finder') }}" method="GET" class="ht-feature-panel ht-finder-form">
        <label>Phong cách mùi hương
            <select name="style"><option value="">Mình muốn khám phá</option><option value="hoa" @selected(request('style') === 'hoa')>Hoa cỏ dịu dàng</option><option value="go" @selected(request('style') === 'go')>Gỗ thanh lịch</option><option value="vanilla" @selected(request('style') === 'vanilla')>Vanilla ngọt ấm</option><option value="tuoi" @selected(request('style') === 'tuoi')>Tươi mát</option><option value="am" @selected(request('style') === 'am')>Ấm áp</option></select>
        </label>
        <label>Dịp sử dụng
            <select name="occasion"><option value="">Mọi dịp</option><option value="hang-ngay" @selected(request('occasion') === 'hang-ngay')>Mỗi ngày</option><option value="hen-ho" @selected(request('occasion') === 'hen-ho')>Hẹn hò</option><option value="tiec" @selected(request('occasion') === 'tiec')>Tiệc và sự kiện</option></select>
        </label>
        <label>Gợi ý dành cho
            <select name="gender"><option value="">Mọi người</option><option value="nu" @selected(request('gender') === 'nu')>Nữ</option><option value="nam" @selected(request('gender') === 'nam')>Nam</option><option value="unisex" @selected(request('gender') === 'unisex')>Unisex</option></select>
        </label>
        <button class="ht-button" type="submit">Xem gợi ý →</button>
    </form>
    @if(request()->hasAny(['style','occasion','gender']))
        <div class="ht-section-heading"><div><span class="ht-eyebrow">DÀNH CHO BẠN</span><h2>Những mùi hương phù hợp</h2></div></div>
        <div class="ht-feature-grid">
            @forelse($recommended as $perfume)
                <a class="ht-feature-product" href="{{ route('perfumes.show', $perfume) }}">
                    @if($perfume->image_src)<img src="{{ $perfume->image_src }}" alt="{{ $perfume->name }}" loading="lazy">@endif
                    <span>{{ $perfume->brand }}</span><h3>{{ $perfume->name }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit($perfume->description, 100) }}</p>
                    <strong>{{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}₫</strong>
                </a>
            @empty<p>Chưa có sản phẩm phù hợp. Hãy thử lựa chọn khác nhé.</p>@endforelse
        </div>
    @endif
</section>
@endsection
