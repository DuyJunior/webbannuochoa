@extends('layouts.store')

@section('title', __('Soopi · Hương thơm của riêng bạn'))
@section('body_class', 'soopi-homepage')

@push('page-styles')
@vite(['resources/css/home-couture.css', 'resources/css/home-reference.css'])
@endpush

@section('content')
@php
    $isFiltered = request()->anyFilled(['search', 'gender', 'category', 'sort', 'min_price', 'max_price', 'concentration', 'note', 'style', 'longevity']);
    $selectedCategory = request('category') ? $categories->firstWhere('id', (int) request('category')) : null;
    $collectionTitle = request()->filled('search') ? __('Mùi hương bạn đang tìm')
        : ($selectedCategory?->localized_name ?? match (request('gender')) {
            'nam' => __('Nước hoa dành cho chàng'),
            'nu' => __('Nước hoa dành cho nàng'),
            'unisex' => __('Hương thơm không giới hạn'),
            default => __('Tuyển chọn để thương nhớ'),
        });
@endphp

<section id="home-live-banner" class="store-container ht-home-live" data-state-url="{{ route('livestream.state') }}" data-current-id="{{ $onAir ? $livestream->id : '' }}" @unless($onAir) hidden @endunless aria-live="polite">
    <div>
        <span class="ht-home-live-badge">{{ __('● ĐANG TRỰC TIẾP') }}</span>
        <strong>{{ $onAir ? $livestream->title : __('Soopi đang livestream') }}</strong>
        <span>{{ __('Xem và trò chuyện cùng Soopi ngay trên website.') }}</span>
    </div>
    <a class="ht-button" href="{{ route('livestream.show') }}">{{ __('Xem livestream →') }}</a>
</section>

@if(!$isFiltered)
@include('partials.home-bloom')
<div class="store-container ht-benefits" aria-label="{{ __('Cam kết của cửa hàng') }}">
    <div>@include('partials.icon', ['name' => 'shield', 'size' => 25])<span><strong>{{ __('Nước hoa chính hãng') }}</strong><small>{{ __('An tâm với từng lựa chọn') }}</small></span></div>
    <div>@include('partials.icon', ['name' => 'gift', 'size' => 25])<span><strong>{{ __('Gói trọn yêu thương') }}</strong><small>{{ __('Chăm chút từng món quà') }}</small></span></div>
    <div>@include('partials.icon', ['name' => 'truck', 'size' => 25])<span><strong>{{ __('Giao hàng tận nơi') }}</strong><small>{{ __('Đóng gói an toàn, cẩn thận') }}</small></span></div>
    <div>@include('partials.icon', ['name' => 'heart', 'size' => 25])<span><strong>{{ __('Tư vấn tận tâm') }}</strong><small>{{ __('Cùng bạn chọn hương phù hợp') }}</small></span></div>
</div>



@endif

@include('partials.scent-gallery')
<div class="ht-compare-bar" id="htCompareBar" hidden><span id="htCompareCount">{{ __('0/3 sản phẩm') }}</span><a href="{{ route('store.compare') }}" id="htCompareLink">{{ __('Xem so sánh →') }}</a><button type="button" id="htCompareClear">{{ __('Xóa') }}</button></div>

@if(!$isFiltered)
@include('partials.home-journal')
@include('partials.home-moments')
@include('partials.store-location')

{{-- ── 4. SẢN PHẨM VỪA XEM GẦN ĐÂY (RECENTLY VIEWED) ── --}}
@php
    $recentIds = session('recently_viewed', []);
    $recentPerfumes = !empty($recentIds) ? \App\Models\Perfume::whereIn('id', array_slice($recentIds, 0, 4))->where('is_active', true)->get() : collect();
@endphp
@if($recentPerfumes->isNotEmpty())
<section class="store-container ht-related-section ht-home-recent">
    <div class="ht-section-heading">
        <div>
            <span class="ht-eyebrow">{{ __('DÀNH RIÊNG CHO BẠN') }}</span>
            <h2>{{ __('Sản phẩm bạn vừa xem gần đây') }}</h2>
        </div>
    </div>
    <div class="ht-related-grid">
        @foreach($recentPerfumes as $recent)
        <a href="{{ route('perfumes.show', $recent) }}" class="ht-related-card">
            <div class="ht-related-img">
                <img src="{{ $recent->image_src ?: asset('images/perfume-default.jpg') }}" alt="{{ $recent->localized_name }}" loading="lazy">
            </div>
            <div class="ht-related-info">
                <span class="ht-related-brand">{{ $recent->brand }}</span>
                <strong class="ht-related-name">{{ $recent->localized_name }}</strong>
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
 function render() {
   document.querySelectorAll('[data-compare-id]').forEach(button => button.classList.toggle('active', selected.has(button.dataset.compareId)));
   bar.hidden = selected.size === 0;
   count.textContent = selected.size + (window.soopiT || (text => text))("/3 sản phẩm");
   link.href = compareBase + '?ids=' + [...selected].join(',');
 }
 document.addEventListener('click', event => {
   const button = event.target.closest('[data-compare-id]');
   if (!button) return;
   const id = button.dataset.compareId;
   if (selected.has(id)) { selected.delete(id); }
   else if (selected.size < 3) { selected.add(id); }
   render();
 });
 document.addEventListener('scent-gallery:updated', render);
 document.getElementById('htCompareClear').addEventListener('click', () => { selected.clear(); render(); });
})();

</script>


@endpush
@endsection

@push('scripts')
<script defer src="{{ asset('js/livestream-home.js') }}"></script>
@vite('resources/js/home-bloom.js')
@endpush
