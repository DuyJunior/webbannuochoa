@extends('layouts.store')

@section('title', 'Kiểm tra đơn hàng · Soopi')

@section('content')
<div class="tracking-page-wrapper">
    <div class="store-container">
        {{-- Breadcrumb --}}
        <nav class="tracking-breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span>/</span>
            <span class="active">Kiểm tra đơn hàng</span>
        </nav>

        <div class="tracking-header-title">
            <span class="badge-tag">HÀNH TRÌNH MÙI HƯƠNG</span>
            <h1>Đơn hàng của bạn đến đâu rồi?</h1>
            <p>Theo dõi hành trình vận chuyển nước hoa chính hãng qua hệ thống Giao Hàng Nhanh (GHN)</p>
        </div>

        {{-- Search Card --}}
        <div class="tracking-search-card">
            <form method="POST" action="{{ route('orders.tracking.search') }}" class="tracking-form-grid">
                @csrf
                <div class="tracking-input-group">
                    <label for="keyword">Mã đơn hàng hoặc Mã vận đơn GHN</label>
                    <div class="input-with-icon">
                        <span class="input-icon">@include('partials.icon', ['name' => 'tag', 'size' => '1em'])</span>
                        <input type="text" id="keyword" name="keyword" value="{{ old('keyword', $keyword ?? '') }}" placeholder="Ví dụ: L8KXW4 hoặc #12">
                    </div>
                </div>

                <div class="tracking-input-group">
                    <label for="phone">Số điện thoại đặt hàng (10 số)</label>
                    <div class="input-with-icon">
                        <span class="input-icon">@include('partials.icon', ['name' => 'phone', 'size' => '1em'])</span>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone', $phone ?? '') }}" placeholder="Ví dụ: 0901234567" maxlength="10" pattern="0[0-9]{9}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                    </div>
                </div>

                <div class="tracking-btn-group">
                    <button type="submit" class="btn-tracking-search">
                        <span>@include('partials.icon', ['name' => 'search', 'size' => '1em']) Tra cứu đơn hàng</span>
                    </button>
                </div>
            </form>

            <div class="tracking-hint">
                @include('partials.icon', ['name' => 'bulb', 'size' => '1em']) <em>Chỉ tra cứu các đơn thuộc tài khoản đang đăng nhập. Nhập số điện thoại hoặc mã đơn / mã GHN của bạn.</em>
            </div>
        </div>

        {{-- Search Results --}}
        @if(isset($searched) && $searched)
            <div class="tracking-results-section">
                <h3 class="results-title">
                    Kết quả tra cứu
                    @if(!empty($keyword) || !empty($phone))
                        <small>cho "{{ trim(($keyword ? 'Mã: ' . $keyword : '') . ($phone ? ' - SĐT: ' . $phone : ''), ' -') }}"</small>
                    @endif
                </h3>

                @if($orders->isEmpty())
                    <div class="empty-search-box">
                        <div class="empty-search-icon">@include('partials.icon', ['name' => 'search', 'size' => '1em'])</div>
                        <h4>Không tìm thấy đơn hàng phù hợp</h4>
                        <p>Vui lòng kiểm tra lại Mã đơn hàng, Mã GHN hoặc Số điện thoại bạn đã dùng khi đặt hàng tại Soopi.</p>
                        <a href="{{ route('orders.tracking') }}" class="btn-retry-search">Thử lại</a>
                    </div>
                @else
                    <div class="orders-result-list">
                        @foreach ($orders as $order)
                            @php
                                $statusLabels = [
                                    'pending' => ['text' => 'Chờ xác nhận', 'class' => 'status-pending'],
                                    'ready_to_pick' => ['text' => 'GHN đã tiếp nhận', 'class' => 'status-ready'],
                                    'delivering' => ['text' => 'Đang giao hàng', 'class' => 'status-shipping'],
                                    'delivered' => ['text' => 'Giao thành công', 'class' => 'status-delivered'],
                                    'cancelled' => ['text' => 'Đã hủy', 'class' => 'status-cancelled'],
                                    'not_shipped' => ['text' => 'Chờ giao', 'class' => 'status-pending'],
                                ];
                                $st = $statusLabels[$order->shipping_status] ?? ['text' => $order->shipping_status, 'class' => 'status-pending'];

                                $statusSteps = [
                                    'pending' => 1,
                                    'ready_to_pick' => 2,
                                    'delivering' => 3,
                                    'delivered' => 4,
                                ];
                                $currentStep = $statusSteps[$order->shipping_status] ?? ($order->shipping_status === 'cancelled' ? -1 : 1);
                            @endphp

                            <div class="order-result-card">
                                <div class="order-result-header">
                                    <div class="order-meta-info">
                                        <span class="order-number">Đơn hàng #{{ $order->id }}</span>
                                        <span class="order-date">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                                        @if($order->ghn_order_code)
                                            <span class="ghn-badge-code">
                                                @include('partials.icon', ['name' => 'truck', 'size' => '1em']) GHN: <strong>{{ $order->ghn_order_code }}</strong>
                                            </span>
                                        @endif
                                    </div>
                                    <div>
                                        <span class="shipping-status-tag {{ $st['class'] }}">{{ $st['text'] }}</span>
                                    </div>
                                </div>

                                {{-- Tracking timeline --}}
                                @if($order->shipping_status !== 'cancelled')
                                    <div class="tracking-mini-timeline">
                                        <div class="timeline-step {{ $currentStep >= 1 ? 'active' : '' }}">
                                            <div class="step-dot">1</div>
                                            <span>Đặt hàng</span>
                                        </div>
                                        <div class="timeline-bar {{ $currentStep >= 2 ? 'active' : '' }}"></div>
                                        <div class="timeline-step {{ $currentStep >= 2 ? 'active' : '' }}">
                                            <div class="step-dot">2</div>
                                            <span>GHN tiếp nhận</span>
                                        </div>
                                        <div class="timeline-bar {{ $currentStep >= 3 ? 'active' : '' }}"></div>
                                        <div class="timeline-step {{ $currentStep >= 3 ? 'active' : '' }}">
                                            <div class="step-dot">3</div>
                                            <span>Đang giao hàng</span>
                                        </div>
                                        <div class="timeline-bar {{ $currentStep >= 4 ? 'active' : '' }}"></div>
                                        <div class="timeline-step {{ $currentStep >= 4 ? 'active' : '' }}">
                                            <div class="step-dot">4</div>
                                            <span>Hoàn thành</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="cancelled-notice">
                                        @include('partials.icon', ['name' => 'warning', 'size' => '1em']) Đơn hàng này đã được hủy trên hệ thống.
                                    </div>
                                @endif

                                <div class="order-result-body">
                                    <div class="order-customer-details">
                                        <div class="detail-line">
                                            <span>@include('partials.icon', ['name' => 'user', 'size' => '1em']) Người nhận:</span>
                                            <strong>{{ $order->name }}</strong>
                                        </div>
                                        <div class="detail-line">
                                            <span>@include('partials.icon', ['name' => 'phone', 'size' => '1em']) Số điện thoại:</span>
                                            <strong>{{ $order->phone }}</strong>
                                        </div>
                                        <div class="detail-line">
                                            <span>@include('partials.icon', ['name' => 'pin', 'size' => '1em']) Địa chỉ nhận:</span>
                                            <span>{{ $order->address }}</span>
                                        </div>
                                    </div>

                                    <div class="order-items-brief">
                                        <h5>Sản phẩm ({{ $order->items->count() }} loại):</h5>
                                        @foreach ($order->items as $item)
                                            @php $prod = $item->product ?? $item->perfume; @endphp
                                            <div class="item-brief-row">
                                                <div class="item-thumb-mini">
                                                    @if($prod && $prod->image_src)
                                                        <img src="{{ $prod->image_src }}" alt="{{ $prod->name }}">
                                                    @else
                                                        <span>@include('partials.brand-mark', ['size' => '1em'])</span>
                                                    @endif
                                                </div>
                                                <div class="item-info-mini">
                                                    <strong>{{ $item->display_name }}</strong>
                                                    @include('partials.order-item-samples')
                                                    <small>x{{ $item->quantity }} · {{ number_format($item->price, 0, ',', '.') }}₫</small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="order-result-footer">
                                    <div class="price-breakdown-mini">
                                        <span>Phí GHN: <strong>{{ number_format($order->ghn_total_fee, 0, ',', '.') }}₫</strong></span>
                                        <span class="sep">|</span>
                                        <span>Tổng thanh toán: <strong class="total-pink">{{ number_format($order->total_price, 0, ',', '.') }} VNĐ</strong></span>
                                    </div>
                                    <div class="action-buttons-group">
                                        @auth
                                            @if($order->user_id === auth()->id())
                                                <a href="{{ route('orders.show', $order->id) }}" class="btn-view-order">Xem chi tiết đơn →</a>
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            {{-- When not searched yet, show logged-in user's recent orders if available --}}
            @if(isset($myRecentOrders) && $myRecentOrders->isNotEmpty())
                <div class="recent-orders-section">
                    <div class="recent-heading">
                        <h3>Đơn Hàng Gần Đây Của Bạn</h3>
                        <a href="{{ route('orders.index') }}" class="link-view-all">Xem tất cả đơn mua →</a>
                    </div>

                    <div class="recent-orders-grid">
                        @foreach ($myRecentOrders as $order)
                            @php
                                $statusLabels = [
                                    'pending' => ['text' => 'Chờ xử lý', 'class' => 'status-pending'],
                                    'ready_to_pick' => ['text' => 'GHN đã nhận', 'class' => 'status-ready'],
                                    'delivering' => ['text' => 'Đang giao', 'class' => 'status-shipping'],
                                    'delivered' => ['text' => 'Thành công', 'class' => 'status-delivered'],
                                    'cancelled' => ['text' => 'Đã hủy', 'class' => 'status-cancelled'],
                                ];
                                $st = $statusLabels[$order->shipping_status] ?? ['text' => $order->shipping_status, 'class' => 'status-pending'];
                            @endphp
                            <div class="recent-card-item">
                                <div class="recent-card-top">
                                    <strong>Đơn #{{ $order->id }}</strong>
                                    <span class="shipping-status-tag {{ $st['class'] }}">{{ $st['text'] }}</span>
                                </div>
                                <div class="recent-card-meta">
                                    <small>{{ $order->created_at->format('d/m/Y H:i') }}</small>
                                    @if($order->ghn_order_code)
                                        <span class="ghn-code-text">Mã GHN: {{ $order->ghn_order_code }}</span>
                                    @endif
                                </div>
                                <div class="recent-card-total">
                                    <span>Tổng tiền:</span>
                                    <strong>{{ number_format($order->total_price, 0, ',', '.') }} VNĐ</strong>
                                </div>
                                <a href="{{ route('orders.show', $order->id) }}" class="btn-check-recent">Theo dõi đơn hàng →</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

<style>
.tracking-page-wrapper {
    padding: 32px 0 80px;
    background: #faf8f9;
    min-height: 80vh;
}
.tracking-breadcrumb {
    display: flex;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 24px;
}
.tracking-breadcrumb a {
    color: #4b5563;
    text-decoration: none;
}
.tracking-breadcrumb a:hover {
    color: #db2777;
}
.tracking-breadcrumb .active {
    color: #db2777;
    font-weight: 600;
}

.tracking-header-title {
    text-align: center;
    max-width: 650px;
    margin: 0 auto 32px;
}
.tracking-header-title .badge-tag {
    display: inline-block;
    background: #fce7f3;
    color: #be185d;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    padding: 4px 12px;
    border-radius: 9999px;
    margin-bottom: 10px;
    text-transform: uppercase;
}
.tracking-header-title h1 {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 2.4rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 8px 0;
}
.tracking-header-title p {
    color: #6b7280;
    font-size: 0.95rem;
    margin: 0;
}

/* Search Card */
.tracking-search-card {
    background: #ffffff;
    border-radius: 20px;
    padding: 32px;
    border: 1px solid #f3e8ee;
    box-shadow: 0 8px 30px rgba(219, 39, 119, 0.06);
    max-width: 820px;
    margin: 0 auto 40px;
}
.tracking-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 16px;
    align-items: flex-end;
}
.tracking-input-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.tracking-input-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #374151;
}
.input-with-icon {
    display: flex;
    align-items: center;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 0 14px;
    background: #ffffff;
    transition: all 0.2s;
}
.input-with-icon:focus-within {
    border-color: #f472b6;
    box-shadow: 0 0 0 3px rgba(244, 114, 182, 0.18);
}
.input-icon {
    font-size: 1.1rem;
    margin-right: 8px;
}
.input-with-icon input {
    border: none;
    outline: none;
    padding: 12px 0;
    font-size: 0.92rem;
    width: 100%;
    color: #1f2937;
}

