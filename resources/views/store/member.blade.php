@extends('layouts.store')

@section('title', 'Đặc Quyền Thành Viên · Hạng ' . $tier['name'] . ' | Soopi')
@section('meta_description', 'Khám phá thẻ thành viên ảo, hạng VIP, điểm thưởng tích lũy và đặc quyền riêng của bạn tại Soopi.')

@section('content')
<div class="store-container ht-member-page">
    <header class="ht-member-header">
        <span class="ht-badge-pill">💎 CHƯƠNG TRÌNH KHÁCH HÀNG THÂN THIẾT</span>
        <h1 class="ht-member-title">Đặc Quyền Thành Viên <em>Soopi Club</em></h1>
        <p class="ht-member-subtitle">100.000đ chi tiêu hợp lệ tích 1 điểm; 1 điểm giảm 1.000đ, tối đa 20% tiền hàng sau mã giảm giá. Chỉ tính đơn hoàn tất và đã thanh toán; đơn cũ nhập vào không có giao dịch được tính khi đã hoàn tất. Đơn hủy hoặc hoàn trả không tích điểm. Điểm demo và điểm thật được tách riêng.</p>
    </header>

    <div class="ht-member-top-grid">
        {{-- Virtual VIP Metallic Card --}}
        <div class="ht-vip-card" style="background: {{ $tier['gradient'] }}; color: {{ $tier['text_color'] }}">
            <div class="card-chip-row">
                <span class="card-chip"></span>
                <span class="card-logo">SOOPI PERFUME</span>
            </div>
            <div class="card-middle-row">
                <span class="card-tier-badge">{{ $tier['badge'] }} {{ $tier['name'] }}</span>
                <span class="card-discount-tag">{{ $tier['point_rate'] }}</span>
            </div>
            <div class="card-bottom-row">
                <div class="holder-info">
                    <span class="sub">CHỦ THẺ THÀNH VIÊN</span>
                    <strong class="name">{{ Auth::user()->name }}</strong>
                </div>
                <div class="card-expiry">
                    <span class="sub">MÃ THÀNH VIÊN</span>
                    <strong>HT-{{ str_pad(Auth::id(), 6, '0', STR_PAD_LEFT) }}</strong>
                </div>
            </div>
        </div>

        {{-- Points & Progress Box --}}
        <div class="ht-points-summary-box">
            <div class="points-header">
                <span class="sub-label">ĐIỂM TÍCH LŨY KHẢ DỤNG</span>
                <div class="points-number">
                    <span class="num">{{ number_format($points, 0, ',', '.') }}</span>
                    <span class="unit">điểm</span>
                </div>
                <p class="value-equiv">Tương đương <strong>{{ number_format($points * 1000, 0, ',', '.') }}₫</strong> giảm trực tiếp khi thanh toán đơn hàng.</p>
            </div>

            {{-- Tier Progress --}}
            <div class="tier-progress-card">
                <div class="progress-labels">
                    <span>Tổng chi tiêu: <strong>{{ number_format($totalSpent, 0, ',', '.') }}₫</strong></span>
                    @if($tier['next_tier'])
                    <span>Cần thêm <strong>{{ number_format($tier['needed_amount'], 0, ',', '.') }}₫</strong> lên {{ $tier['next_tier'] }}</span>
                    @else
                    <span style="color: #d4af37; font-weight: 700;">★ Bạn đã đạt hạng VIP cao nhất!</span>
                    @endif
                </div>
                <div class="progress-track">
                    <div class="progress-bar-fill" style="width: {{ $tier['progress_percent'] }}%"></div>
                </div>
                <div class="progress-milestones">
                    <span>Bạc (Silver)</span>
                    <span>Hoa Hồng (1.5Tr)</span>
                    <span>Hoàng Gia (5Tr)</span>
                </div>
            </div>

            <div class="points-actions">
                <a href="{{ route('home') }}#san-pham" class="ht-button ht-button-primary">Mua Sắm Để Tích Điểm</a>
                <a href="{{ route('orders.index') }}" class="ht-button ht-button-outline">Lịch Sử Mua Hàng</a>
            </div>
        </div>
    </div>

    {{-- Tier Comparison Perks Table --}}
    <section class="ht-tier-perks-section">
        <h2 class="section-title">Các Mốc Thành Viên</h2>
        <p class="section-sub">Hạng thẻ ghi nhận mức chi tiêu. Hiện tất cả các hạng áp dụng cùng chính sách điểm, chưa có giảm giá tự động theo hạng.</p>

        <div class="perks-cards-grid">
            {{-- Hạng Bạc --}}
            <div class="tier-perk-card {{ $tier['code'] === 'silver' ? 'current-tier' : '' }}">
                @if($tier['code'] === 'silver')<span class="current-tag">HẠNG HIỆN TẠI</span>@endif
                <div class="card-head silver">
                    <span class="badge">🥈</span>
                    <h3>Hạng Bạc (Silver)</h3>
                    <span class="cond">Chi tiêu dưới 1.500.000₫</span>
                </div>
                <ul class="perks-list">
                    @foreach($tier['perks'] as $perk)<li>✓ {{ $perk }}</li>@endforeach
                </ul>
            </div>

            {{-- Hạng Hoa Hồng --}}
            <div class="tier-perk-card {{ $tier['code'] === 'rose' ? 'current-tier' : '' }}">
                @if($tier['code'] === 'rose')<span class="current-tag">HẠNG HIỆN TẠI</span>@endif
                <div class="card-head rose">
                    <span class="badge">🌹</span>
                    <h3>Hạng Hoa Hồng (Rose)</h3>
                    <span class="cond">Chi tiêu từ 1.500.000₫ đến 5.000.000₫</span>
                </div>
                <ul class="perks-list">
                    @foreach($tier['perks'] as $perk)<li>✓ {{ $perk }}</li>@endforeach
                </ul>
            </div>

            {{-- Hạng Hoàng Gia --}}
            <div class="tier-perk-card premium {{ $tier['code'] === 'premium' ? 'current-tier' : '' }}">
                @if($tier['code'] === 'premium')<span class="current-tag">HẠNG HIỆN TẠI</span>@endif
                <div class="card-head royal">
                    <span class="badge">👑</span>
                    <h3>Hoàng Gia (Premium VIP)</h3>
                    <span class="cond">Chi tiêu tích lũy trên 5.000.000₫</span>
                </div>
                <ul class="perks-list">
                    @foreach($tier['perks'] as $perk)<li>✓ {{ $perk }}</li>@endforeach
                </ul>
            </div>
        </div>
    </section>
