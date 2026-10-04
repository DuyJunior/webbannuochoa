@extends('layouts.store')

@section('title', __('Chi tiết đơn hàng #') . $order->id . ' · Soopi')

@section('content')
<div class="order-detail-page">
    <div class="store-container">
        {{-- Breadcrumb --}}
        <nav class="detail-breadcrumb">
            <a href="{{ route('home') }}">{{ __('Trang chủ') }}</a>
            <span>/</span>
            <a href="{{ route('orders.index') }}">{{ __('Đơn hàng của tôi') }}</a>
            <span>/</span>
            <span class="active">{{ __('Chi tiết đơn hàng #') }}{{ $order->id }}</span>
        </nav>

        <div class="order-detail-header">
            <div>
                <span class="badge-tag">{{ __('Đơn hàng #') }}{{ $order->id }}</span>
                <h1>{{ __('Chi Tiết Đơn Hàng') }}</h1>
                <p>{{ __('Ngày tạo:') }} {{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div class="header-action-group">
                <a href="{{ route('orders.index') }}" class="btn-back-history">{{ __('← Lịch sử đơn hàng') }}</a>
                @if(in_array($order->shipping_status, ['pending', 'ready_to_pick']))
                    <form method="POST" action="{{ route('orders.cancel', $order->id) }}" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này? Hệ thống sẽ tự động đồng bộ yêu cầu hủy sang Giao Hàng Nhanh (GHN).');">
                        @csrf
                        <button type="submit" class="btn-cancel-order">{{ __('Hủy đơn hàng này') }}</button>
                    </form>
                @endif
            </div>
        </div>

        @include('partials.order-timeline')

        {{-- Trạng thái vận chuyển GHN Timeline --}}
        @php
            $statusSteps = [
                'pending' => 1,
                'ready_to_pick' => 2,
                'delivering' => 3,
                'delivered' => 4,
            ];
            $currentStep = $statusSteps[$order->shipping_status] ?? ($order->shipping_status === 'cancelled' ? -1 : 1);
        @endphp

        <div class="shipping-tracking-banner">
            <div class="tracking-top-bar">
                <div class="tracking-partner">
                    <span class="truck-icon">@include('partials.icon', ['name' => 'truck', 'size' => '1em'])</span>
                    <div>
                        <strong>{{ __('Vận chuyển bởi Giao Hàng Nhanh (GHN)') }}</strong>
                        @if($order->ghn_order_code)
                            <p class="tracking-code">{{ __('Mã vận đơn GHN:') }} <span class="code-bold">{{ $order->ghn_order_code }}</span></p>
                        @else
                            <p class="tracking-code">{{ __('Chưa có mã vận đơn GHN') }}</p>
                        @endif
                    </div>
                </div>
                <div class="tracking-status-pill">
                    @if($order->shipping_status === 'cancelled')
                        <span class="pill-cancelled">{{ __('Đơn hàng đã hủy') }}</span>
                    @elseif($order->shipping_status === 'delivered')
                        <span class="pill-delivered">{{ __('Giao hàng thành công') }}</span>
                    @elseif($order->shipping_status === 'delivering')
                        <span class="pill-delivering">{{ __('Đang giao hàng') }}</span>
                    @elseif($order->shipping_status === 'ready_to_pick')
                        <span class="pill-ready">{{ __('GHN đã tiếp nhận (Chờ lấy hàng)') }}</span>
                    @else
                        <span class="pill-pending">{{ __('Chờ xác nhận') }}</span>
                    @endif
                </div>
            </div>

            @if($order->shipping_status !== 'cancelled')
                <div class="tracking-timeline">
                    <div class="timeline-step {{ $currentStep >= 1 ? 'active' : '' }}">
                        <div class="step-circle">1</div>
                        <span>{{ __('Đặt hàng') }}</span>
                    </div>
                    <div class="timeline-line {{ $currentStep >= 2 ? 'active' : '' }}"></div>
                    <div class="timeline-step {{ $currentStep >= 2 ? 'active' : '' }}">
                        <div class="step-circle">2</div>
                        <span>{{ __('GHN tiếp nhận') }}</span>
                    </div>
                    <div class="timeline-line {{ $currentStep >= 3 ? 'active' : '' }}"></div>
                    <div class="timeline-step {{ $currentStep >= 3 ? 'active' : '' }}">
                        <div class="step-circle">3</div>
                        <span>{{ __('Đang giao hàng') }}</span>
                    </div>
                    <div class="timeline-line {{ $currentStep >= 4 ? 'active' : '' }}"></div>
                    <div class="timeline-step {{ $currentStep >= 4 ? 'active' : '' }}">
                        <div class="step-circle">4</div>
                        <span>{{ __('Thành công') }}</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="detail-grid-layout">
            {{-- Cột trái: Danh sách sản phẩm --}}
            <div class="detail-col-items">
                <div class="detail-card">
                    <h3 class="detail-card-title">{{ __('Sản phẩm trong đơn hàng') }}</h3>
                    <div class="detail-items-table">
                        @foreach ($order->items as $item)
                            @php $prod = $item->product ?? $item->perfume; @endphp
                            <div class="detail-item-row">
                                <div class="detail-item-thumb">
                                    @if($prod && $prod->image_src)
                                        <img src="{{ $prod->image_src }}" alt="{{ $prod->name }}">
                                    @else
                                        <span>@include('partials.brand-mark', ['size' => '1em'])</span>
                                    @endif
                                </div>
                                <div class="detail-item-info">
                                    <h4>{{ $item->display_name }}</h4>
                                    <div class="detail-item-meta">
                                        <span>{{ __('Dung tích:') }} {{ $item->volume_label }}</span>
                                        @if($item->addon_gift)
                                            <span class="badge-gift">@include('partials.icon', ['name' => 'gift', 'size' => '1em']) {{ __('Hộp quà & Nơ') }}</span>
                                        @endif
                                        @if($item->engrave_text)
                                            <span class="badge-engrave">@include('partials.icon', ['name' => 'pen', 'size' => '1em']) {{ __('Khắc tên: "') }}{{ $item->engrave_text }}"</span>
                                        @endif
                                    </div>
                                    @include('partials.order-item-samples')
                                    <div class="detail-item-unitprice">
                                        {{ __('Đơn giá:') }} {{ number_format($item->price, 0, ',', '.') }}{{ __('₫ ×') }} {{ $item->quantity }}
                                    </div>
                                </div>
                                <div class="detail-item-total">
                                    {{ number_format($item->price * $item->quantity, 0, ',', '.') }} {{ __('VNĐ') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Cột phải: Thông tin nhận hàng & Thanh toán --}}
            <div class="detail-col-side">
                <div class="detail-card">
                    <h3 class="detail-card-title">{{ __('Thông tin giao nhận') }}</h3>
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-label">{{ __('Người nhận:') }}</span>
                            <span class="info-value"><strong>{{ $order->name }}</strong></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('Số điện thoại:') }}</span>
                            <span class="info-value">{{ $order->phone }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">{{ __('Địa chỉ giao:') }}</span>
                            <span class="info-value">{{ $order->address }}</span>
                        </div>
                        @if($order->note)
                            <div class="info-row">
                                <span class="info-label">{{ __('Ghi chú đơn hàng:') }}</span>
                                <span class="info-value" style="white-space:pre-line;overflow-wrap:anywhere">{{ $order->note }}</span>
                            </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">{{ __('Hình thức:') }}</span>
                            <span class="info-value">
                                @include('user.payment._method', ['order' => $order])
                            </span>
                        </div>
                </div>

                @if($order->gift_wrap || $order->gift_card || $order->gift_message || $order->gift_delivery_date)
                    <div class="detail-card" style="margin-top: 20px; border: 1px solid rgba(225, 29, 72, 0.25); background: linear-gradient(180deg, #fff5f7 0%, #ffffff 100%);">
                        <h3 class="detail-card-title" style="color: #be123c; display:flex; align-items:center; gap:8px;">
                            <span>@include('partials.icon', ['name' => 'gift', 'size' => '1em'])</span> {{ __('Dịch vụ quà tặng cao cấp') }}
                        </h3>
                        <div class="info-list">
                            @if($order->gift_wrap)
                                <div class="info-row">
                                    <span class="info-label">{{ __('Gói quà:') }}</span>
                                    <span class="info-value"><strong>{{ $order->gift_wrap }}</strong></span>
                                </div>
                            @endif
                            @if($order->gift_card)
                                <div class="info-row">
                                    <span class="info-label">{{ __('Thiệp tặng:') }}</span>
                                    <span class="info-value"><strong>{{ $order->gift_card }}</strong></span>
                                </div>
                            @endif
                            @if($order->gift_delivery_date)
                                <div class="info-row">
                                    <span class="info-label">{{ __('Ngày giao quà:') }}</span>
                                    <span class="info-value"><strong style="color:#e11d48;">{{ \Carbon\Carbon::parse($order->gift_delivery_date)->format('d/m/Y') }}</strong></span>
                                </div>
                            @endif
                            @if($order->gift_message)
                                <div class="info-row" style="flex-direction: column; align-items: flex-start; gap: 4px; margin-top: 6px;">
                                    <span class="info-label">{{ __('Lời chúc gửi kèm:') }}</span>
                                    <div style="background:#fff; border:1px dashed #f43f5e; padding:10px 14px; border-radius:8px; font-style:italic; color:#881337; width:100%; box-sizing:border-box;">
                                        “{{ $order->gift_message }}”
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="detail-card" style="margin-top: 20px;">
                    <h3 class="detail-card-title">{{ __('Tổng kết chi phí') }}</h3>
                    <div class="cost-summary-list">
                        @php
                            $subtotal = $order->items->sum(fn($i) => $i->price * $i->quantity);
                        @endphp
                        <div class="cost-item">
                            <span>{{ __('Tiền hàng') }}</span>
                            <strong>{{ number_format($subtotal, 0, ',', '.') }} {{ __('VNĐ') }}</strong>
                        </div>
                        <div class="cost-item">
                            <span>{{ __('Cước vận chuyển (GHN)') }}</span>
                            <strong>{{ number_format($order->ghn_total_fee, 0, ',', '.') }} {{ __('VNĐ') }}</strong>
                        </div>
                        <div class="cost-item grand-cost">
                            <span>{{ __('Tổng cộng') }}</span>
                            <strong class="grand-price">{{ number_format($order->total_price, 0, ',', '.') }} {{ __('VNĐ') }}</strong>
                        </div>
                    </div>

                    @if($order->status === 'pending' && $order->paymentTransactions->contains('gateway', 'sepay') && ($order->is_demo ? \App\Support\DemoMode::enabled() : app(\App\Services\SePayService::class)->ready()) && app(\App\Services\SePayService::class)->canPay($order))
                        <div style="margin-top: 18px;">
                            <a href="{{ route('user.orders.sepay.pay', $order->id) }}" style="display:flex; align-items:center; justify-content:center; gap:8px; background:linear-gradient(135deg, #987145 0%, #6c4b2b 100%); color:#fff; font-weight:700; font-size:0.95rem; padding:12px 18px; border-radius:10px; text-decoration:none; box-shadow:0 4px 14px rgba(165,0,100,0.35);">
                                @include('partials.icon', ['name' => 'bank', 'size' => '1em'])
                                <span>{{ __('Thanh toán qua SePay') }}</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.order-detail-page {
    padding: 32px 0 80px;
    background: #faf8f9;
    min-height: 80vh;
}
.detail-breadcrumb {
    display: flex;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 24px;
}
.detail-breadcrumb a {
    color: #4b5563;
    text-decoration: none;
}
.detail-breadcrumb a:hover {
    color: #db2777;
}
.detail-breadcrumb .active {
    color: #db2777;
    font-weight: 600;
}

.order-detail-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}
.order-detail-header .badge-tag {
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
.order-detail-header h1 {
    font-family: 'Playfair Display', 'Soopi Serif', Georgia, serif;
    font-size: 2.2rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 4px 0;
}
.order-detail-header p {
    color: #6b7280;
    font-size: 0.9rem;
    margin: 0;
}
.header-action-group {
    display: flex;
    gap: 12px;
    align-items: center;
}
.btn-back-history {
    padding: 10px 18px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    color: #374151;
    font-size: 0.85rem;
    font-weight: 600;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-back-history:hover {
    background: #f3f4f6;
}
.btn-cancel-order {
    padding: 10px 18px;
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #dc2626;
    font-size: 0.85rem;
    font-weight: 600;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-cancel-order:hover {
    background: #fca5a5;
    color: #991b1b;
}

/* Shipping tracking banner */
.shipping-tracking-banner {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f3e8ee;
    padding: 24px;
    margin-bottom: 28px;
    box-shadow: 0 4px 20px rgba(219, 39, 119, 0.03);
}
.tracking-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 24px;
}
.tracking-partner {
    display: flex;
    align-items: center;
    gap: 12px;
}
.truck-icon {
    font-size: 2rem;
}
.tracking-partner strong {
    font-size: 1rem;
    color: #111827;
}
.tracking-code {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 2px 0 0 0;
}
.code-bold {
    color: #2563eb;
    font-weight: 700;
}

.tracking-status-pill span {
    font-size: 0.85rem;
    font-weight: 700;
    padding: 6px 14px;
    border-radius: 9999px;
}
.pill-pending { background: #fef3c7; color: #b45309; }
.pill-ready { background: #e0e7ff; color: #4338ca; }
.pill-delivering { background: #dbeafe; color: #1d4ed8; }
.pill-delivered { background: #dcfce7; color: #15803d; }
.pill-cancelled { background: #fee2e2; color: #b91c1c; }

/* Timeline */
.tracking-timeline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 20px 0;
}
.timeline-step {
    flex: 1 1 0;
    min-width: 0;
    text-align: center;
    overflow-wrap: anywhere;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    color: #9ca3af;
    font-weight: 500;
}
.timeline-step.active {
    color: #db2777;
    font-weight: 700;
}
.step-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 700;
}
.timeline-step.active .step-circle {
    background: #db2777;
    color: #ffffff;
    box-shadow: 0 0 0 4px #fce7f3;
}
.timeline-line {
    flex: 0 1 24px;
    height: 3px;
    background: #e5e7eb;
    margin: 0 10px -20px;
}
.timeline-line.active {
    background: #db2777;
}

/* Detail Grid Layout */
.detail-grid-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 28px;
    align-items: flex-start;
}
.detail-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    border: 1px solid #f3e8ee;
    box-shadow: 0 4px 20px rgba(219, 39, 119, 0.03);
}
.detail-card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 18px 0;
    padding-bottom: 12px;
    border-bottom: 1px solid #f3f4f6;
}

.detail-items-table {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.detail-item-row {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-bottom: 16px;
    border-bottom: 1px dashed #f3f4f6;
}
.detail-item-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.detail-item-thumb {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    border: 1px solid #f3e8ee;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.detail-item-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.detail-item-info {
    flex: 1;
    min-width: 0;
}
.detail-item-info h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}
.detail-item-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    font-size: 0.78rem;
    color: #6b7280;
    margin-bottom: 4px;
}
.badge-gift {
    background: #fdf2f8;
    color: #be185d;
    padding: 2px 6px;
    border-radius: 4px;
}
.badge-engrave {
    background: #eff6ff;
    color: #1d4ed8;
    padding: 2px 6px;
    border-radius: 4px;
}
.detail-item-unitprice {
    font-size: 0.8rem;
    color: #9ca3af;
}
.detail-item-total {
    font-size: 0.95rem;
    font-weight: 700;
    color: #111827;
}

.info-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.info-row {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: 0.88rem;
}
.info-label {
    color: #9ca3af;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    font-weight: 600;
}
.info-value {
    color: #1f2937;
    line-height: 1.4;
}

.cost-summary-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.cost-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    color: #4b5563;
}
.cost-item strong {
    color: #111827;
}
.grand-cost {
    margin-top: 6px;
    padding-top: 10px;
    border-top: 1px dashed #e5e7eb;
    font-size: 1.05rem;
    font-weight: 700;
}
.grand-cost .grand-price {
    color: #db2777;
    font-size: 1.25rem;
}

@media (max-width: 860px) {
    .detail-grid-layout {
        grid-template-columns: 1fr;
    }
    .order-detail-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .tracking-timeline {
        padding: 10px 0 0;
    }
}
@media (max-width: 600px) {
    .detail-item-row {
        display: grid;
        grid-template-columns: 56px minmax(0, 1fr);
        align-items: start;
        gap: 12px;
    }
    .detail-item-thumb {
        width: 56px;
        height: 56px;
    }
    .detail-item-info {
        overflow-wrap: anywhere;
    }
    .detail-item-total {
        grid-column: 1 / -1;
        padding-top: 10px;
        border-top: 1px solid #f3e8ee;
        text-align: right;
        white-space: nowrap;
    }
}
</style>
@endsection
