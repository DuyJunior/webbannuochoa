@extends('layouts.store')

@section('title', 'Tủ Nước Hoa Cá Nhân (Scent Wardrobe) | Soopi')
@section('meta_description', 'Bộ sưu tập mùi hương cá nhân hóa của bạn theo từng dịp: Đi làm, Hẹn hò, Đi tiệc và Thường ngày.')

@section('content')
<div class="store-container ht-wardrobe-page">
    <header class="ht-wardrobe-header">
        <div class="header-left">
            <span class="ht-badge-pill">💎 BỘ SƯU TẬP CÁ NHÂN</span>
            <h1 class="ht-wardrobe-title">Tủ Nước Hoa Của <em>{{ Auth::user()->name }}</em></h1>
            <p class="ht-wardrobe-subtitle">Phân loại và lưu giữ những nốt hương đặc trưng theo từng khoảnh khắc cuộc sống: từ thanh lịch chốn công sở đến nồng nàn những đêm hẹn hò.</p>
        </div>
        <div class="header-actions">
            <button type="button" class="ht-button ht-button-outline" id="shareWardrobeBtn">
                🔗 Chia Sẻ Tủ Nước Hoa
            </button>
            <a href="{{ route('home') }}#san-pham" class="ht-button ht-button-primary">
                + Thêm Mùi Hương Mới
            </a>
        </div>
    </header>

    {{-- Tabs by Occasion --}}
    <div class="ht-wardrobe-tabs">
        <button class="w-tab active" data-target="all">Tất Cả ({{ $wardrobeItems->count() }})</button>
        <button class="w-tab" data-target="work">💼 Đi Làm & Công Sở ({{ $byOccasion['work']->count() }})</button>
        <button class="w-tab" data-target="date">🥂 Hẹn Hò & Lãng Mạn ({{ $byOccasion['date']->count() }})</button>
        <button class="w-tab" data-target="party">👑 Đi Tiệc & Dạ Hội ({{ $byOccasion['party']->count() }})</button>
        <button class="w-tab" data-target="casual">🌿 Thường Ngày & Dạo Phố ({{ $byOccasion['casual']->count() }})</button>
    </div>

    @if($wardrobeItems->isEmpty())
    <div class="ht-wardrobe-empty">
        <div class="empty-icon">🪞</div>
        <h3>Tủ nước hoa của bạn hiện chưa có chai nào</h3>
        <p>Hãy khám phá cửa hàng hoặc làm bài trắc nghiệm để chọn và lưu những mùi hương ưng ý nhất vào từng dịp nhé!</p>
        <div class="empty-actions">
            <a href="{{ route('store.quiz') }}" class="ht-button ht-button-secondary">Làm Trắc Nghiệm Mùi Hương</a>
            <a href="{{ route('home') }}" class="ht-button ht-button-primary">Khám Phá Cửa Hàng</a>
        </div>
    </div>
    @else
    <div class="ht-wardrobe-grid" id="wardrobeGrid">
        @foreach($wardrobeItems as $item)
        <article class="ht-wardrobe-card" data-occasion="{{ $item->occasion }}">
            <div class="card-occasion-tag">
                {{ $item->occasion_label }}
            </div>
            <div class="card-img-wrap">
                <img src="{{ $item->perfume->image_src ?: asset('images/perfume-default.jpg') }}" alt="{{ $item->perfume->name }}" loading="lazy">
            </div>
            <div class="card-body">
                <span class="card-brand">{{ $item->perfume->brand }}</span>
                <h3 class="card-name">
                    <a href="{{ route('perfumes.show', $item->perfume) }}">{{ $item->perfume->name }}</a>
                </h3>
                <p class="card-specs">Dung tích: {{ $item->perfume->volume_ml }}ml · {{ ucfirst($item->perfume->gender) }}</p>
                
                @if($item->notes)
                <div class="card-user-note">
                    <span class="note-quote">“</span>{{ $item->notes }}<span class="note-quote">”</span>
                </div>
                @endif

                <div class="card-scent-notes">
                    <span>Hương đầu: {{ Str::limit($item->perfume->scent_profile['top']['notes'] ?? 'Tươi mát', 35) }}</span>
                    <span>🌿 Độ lưu: {{ $item->perfume->scent_profile['longevity']['text'] ?? '8h' }}</span>
                </div>

                <div class="card-footer">
                    <div class="card-price">
                        <strong>{{ number_format($item->perfume->sale_price ?? $item->perfume->price, 0, ',', '.') }}₫</strong>
                    </div>
                    <div class="card-actions">
                        <form action="{{ route('cart.add', $item->perfume) }}" method="POST" style="display:inline;">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="ht-button ht-button-primary btn-sm">Mua Thêm</button>
                        </form>
                        <form action="{{ route('store.wardrobe.remove', $item->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn bỏ chai này khỏi tủ cá nhân?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="ht-button ht-button-outline btn-sm delete-btn" title="Xóa khỏi tủ">✕</button>
                        </form>
                    </div>
                </div>
            </div>
        </article>
        @endforeach
    </div>
    @endif