</div>

<style>
.ht-member-page {
    padding: 40px 20px 80px;
    max-width: 1080px;
    margin: 0 auto;
}
.ht-member-header {
    text-align: center;
    margin-bottom: 36px;
}
.ht-member-title {
    font: 400 clamp(28px, 5vw, 40px) 'Playfair Display', Georgia, serif;
    color: #2b1f26;
    margin: 12px 0 10px;
}
.ht-member-title em { color: #c2476a; font-style: italic; }
.ht-member-subtitle {
    color: #6d5b64;
    font-size: 16px;
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.6;
}
.ht-member-top-grid {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 30px;
    margin-bottom: 50px;
    align-items: stretch;
}
@media (max-width: 860px) {
    .ht-member-top-grid { grid-template-columns: 1fr; }
}

/* Metallic VIP Card */
.ht-vip-card {
    border-radius: 24px;
    padding: 28px;
    box-shadow: 0 18px 45px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 250px;
    position: relative;
    border: 1px solid rgba(255,255,255,0.2);
}
.card-chip-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.card-chip {
    width: 42px;
    height: 32px;
    background: linear-gradient(135deg, #d4af37, #fef08a);
    border-radius: 6px;
    display: inline-block;
}
.card-logo {
    font: 700 12px 'Playfair Display', Georgia, serif;
    letter-spacing: 2px;
    opacity: 0.9;
}
.card-middle-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 20px 0;
}
.card-tier-badge {
    font-size: 16px;
    font-weight: 700;
}
.card-discount-tag {
    background: rgba(255,255,255,0.2);
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}
.card-bottom-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
}
.card-bottom-row .sub {
    font-size: 9.5px;
    letter-spacing: 1px;
    opacity: 0.8;
    display: block;
    margin-bottom: 2px;
}
.card-bottom-row strong {
    font-size: 16px;
    letter-spacing: 1px;
}

/* Points & Progress Box */
.ht-points-summary-box {
    background: #fff;
    border: 1px solid #fce7f3;
    border-radius: 24px;
    padding: 28px;
    box-shadow: 0 10px 30px rgba(194, 71, 106, 0.06);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.sub-label {
    font-size: 11.5px;
    letter-spacing: 1.5px;
    color: #c2476a;
    font-weight: 700;
    display: block;
}
.points-number {
    display: flex;
    align-items: baseline;
    gap: 6px;
    margin: 4px 0 6px;
}
.points-number .num {
    font-size: 38px;
    font-weight: 700;
    color: #2b1f26;
}
.points-number .unit {
    font-size: 16px;
    color: #8b6b7a;
}
.value-equiv {
    font-size: 14px;
    color: #6d5b64;
    margin: 0 0 16px;
}
.tier-progress-card {
    background: #fdf6f9;
    border-radius: 16px;
    padding: 16px;
    margin-bottom: 20px;
}
.progress-labels {
    display: flex;
    justify-content: space-between;
    font-size: 12.5px;
    color: #55444e;
    margin-bottom: 8px;
}
.progress-track {
    height: 8px;
    background: #eedae3;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 8px;
}
.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #f472b6, #c2476a);
    border-radius: 10px;
    transition: width 0.4s ease;
}
.progress-milestones {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #a08493;
}
.points-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

/* Perks Cards Grid */
.ht-tier-perks-section {
    margin-top: 30px;
}
.section-title {
    text-align: center;
    font: 600 24px Georgia, serif;
    color: #2b1f26;
    margin: 0 0 6px;
}
.section-sub {
    text-align: center;
    color: #715865;
    font-size: 14.5px;
    margin: 0 0 30px;
}
.perks-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}
.tier-perk-card {
    background: #fff;
    border: 1px solid #fce7f3;
    border-radius: 20px;
    padding: 26px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.03);
    position: relative;
    display: flex;
    flex-direction: column;
}
.tier-perk-card.current-tier {
    border: 2px solid #c2476a;
    box-shadow: 0 12px 36px rgba(194, 71, 106, 0.15);
}
.current-tag {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: #c2476a;
    color: #fff;
    font-size: 10.5px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 12px;
    letter-spacing: 1px;
}
.card-head {
    text-align: center;
    border-bottom: 1px solid #f8e8ee;
    padding-bottom: 18px;
    margin-bottom: 18px;
}
.card-head .badge {
    font-size: 36px;
    display: block;
    margin-bottom: 8px;
}
.card-head h3 {
    font: 600 18px Georgia, serif;
    margin: 0 0 4px;
    color: #2b1f26;
}
.card-head .cond {
    font-size: 12.5px;
    color: #8b6b7a;
}
.perks-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
    font-size: 13.5px;
    color: #55444e;
    line-height: 1.45;
}
.perks-list strong { color: #c2476a; }
</style>
@endsection
