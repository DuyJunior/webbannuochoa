@extends('layouts.store')

@section('title', 'Soopi · Hương thơm của riêng bạn')
@section('body_class', 'soopi-homepage')

@push('page-styles')
@vite(['resources/css/home-couture.css', 'resources/css/home-reference.css'])
@endpush

@section('content')
@php
    $isFiltered = request()->anyFilled(['search', 'gender', 'category', 'sort', 'min_price', 'max_price', 'concentration', 'note', 'style', 'longevity']);
    $selectedCategory = request('category') ? $categories->firstWhere('id', (int) request('category')) : null;
    $collectionTitle = request()->filled('search') ? 'Mùi hương bạn đang tìm'
        : ($selectedCategory?->name ?? match (request('gender')) {
            'nam' => 'Nước hoa dành cho chàng',
            'nu' => 'Nước hoa dành cho nàng',
            'unisex' => 'Hương thơm không giới hạn',
            default => 'Tuyển chọn để thương nhớ',
        });
@endphp

<section id="home-live-banner" class="store-container ht-home-live" data-state-url="{{ route('livestream.state') }}" data-current-id="{{ $onAir ? $livestream->id : '' }}" @unless($onAir) hidden @endunless aria-live="polite">
    <div>
        <span class="ht-home-live-badge">● ĐANG TRỰC TIẾP</span>
        <strong>{{ $onAir ? $livestream->title : 'Soopi đang livestream' }}</strong>
        <span>Xem và trò chuyện cùng Soopi ngay trên website.</span>
    </div>
    <a class="ht-button" href="{{ route('livestream.show') }}">Xem livestream →</a>
</section>

@if(!$isFiltered)
@include('partials.home-bloom')
<div class="store-container ht-benefits" aria-label="Cam kết của cửa hàng">
    <div>@include('partials.icon', ['name' => 'shield', 'size' => 25])<span><strong>Nước hoa chính hãng</strong><small>An tâm với từng lựa chọn</small></span></div>
    <div>@include('partials.icon', ['name' => 'gift', 'size' => 25])<span><strong>Gói trọn yêu thương</strong><small>Chăm chút từng món quà</small></span></div>
    <div>@include('partials.icon', ['name' => 'truck', 'size' => 25])<span><strong>Giao hàng tận nơi</strong><small>Đóng gói an toàn, cẩn thận</small></span></div>
    <div>@include('partials.icon', ['name' => 'heart', 'size' => 25])<span><strong>Tư vấn tận tâm</strong><small>Cùng bạn chọn hương phù hợp</small></span></div>
</div>



@endif

@include('partials.scent-gallery')
<div class="ht-compare-bar" id="htCompareBar" hidden><span id="htCompareCount">0/3 sản phẩm</span><a href="{{ route('store.compare') }}" id="htCompareLink">Xem so sánh →</a><button type="button" id="htCompareClear">Xóa</button></div>

@if(!$isFiltered)
@include('partials.home-journal')
@include('partials.home-moments')

{{-- ── 4. SẢN PHẨM VỪA XEM GẦN ĐÂY (RECENTLY VIEWED) ── --}}
@php
    $recentIds = session('recently_viewed', []);
    $recentPerfumes = !empty($recentIds) ? \App\Models\Perfume::whereIn('id', array_slice($recentIds, 0, 4))->where('is_active', true)->get() : collect();
@endphp
@if($recentPerfumes->isNotEmpty())
<section class="store-container ht-related-section ht-home-recent">
    <div class="ht-section-heading">
        <div>
            <span class="ht-eyebrow">DÀNH RIÊNG CHO BẠN</span>
            <h2>Sản phẩm bạn vừa xem gần đây</h2>
        </div>
    </div>
    <div class="ht-related-grid">
        @foreach($recentPerfumes as $recent)
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
@endif
@push('scripts')
<script>
(() => {
 const compareBase = "{{ route('store.compare') }}";
 const selected = new Set();
 const bar = document.getElementById('htCompareBar');
 const count = document.getElementById('htCompareCount');
 const link = document.getElementById('htCompareLink');
 const buttons = document.querySelectorAll('[data-compare-id]');
 function render() {
   buttons.forEach(button => button.classList.toggle('active', selected.has(button.dataset.compareId)));
   bar.hidden = selected.size === 0;
   count.textContent = selected.size + '/3 sản phẩm';
   link.href = compareBase + '?ids=' + [...selected].join(',');
 }
 buttons.forEach(button => button.addEventListener('click', () => {
   const id = button.dataset.compareId;
   if (selected.has(id)) { selected.delete(id); }
   else if (selected.size < 3) { selected.add(id); }
   render();
 }));
 document.getElementById('htCompareClear').addEventListener('click', () => { selected.clear(); render(); });
})();

</script>


@endpush
@endsection

@push('scripts')
<script defer src="{{ asset('js/livestream-home.js') }}"></script>
@vite('resources/js/home-bloom.js')
@endpush
