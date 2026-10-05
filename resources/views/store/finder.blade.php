@extends('layouts.store')
@section('title', $giftMode ? __('Gợi ý quà tặng · Soopi') : __('Tìm hương dành cho bạn · Soopi'))
@section('content')
<section class="store-container ht-feature-page interior-finder">
    @if($giftMode)
        @include('partials.gift-finder-heading')
        <details class="gift-finder-adjust">
            <summary>{{ __('Thay đổi lựa chọn quà tặng') }}</summary>
    @else
    @include('partials.interior-heading', [
        'eyebrow' => 'LE RENDEZ-VOUS', 'heading' => __('Một cuộc hẹn.'), 'accent' => __('Một mùi hương riêng.'),
        'description' => __('Bắt đầu từ điều bạn yêu thích. Cùng Soopi tìm một mùi hương dành cho khoảnh khắc của bạn.'),
        'art' => 'images/journal/notes.webp',
    ])
    <div class="interior-section-label"><span>{{ __('01 / LẮNG NGHE GU HƯƠNG') }}</span><span>{{ __('Chọn theo cảm xúc của bạn') }}</span></div>
    @endif
    <form action="{{ route('store.finder') }}" method="GET" class="ht-feature-panel ht-finder-form">
        @if($giftMode)<input type="hidden" name="gift" value="1">@endif
        <label><span class="interior-field-number">01</span> {{ __('Phong cách mùi hương') }}
            <select name="style"><option value="">{{ __('Mình muốn khám phá') }}</option><option value="hoa" @selected(request('style') === 'hoa')>{{ __('Hoa cỏ dịu dàng') }}</option><option value="go" @selected(request('style') === 'go')>{{ __('Gỗ thanh lịch') }}</option><option value="vanilla" @selected(request('style') === 'vanilla')>{{ __('Vanilla ngọt ấm') }}</option><option value="tuoi" @selected(request('style') === 'tuoi')>{{ __('Tươi mát') }}</option><option value="am" @selected(request('style') === 'am')>{{ __('Ấm áp') }}</option></select>
        </label>
        <label><span class="interior-field-number">02</span> {{ __('Dịp sử dụng') }}
            <select name="occasion"><option value="">{{ __('Mọi dịp') }}</option><option value="hang-ngay" @selected(request('occasion') === 'hang-ngay')>{{ __('Mỗi ngày') }}</option><option value="hen-ho" @selected(request('occasion') === 'hen-ho')>{{ __('Hẹn hò') }}</option><option value="tiec" @selected(request('occasion') === 'tiec')>{{ __('Tiệc và sự kiện') }}</option></select>
        </label>
        <label><span class="interior-field-number">03</span> {{ __('Gợi ý dành cho') }}
            <select name="gender"><option value="">{{ __('Mọi người') }}</option><option value="nu" @selected(request('gender') === 'nu')>{{ __('Nữ') }}</option><option value="nam" @selected(request('gender') === 'nam')>{{ __('Nam') }}</option><option value="unisex" @selected(request('gender') === 'unisex')>Unisex</option></select>
        </label>
        <label><span class="interior-field-number">04</span> {{ __('Ngân sách tối đa (₫)') }}<input type="number" name="max_price" min="0" max="1000000000" value="{{ request('max_price') }}" placeholder="{{ __('Không giới hạn') }}"></label>
        <button class="ht-button" type="submit">{{ __('Xem gợi ý →') }}</button>
    </form>
    @if($giftMode)</details>@endif
    @if($errors->any())<div class="public-flash alert-danger" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if($giftMode || request()->hasAny(['style','occasion','gender','max_price']))
        <div class="ht-section-heading" id="gift-results"><div><span class="ht-eyebrow">{{ __('DÀNH CHO BẠN') }}</span><h2>{{ $giftMode ? __('Những món quà dành cho bạn chọn') : __('Những mùi hương phù hợp') }}</h2></div></div>
        <div class="ht-feature-grid">
            @forelse($recommended as $perfume)
                <a class="ht-feature-product" href="{{ route('perfumes.show', ['perfume' => $perfume, ...($giftMode ? ['gift' => 1] : [])]) }}">
                    @if($perfume->image_src)<img src="{{ asset(config('scent-gallery.artwork.'.$perfume->slug, $perfume->image_src)) }}" alt="{{ $perfume->localized_name }}" loading="lazy">@endif
                    <span>{{ $perfume->brand }}</span><h3>{{ $perfume->localized_name }}</h3>
                    <p>{{ \Illuminate\Support\Str::limit($perfume->localized_description, 100) }}</p>
                    <strong>{{ number_format((float) ($perfume->sale_price ?? $perfume->price), 0, ',', '.') }}₫</strong>
                </a>
            @empty<p>{{ __('Chưa có sản phẩm phù hợp. Hãy thử lựa chọn khác nhé.') }}</p>@endforelse
        </div>
    @else
        <aside class="interior-finder-note">@include('partials.brand-mark', ['size' => 44, 'class' => 'interior-finder-brand-mark'])<div><h2>{{ __('Chưa biết bắt đầu từ đâu?') }}</h2><p>{{ __('Để cảm xúc dẫn lối qua bốn câu hỏi nhỏ.') }}</p></div><a class="ht-text-link" href="{{ route('store.quiz') }}">{{ __('Làm trắc nghiệm mùi hương ↗') }}</a></aside>
    @endif
</section>
@endsection