</div>

<style>
.ht-wardrobe-page {
    padding: 40px 20px 80px;
    max-width: 1100px;
    margin: 0 auto;
}
.ht-wardrobe-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 20px;
}
.ht-wardrobe-title {
    font: 400 clamp(26px, 4.5vw, 38px) 'Playfair Display', Georgia, serif;
    color: #2b1f26;
    margin: 10px 0 8px;
}
.ht-wardrobe-title em {
    color: #c2476a;
    font-style: italic;
}
.ht-wardrobe-subtitle {
    color: #6d5b64;
    font-size: 15px;
    max-width: 620px;
    margin: 0;
    line-height: 1.55;
}
.header-actions {
    display: flex;
    gap: 12px;
}
.ht-wardrobe-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
    border-bottom: 1px solid #f8e8ee;
    padding-bottom: 12px;
    overflow-x: auto;
}
.w-tab {
    background: none;
    border: none;
    font-size: 14px;
    color: #7d6874;
    padding: 8px 16px;
    border-radius: 20px;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s;
}
.w-tab.active {
    background: #c2476a;
    color: #fff;
    font-weight: 600;
}
.ht-wardrobe-empty {
    text-align: center;
    background: #fffafa;
    border: 1px dashed #f3d4e0;
    border-radius: 24px;
    padding: 60px 20px;
    margin-top: 20px;
}
.empty-icon {
    font-size: 48px;
    margin-bottom: 14px;
}
.ht-wardrobe-empty h3 {
    font: 600 22px Georgia, serif;
    color: #3b2832;
    margin: 0 0 8px;
}
.ht-wardrobe-empty p {
    color: #7b6672;
    max-width: 480px;
    margin: 0 auto 20px;
}
.empty-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}
.ht-wardrobe-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}
.ht-wardrobe-card {
    background: #fff;
    border: 1px solid #fce7f3;
    border-radius: 20px;
    padding: 22px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.03);
    position: relative;
    display: flex;
    flex-direction: column;
    transition: transform 0.2s, box-shadow 0.2s;
}
.ht-wardrobe-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 34px rgba(194, 71, 106, 0.12);
}
.card-occasion-tag {
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
.card-img-wrap {
    text-align: center;
    background: #faf4f7;
    border-radius: 14px;
    padding: 16px;
    margin: 30px 0 16px;
}
.card-img-wrap img {
    height: 140px;
    object-fit: contain;
}
.card-brand {
    font-size: 11px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #c2476a;
    font-weight: 700;
}
.card-name {
    font: 600 18px Georgia, serif;
    margin: 4px 0;
}
.card-name a {
    color: #2b1f26;
    text-decoration: none;
}
.card-specs {
    font-size: 12.5px;
    color: #8b7782;
    margin-bottom: 12px;
}
.card-user-note {
    background: #fff8eb;
    border-left: 3px solid #d97706;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12.5px;
    color: #78350f;
    font-style: italic;
    margin-bottom: 12px;
}
.card-scent-notes {
    font-size: 12px;
    color: #55444e;
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 16px;
    background: #fdf6f9;
    padding: 8px 10px;
    border-radius: 8px;
}
.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid #f8e8ee;
    padding-top: 14px;
    margin-top: auto;
}
.card-price strong {
    color: #c2476a;
    font-size: 17px;
}
.card-actions {
    display: flex;
    gap: 6px;
}
.btn-sm {
    padding: 6px 12px !important;
    font-size: 12.5px !important;
}
.delete-btn {
    color: #991b1b !important;
    border-color: #fee2e2 !important;
}
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Tabs filtering
    const tabs = document.querySelectorAll('.w-tab');
    const cards = document.querySelectorAll('.ht-wardrobe-card');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            const target = this.dataset.target;
            cards.forEach(card => {
                if (target === 'all' || card.dataset.occasion === target) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Share Wardrobe
    const shareBtn = document.getElementById('shareWardrobeBtn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function () {
            const shareUrl = "{{ route('store.wardrobe.share', Auth::user()) }}";
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl);
                alert('✓ Đã sao chép link Tủ nước hoa của bạn!\n' + shareUrl + '\nBạn có thể gửi link này cho bạn bè.');
            } else {
                prompt('Copy link Tủ nước hoa của bạn để gửi cho bạn bè:', shareUrl);
            }
        });
    }
});
</script>
@endpush
@endsection