.btn-tracking-search {
    padding: 13px 24px;
    background: linear-gradient(135deg, #f472b6 0%, #db2777 100%);
    color: #ffffff;
    font-size: 0.92rem;
    font-weight: 700;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(219, 39, 119, 0.28);
    transition: all 0.2s;
    white-space: nowrap;
}
.btn-tracking-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(219, 39, 119, 0.38);
}
.tracking-hint {
    margin-top: 16px;
    font-size: 0.82rem;
    color: #9ca3af;
    text-align: center;
}

/* Results Section */
.tracking-results-section {
    max-width: 860px;
    margin: 0 auto;
}
.results-title {
    font-size: 1.3rem;
    color: #1f2937;
    margin-bottom: 20px;
    font-weight: 600;
}
.results-title small {
    font-size: 0.88rem;
    color: #6b7280;
    font-weight: 400;
}

.empty-search-box {
    background: #ffffff;
    border-radius: 16px;
    padding: 50px 20px;
    text-align: center;
    border: 1px solid #f3e8ee;
}
.empty-search-icon {
    font-size: 3rem;
    margin-bottom: 12px;
}
.empty-search-box h4 {
    font-size: 1.2rem;
    color: #1f2937;
    margin-bottom: 6px;
}
.empty-search-box p {
    color: #6b7280;
    font-size: 0.9rem;
    max-width: 480px;
    margin: 0 auto 20px;
}
.btn-retry-search {
    display: inline-block;
    padding: 10px 24px;
    background: #fdf2f8;
    color: #db2777;
    border: 1px solid #fbcfe8;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
}

