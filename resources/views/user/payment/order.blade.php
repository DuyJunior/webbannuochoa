@extends('layouts.store')

@section('title', 'Lịch sử đơn hàng · Soopi')

@section('content')
<div class="orders-history-page">
    <div class="store-container">
        {{-- Breadcrumb --}}
        <nav class="orders-breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span>/</span>
            <span class="active">Đơn hàng của tôi</span>
        </nav>

        <div class="orders-page-header">
            <div>
                <span class="badge-tag">NHỮNG MÙI HƯƠNG BẠN ĐÃ CHỌN</span>
                <h1>Đơn hàng của bạn</h1>
                <p>Theo dõi tiến trình vận chuyển đơn hàng qua Giao Hàng Nhanh (GHN)</p>
            </div>
            <a href="{{ route('home') }}#san-pham" class="btn-continue-shop">+ Tiếp tục mua sắm</a>
        </div>

        @if($orders->isEmpty())
            <div class="empty-orders-card">
                <div class="empty-icon">@include('partials.icon', ['name' => 'box', 'size' => '1em'])</div>
                <h3>Bạn chưa có đơn hàng nào</h3>
                <p>Hãy khám phá bộ sưu tập nước hoa cao cấp tại Soopi và đặt hàng ngay hôm nay!</p>
                <a href="{{ route('home') }}#san-pham" class="btn-shop-now">Khám phá sản phẩm</a>
            </div>
        @else
            <div class="orders-list-wrapper">
                @foreach ($orders as $order)
                    @php
                        $statusLabels = [
                            'pending' => ['text' => 'Chờ xử lý', 'class' => 'status-pending'],
                            'ready_to_pick' => ['text' => 'GHN đã nhận đơn', 'class' => 'status-ready'],
                            'delivering' => ['text' => 'Đang giao hàng', 'class' => 'status-shipping'],
                            'delivered' => ['text' => 'Giao thành công', 'class' => 'status-delivered'],
                            'cancelled' => ['text' => 'Đã hủy', 'class' => 'status-cancelled'],
                            'not_shipped' => ['text' => 'Chưa giao', 'class' => 'status-pending'],
                        ];
                        $st = $statusLabels[$order->shipping_status] ?? ['text' => $order->shipping_status, 'class' => 'status-pending'];
                    @endphp

                    <div class="order-card-box">
                        <div class="order-card-top">
                            <div class="order-info-group">
                                <span class="order-id-badge">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px; margin-right:4px;"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                                    #DH{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                </span>
                                <span class="order-time">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px; margin-right:3px;"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </span>
                                @if($order->ghn_order_code)
                                    <span class="ghn-code-pill">
                                        @include('partials.icon', ['name' => 'truck', 'size' => '1em']) GHN: <strong>{{ $order->ghn_order_code }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="order-status-group">
                                @if($order->gift_wrap || $order->gift_card || $order->gift_message)
                                    <span class="badge-gift">
                                        @include('partials.icon', ['name' => 'gift', 'size' => '1em']) Quà tặng
                                    </span>
                                @endif
                                <span class="shipping-status-tag {{ $st['class'] }}">{{ $st['text'] }}</span>
                            </div>
                        </div>

                        <div class="order-card-content">
                            <div class="order-items-preview">
                                @foreach ($order->items as $item)
                                    @php $prod = $item->product ?? $item->perfume; @endphp
                                    <div class="item-mini-row">
                                        <div class="item-mini-thumb">
                                            @if($prod && $prod->image_src)
                                                <img src="{{ $prod->image_src }}" alt="{{ $prod->name }}">
                                            @else
                                                <span>@include('partials.icon', ['name' => 'flower', 'size' => '1em'])</span>
                                            @endif
                                        </div>
                                        <div class="item-mini-info">
                                            <span class="item-mini-name">{{ $item->product_name ?? $prod->name ?? 'Nước hoa cao cấp' }}</span>
                                            <div class="item-mini-meta">
                                                <span>{{ $item->volume_ml ? $item->volume_ml.'ml' : '100ml' }}</span>
                                                <span class="meta-dot">·</span>
                                                <span>Số lượng: <strong>x{{ $item->quantity }}</strong></span>
                                                <span class="meta-dot">·</span>
                                                <span class="item-price-tag">{{ number_format($item->price, 0, ',', '.') }}₫</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="order-finance-summary">
                                <div class="finance-row">
                                    <span>Cước vận chuyển GHN:</span>
                                    <strong>{{ number_format($order->ghn_total_fee, 0, ',', '.') }}₫</strong>
                                </div>
                                <div class="finance-row total-row">
                                    <span>Tổng thanh toán:</span>
                                    <strong class="total-price-highlight">{{ number_format($order->total_price, 0, ',', '.') }}₫</strong>
                                </div>
                            </div>
                        </div>

                        <div class="order-card-footer">
                            <div class="order-receiver-info">
                                <div class="receiver-address">
                                    <span class="icon-pin">@include('partials.icon', ['name' => 'pin', 'size' => '1em'])</span>
                                    <strong>{{ $order->name }}</strong> ({{ $order->phone }}) 
                                    <span class="addr-text">— {{ $order->address }}</span>
                                </div>
                                <div class="payment-method-row">
                                    @php
                                        $lastTx = $order->paymentTransactions?->first();
                                        $gw = $lastTx?->gateway;
                                    @endphp
                                    @if($order->status === 'paid')
                                        @if($gw === 'atm_domestic')
                                            <span class="pay-badge pay-success">@include('partials.icon', ['name' => 'check', 'size' => '1em']) Thẻ ATM Nội Địa (Đã thanh toán)</span>
                                        @elseif($gw === 'atm_international')
                                            <span class="pay-badge pay-success">@include('partials.icon', ['name' => 'check', 'size' => '1em']) Visa/Mastercard (Đã thanh toán)</span>
                                        @else
                                            <span class="pay-badge pay-success">@include('partials.icon', ['name' => 'check', 'size' => '1em']) Ví MoMo (Đã thanh toán)</span>
                                        @endif
                                    @elseif($order->status === 'cod_ordered')
                                        <span class="pay-badge pay-cod">@include('partials.icon', ['name' => 'cash', 'size' => '1em']) Thanh toán khi nhận hàng (COD)</span>
                                    @elseif($order->status === 'pending')
                                        <span class="pay-badge pay-pending">@include('partials.icon', ['name' => 'hourglass', 'size' => '1em']) Chờ thanh toán</span>
                                    @endif
                                </div>
                            </div>
                            <div class="order-actions">
                                @if($order->status === 'pending')
                                    <a href="{{ route('user.orders.momo.pay', $order->id) }}" class="btn-pay-again">
                                        <img src="{{ asset('images/payments/momo.svg') }}" alt="MoMo" style="height:17px; width:17px; border-radius:3px; object-fit:contain;">
                                        <span>Thanh toán lại qua MoMo</span>
                                    </a>
                                @endif
                                <a href="{{ route('orders.show', $order->id) }}" class="btn-detail">Xem chi tiết đơn →</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="orders-pagination">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

<style>
.orders-history-page {
    padding: 32px 0 80px;
    background: #faf8f9;
    min-height: 80vh;
}
.orders-breadcrumb {
    display: flex;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 24px;
}
.orders-breadcrumb a {
    color: #4b5563;
    text-decoration: none;
}
.orders-breadcrumb a:hover {
    color: #db2777;
}
.orders-breadcrumb .active {
    color: #db2777;
    font-weight: 600;
}

.orders-page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 30px;
}
.orders-page-header .badge-tag {
    display: inline-block;
    background: #fce7f3;
    color: #be185d;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    padding: 4px 10px;
    border-radius: 9999px;
    margin-bottom: 8px;
    text-transform: uppercase;
}
.orders-page-header h1 {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 2.2rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 6px 0;
}
.orders-page-header p {
    color: #6b7280;
    font-size: 0.95rem;
    margin: 0;
}
.btn-continue-shop {
    padding: 10px 18px;
    background: #ffffff;
    border: 1.5px solid #fbcfe8;
    color: #db2777;
    font-size: 0.88rem;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-continue-shop:hover {
    background: #fdf2f8;
}

/* Empty Card */
.empty-orders-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 60px 20px;
    text-align: center;
    border: 1px solid #f3e8ee;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    max-width: 520px;
    margin: 40px auto;
}
.empty-icon {
    font-size: 3.5rem;
    margin-bottom: 16px;
}
.empty-orders-card h3 {
    font-size: 1.35rem;
    color: #1f2937;
    margin-bottom: 8px;
}
.empty-orders-card p {
    color: #6b7280;
    font-size: 0.92rem;
    line-height: 1.5;
    margin-bottom: 24px;
}
.btn-shop-now {
    display: inline-block;
    padding: 12px 28px;
    background: linear-gradient(135deg, #f472b6 0%, #db2777 100%);
    color: #ffffff;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(219, 39, 119, 0.25);
    transition: all 0.2s;
}
.btn-shop-now:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(219, 39, 119, 0.35);
}

