@extends('layouts.store')
@section('title', 'Tìm hương dành cho bạn · Soopi')
@section('content')
<section class="store-container ht-feature-page interior-finder">
    @include('partials.interior-heading', [
        'eyebrow' => 'LE RENDEZ-VOUS', 'heading' => 'Một cuộc hẹn.', 'accent' => 'Một mùi hương riêng.',
        'description' => 'Bắt đầu từ điều bạn yêu thích. Cùng Soopi tìm một mùi hương dành cho khoảnh khắc của bạn.',
        'art' => 'images/journal/notes.webp',
    ])
    <div class="interior-section-label"><span>01 / LẮNG NGHE GU HƯƠNG</span><span>Chọn theo cảm xúc của bạn</span></div>
    <form action="{{ route('store.finder') }}" method="GET" class="ht-feature-panel ht-finder-form">
        <label><span class="interior-field-number">01</span> Phong cách mùi hương
            <select name="style"><option value="">Mình muốn khám phá</option><option value="hoa" @selected(request('style') === 'hoa')>Hoa cỏ dịu dàng</option><option value="go" @selected(request('style') === 'go')>Gỗ thanh lịch</option><option value="vanilla" @selected(request('style') === 'vanilla')>Vanilla ngọt ấm</option><option value="tuoi" @selected(request('style') === 'tuoi')>Tươi mát</option><option value="am" @selected(request('style') === 'am')>Ấm áp</option></select>
        </label>
        <label><span class="interior-field-number">02</span> Dịp sử dụng
            <select name="occasion"><option value="">Mọi dịp</option><option value="hang-ngay" @selected(request('occasion') === 'hang-ngay')>Mỗi ngày</option><option value="hen-ho" @selected(request('occasion') === 'hen-ho')>Hẹn hò</option><option value="tiec" @selected(request('occasion') === 'tiec')>Tiệc và sự kiện</option></select>
        </label>
        <label><span class="interior-field-number">03</span> Gợi ý dành cho
            <select name="gender"><option value="">Mọi người</option><option value="nu" @selected(request('gender') === 'nu')>Nữ</option><option value="nam" @selected(request('gender') === 'nam')>Nam</option><option value="unisex" @selected(request('gender') === 'unisex')>Unisex</option></select>
        </label>
        <label><span class="interior-field-number">04</span> Ngân sách tối đa (₫)<input type="number" name="max_price" min="0" max="1000000000" value="{{ request('max_price') }}" placeholder="Không giới hạn"></label>
        <button class="ht-button" type="submit">Xem gợi ý →</button>
    </form>
    @if($errors->any())<div class="public-flash alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if(request()->hasAny(['style','occasion','gender','max_price']))
        <div class="ht-section-heading"><div><span class="ht-eyebrow">DÀNH CHO BẠN</span><h2>Những mùi hương phù hợp</h2></div></div>
        <div class="ht-feature-grid">
            @forelse($recommended as $perfume)
                <a class="ht-feature-product" href="{{ route('perfumes.show', $perfume) }}">
                    @if($perfume->image_src)<img src="{{ asset(config('scent-gallery.artwork.'.$perfume->slug, $perfume->image_src)) }}" alt="{{ $perfume->name }}" loading="lazy">@endif
                    <span>{{ $perfume->brand }}</span><h3>{{ $perfume->name }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit($perfume->description, 100) }}</p>
                    <strong>{{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}₫</strong>
                </a>
            @empty<p>Chưa có sản phẩm phù hợp. Hãy thử lựa chọn khác nhé.</p>@endforelse
        </div>
    @else
        <aside class="interior-finder-note">@include('partials.brand-mark', ['size' => 44, 'class' => 'interior-finder-brand-mark'])<div><h2>Chưa biết bắt đầu từ đâu?</h2><p>Để cảm xúc dẫn lối qua bốn câu hỏi nhỏ.</p></div><a class="ht-text-link" href="{{ route('store.quiz') }}">Làm trắc nghiệm mùi hương ↗</a></aside>
    @endif
</section>
@endsection