/* Order Result Card */
.orders-result-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.order-result-card {
    background: #ffffff;
    border-radius: 18px;
    border: 1px solid #f3e8ee;
    box-shadow: 0 4px 20px rgba(219, 39, 119, 0.04);
    overflow: hidden;
}
.order-result-header {
    padding: 18px 24px;
    background: #fdfafc;
    border-bottom: 1px solid #f5ecf0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.order-meta-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}
.order-number {
    font-weight: 700;
    color: #1f2937;
    font-size: 1rem;
}
.order-date {
    color: #9ca3af;
    font-size: 0.82rem;
}
.ghn-badge-code {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    font-size: 0.8rem;
    padding: 3px 10px;
    border-radius: 9999px;
}

.shipping-status-tag {
    font-size: 0.8rem;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 9999px;
}
.status-pending { background: #fef3c7; color: #b45309; }
.status-ready { background: #e0e7ff; color: #4338ca; }
.status-shipping { background: #dbeafe; color: #1d4ed8; }
.status-delivered { background: #dcfce7; color: #15803d; }
.status-cancelled { background: #fee2e2; color: #b91c1c; }

/* Timeline */
.tracking-mini-timeline {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px 32px;
    background: #ffffff;
    border-bottom: 1px solid #fdf2f8;
}
.timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    color: #9ca3af;
}
.timeline-step.active {
    color: #db2777;
    font-weight: 700;
}
.step-dot {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #9ca3af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 700;
}
.timeline-step.active .step-dot {
    background: #db2777;
    color: #ffffff;
    box-shadow: 0 0 0 4px #fce7f3;
}
.timeline-bar {
    flex: 1;
    height: 3px;
    background: #e5e7eb;
    margin: 0 8px -18px;
}
.timeline-bar.active {
    background: #db2777;
}
.cancelled-notice {
    padding: 16px 24px;
    background: #fef2f2;
    color: #b91c1c;
    font-size: 0.88rem;
    border-bottom: 1px solid #fee2e2;
}

.order-result-body {
    padding: 20px 24px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}
.order-customer-details {
    display: flex;
    flex-direction: column;
    gap: 8px;
    font-size: 0.88rem;
}
.detail-line {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.detail-line span {
    font-size: 0.76rem;
    color: #9ca3af;
    text-transform: uppercase;
    font-weight: 600;
}
.detail-line strong, .detail-line span:last-child {
    color: #1f2937;
}

.order-items-brief h5 {
    font-size: 0.88rem;
    color: #374151;
    margin: 0 0 10px 0;
}
.item-brief-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.item-thumb-mini {
    width: 38px;
    height: 38px;
    border-radius: 6px;
    border: 1px solid #f3e8ee;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
}
.item-thumb-mini img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.item-info-mini {
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.item-info-mini strong {
    font-size: 0.85rem;
    color: #1f2937;
}
.item-info-mini small {
    font-size: 0.76rem;
    color: #6b7280;
}

.order-result-footer {
    padding: 16px 24px;
    background: #ffffff;
    border-top: 1px solid #f5ecf0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}
.price-breakdown-mini {
    font-size: 0.88rem;
    color: #4b5563;
    display: flex;
    align-items: center;
    gap: 8px;
}
.price-breakdown-mini .sep {
    color: #e5e7eb;
}
.total-pink {
    color: #db2777;
    font-size: 1.15rem;
}
.btn-view-order {
    padding: 8px 16px;
    background: #fdf2f8;
    color: #db2777;
    border: 1px solid #fbcfe8;
    border-radius: 8px;
    font-size: 0.83rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-view-order:hover {
    background: #fce7f3;
}

/* Recent Orders Section */
.recent-orders-section {
    max-width: 860px;
    margin: 40px auto 0;
}
.recent-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}
.recent-heading h3 {
    font-size: 1.25rem;
    color: #1f2937;
    margin: 0;
}
.link-view-all {
    color: #db2777;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
}
.recent-orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
}
.recent-card-item {
    background: #ffffff;
    border-radius: 14px;
    padding: 18px;
    border: 1px solid #f3e8ee;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.recent-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.recent-card-meta {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.recent-card-meta small {
    color: #9ca3af;
    font-size: 0.78rem;
}
.ghn-code-text {
    font-size: 0.78rem;
    color: #2563eb;
    font-weight: 600;
}
.recent-card-total {
    display: flex;
    justify-content: space-between;
    font-size: 0.85rem;
    color: #4b5563;
    padding-top: 6px;
    border-top: 1px dashed #f3f4f6;
}
.recent-card-total strong {
    color: #db2777;
}
.btn-check-recent {
    margin-top: 4px;
    display: block;
    text-align: center;
    padding: 8px;
    background: #fdf2f8;
    color: #db2777;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-check-recent:hover {
    background: #fce7f3;
}

@media (max-width: 768px) {
    .tracking-form-grid {
        grid-template-columns: 1fr;
    }
    .order-result-body {
        grid-template-columns: 1fr;
    }
    .tracking-mini-timeline {
        padding: 16px;
    }
}
</style>
@endsection