/* Orders List */
.orders-list-wrapper {
    display: flex;
    flex-direction: column;
    gap: 22px;
}
.order-card-box {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #f0dfe5;
    box-shadow: 0 6px 24px rgba(75, 20, 40, 0.04);
    overflow: hidden;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.order-card-box:hover {
    box-shadow: 0 10px 30px rgba(75, 20, 40, 0.07);
}
.order-card-top {
    padding: 16px 24px;
    background: #fffafc;
    border-bottom: 1px solid #f6ebf0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.order-info-group {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.order-id-badge {
    font-weight: 700;
    color: #831843;
    font-size: 0.95rem;
    background: #fdf2f8;
    padding: 5px 12px;
    border-radius: 8px;
    border: 1px solid #fbcfe8;
    display: inline-flex;
    align-items: center;
}
.order-time {
    color: #6b7280;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
}
.ghn-code-pill {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
    font-size: 0.8rem;
    padding: 4px 12px;
    border-radius: 9999px;
    font-weight: 500;
}
.ghn-code-pill strong {
    color: #1d4ed8;
}

.order-status-group {
    display: flex;
    align-items: center;
    gap: 8px;
}
.badge-gift {
    background: #fce7f3;
    color: #9d174d;
    font-size: 0.8rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 9999px;
    border: 1px solid #fbcfe8;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.shipping-status-tag {
    font-size: 0.8rem;
    font-weight: 700;
    padding: 4px 14px;
    border-radius: 9999px;
    display: inline-block;
}
.status-pending {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}
.status-ready {
    background: #e0e7ff;
    color: #3730a3;
    border: 1px solid #c7d2fe;
}
.status-shipping {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}
.status-delivered {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}
.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.order-card-content {
    padding: 22px 24px;
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 24px;
    align-items: center;
}
.order-items-preview {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.item-mini-row {
    display: flex;
    align-items: center;
    gap: 14px;
}
.item-mini-thumb {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    border: 1px solid #f0dfe5;
    background: #fff8fa;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.item-mini-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.item-mini-info {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.item-mini-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1f2937;
}
.item-mini-meta {
    font-size: 0.82rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.meta-dot {
    color: #d1d5db;
}
.item-price-tag {
    color: #be185d;
    font-weight: 600;
}

.order-finance-summary {
    background: #fff8fa;
    border: 1px solid #fce7f3;
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.finance-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.88rem;
    color: #4b5563;
}
.total-row {
    margin-top: 6px;
    padding-top: 10px;
    border-top: 1px dashed #fbcfe8;
}
.total-row span {
    font-weight: 700;
    color: #1f2937;
}
.total-price-highlight {
    color: #be185d;
    font-size: 1.25rem;
    font-weight: 800;
}

.order-card-footer {
    padding: 16px 24px;
    background: #ffffff;
    border-top: 1px solid #f6ebf0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}
.order-receiver-info {
    font-size: 0.85rem;
    color: #4b5563;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.receiver-address {
    line-height: 1.45;
}
.receiver-address strong {
    color: #111827;
}
.addr-text {
    color: #6b7280;
}
.payment-method-row {
    display: flex;
    gap: 8px;
    align-items: center;
}
.pay-badge {
    font-size: 0.76rem;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
}
.pay-success {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.pay-cod {
    background: #e0f2fe;
    color: #0369a1;
    border: 1px solid #bae6fd;
}
.pay-pending {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
}
.order-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}
.btn-detail {
    padding: 9px 18px;
    background: #fff;
    border: 1.5px solid #e5e7eb;
    color: #374151;
    font-size: 0.85rem;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-detail:hover {
    border-color: #db2777;
    color: #be185d;
    background: #fff4f7;
}

.orders-pagination {
    margin-top: 30px;
    display: flex;
    justify-content: center;
}

@media (max-width: 768px) {
    .order-card-content {
        grid-template-columns: 1fr;
    }
    .order-finance-summary {
        border-left: none;
        padding-left: 0;
        border-top: 1px dashed #f3f4f6;
        padding-top: 14px;
    }
    .orders-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 14px;
    }
}

/* ===== Pay Again Dropdown ===== */
.pay-again-dropdown { position: relative; display: inline-block; }
.btn-pay-again-trigger {
    display: inline-flex; align-items: center; gap: 7px;
    background: linear-gradient(135deg, #a50064 0%, #d82d8b 100%);
    color: #fff; font-weight: 700; font-size: 0.85rem;
    padding: 9px 14px; border-radius: 8px; border: none;
    cursor: pointer; box-shadow: 0 2px 10px rgba(165,0,100,0.3);
    transition: all 0.2s; white-space: nowrap;
}
.btn-pay-again-trigger:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(165,0,100,0.4); }
.pay-arrow { font-size: 0.65rem; transition: transform 0.2s; }
.pay-arrow.open { transform: rotate(180deg); }

.pay-drop-menu {
    position: absolute; bottom: calc(100% + 6px); left: 0;
    min-width: 240px; background: #fff;
    border: 1.5px solid #f9a8d4; border-radius: 14px;
    box-shadow: 0 8px 28px rgba(165,0,100,0.15);
    z-index: 9999; overflow: hidden;
    animation: payDropIn 0.18s ease;
}
@keyframes payDropIn {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.pay-drop-header {
    padding: 9px 14px 7px; font-size: 0.73rem; font-weight: 700;
    color: #9ca3af; text-transform: uppercase; letter-spacing: 0.06em;
    border-bottom: 1px solid #fdf2f8;
}
.pay-drop-item {
    display: flex; align-items: center; gap: 10px;
    padding: 11px 14px; text-decoration: none;
    border-bottom: 1px solid #fdf2f8; transition: background 0.14s;
}
.pay-drop-item:last-child { border-bottom: none; }
.pay-drop-item:hover { background: #fdf2f8; }
.pay-ditem-icon {
    width: 34px; height: 34px; border-radius: 9px; display: flex;
    align-items: center; justify-content: center; flex-shrink: 0;
    font-size: 1.1rem;
}
.pay-ditem-icon.momo-bg { background: #fce7f3; }
.pay-ditem-icon.domestic-bg { background: #dbeafe; }
.pay-ditem-icon.intl-bg { background: #e0e7ff; }
.pay-ditem-info { display: flex; flex-direction: column; gap: 1px; }
.pay-ditem-name { font-size: 0.85rem; font-weight: 600; color: #111827; }
.pay-ditem-desc { font-size: 0.72rem; color: #6b7280; }
</style>

<script>
function togglePayDropdown(orderId, event) {
    event.stopPropagation();
    const menu = document.getElementById('payMenu_' + orderId);
    const btn = event.currentTarget;
    const arrow = btn.querySelector('.pay-arrow');
    const isOpen = menu.style.display === 'block';
    // Đóng tất cả dropdown khác
    document.querySelectorAll('.pay-drop-menu').forEach(m => m.style.display = 'none');
    document.querySelectorAll('.pay-arrow').forEach(a => a.classList.remove('open'));
    // Toggle cái hiện tại
    if (!isOpen) {
        menu.style.display = 'block';
        arrow.classList.add('open');
    }
}
// Đóng khi click ngoài
document.addEventListener('click', function() {
    document.querySelectorAll('.pay-drop-menu').forEach(m => m.style.display = 'none');
    document.querySelectorAll('.pay-arrow').forEach(a => a.classList.remove('open'));
});
</script>
@endsection
