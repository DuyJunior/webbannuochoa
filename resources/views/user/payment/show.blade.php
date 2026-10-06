@extends('layouts.store')
@section('title', __('Chi tiết đơn hàng #') . $order->id . ' · Soopi')
@section('robots', 'noindex, nofollow')

@section('content')
@php
    $cancelled = $order->status === 'cancelled' || $order->shipping_status === 'cancelled';
    $shipping = $cancelled ? 'cancelled' : ($order->shipping_status ?: 'pending');
    $shippingLabels = [
        'pending' => __('Chờ xác nhận'), 'not_shipped' => __('Chưa giao'), 'processing' => __('Đang xử lý'),
        'ready_to_pick' => __('Chờ lấy hàng'), 'picking' => __('Đang lấy hàng'), 'picked' => __('Đã lấy hàng'),
        'storing' => __('Đang lưu kho'), 'transporting' => __('Đang trung chuyển'), 'sorting' => __('Đang phân loại'),
        'delivering' => __('Đang giao hàng'), 'delivered' => __('Giao hàng thành công'), 'cancelled' => __('Đơn hàng đã hủy'),
        'delivery_fail' => __('Giao hàng chưa thành công'), 'waiting_to_return' => __('Chờ hoàn hàng'),
        'return' => __('Chờ hoàn hàng'), 'returning' => __('Đang hoàn hàng'), 'returned' => __('Đã hoàn hàng'),
        'return_transporting' => __('Đang chuyển hoàn'), 'return_sorting' => __('Đang phân loại hoàn'),
        'return_fail' => __('Hoàn hàng chưa thành công'), 'exception' => __('Vận chuyển cần kiểm tra'),
        'lost' => __('Vận chuyển cần kiểm tra'), 'damage' => __('Vận chuyển cần kiểm tra'),
    ];
    $stepMap = ['pending'=>1, 'not_shipped'=>1, 'processing'=>1, 'ready_to_pick'=>2, 'picking'=>2,
        'picked'=>2, 'storing'=>2, 'transporting'=>2, 'sorting'=>2, 'delivering'=>3, 'delivered'=>4];
    $currentStep = $stepMap[$shipping] ?? null;
    $statusLabel = $shippingLabels[$shipping] ?? __('Đang cập nhật vận chuyển');
    $statusDescription = match (true) {
        $cancelled => __('Đơn hàng đã dừng xử lý. Bạn có thể xem lại sản phẩm và thông tin thanh toán bên dưới.'),
        $shipping === 'delivered' => __('Cảm ơn bạn đã chọn Soopi. Hãy chia sẻ cảm nhận về mùi hương bạn vừa nhận.'),
        $shipping === 'delivering' => __('Đơn hàng đang trên đường đến bạn. Vui lòng giữ liên lạc để nhận hàng.'),
        $currentStep === 2 => __('Đơn hàng đang trong quá trình lấy hàng và vận chuyển. Các cập nhật sẽ hiển thị tại đây.'),
        $currentStep === 1 => __('Soopi đã ghi nhận đơn hàng. Theo dõi việc chuẩn bị và giao hàng ngay tại đây.'),
        default => __('Vui lòng xem lịch sử xử lý bên dưới hoặc liên hệ Soopi để được hỗ trợ.'),
    };
    $subtotal = $order->items->sum(fn($item) => $item->price * $item->quantity);
@endphp
<div class="order-detail-page order-folio">
    <div class="store-container">
        <nav class="of-breadcrumb" aria-label="{{ __('Điều hướng') }}">
            <a href="{{ route('orders.index') }}">← {{ __('Đơn hàng của tôi') }}</a>
            <span aria-hidden="true">/</span><span>{{ __('Đơn hàng #') }}{{ $order->id }}</span>
        </nav>
        <header class="of-header">
            <div>
                <p class="of-eyebrow">SOOPI / {{ __('HÀNH TRÌNH MÙI HƯƠNG') }}</p>
                <h1>{{ __('Chi tiết đơn hàng') }} <span>#{{ $order->id }}</span></h1>
                <p class="of-created">{{ __('Ngày tạo:') }} <time datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->format('d/m/Y · H:i') }}</time></p>
            </div>
            <a class="of-button" href="#order-products">{{ __('Xem sản phẩm') }} ↓</a>
        </header>

        <section class="of-journey" aria-labelledby="order-journey-title">
            <div class="of-journey-heading">
                <div class="of-seal" aria-hidden="true">@include('partials.brand-mark', ['size' => '34px', 'light' => true])</div>
                <div class="of-journey-copy">
                    <p class="of-eyebrow">{{ __('TRẠNG THÁI ĐƠN HÀNG') }}</p>
                    <h2 id="order-journey-title">{{ $statusLabel }}</h2>
                    <p>{{ $statusDescription }}</p>
                </div>
                <div class="of-carrier">
                    <span>@include('partials.icon', ['name'=>'truck', 'size'=>19]) Giao Hàng Nhanh</span>
                    @if($order->ghn_order_code)
                        <small>{{ __('Mã vận đơn GHN:') }}</small>
                        <strong>{{ $order->ghn_order_code }}</strong>
                    @else
                        <small>{{ __('Chưa có mã vận đơn GHN') }}</small>
                    @endif
                </div>
            </div>
            @if($currentStep)
                <ol class="of-progress" aria-label="{{ __('Tiến trình giao hàng') }}">
                    @foreach([__('Đặt hàng'), __('Lấy hàng & vận chuyển'), __('Đang giao hàng'), __('Thành công')] as $stepLabel)
                        <li class="{{ $loop->iteration <= $currentStep ? 'is-reached' : '' }} {{ $loop->iteration === $currentStep ? 'is-current' : '' }}" @if($loop->iteration === $currentStep) aria-current="step" @endif>
                            <span class="of-step-number" aria-hidden="true">@if($loop->iteration < $currentStep) ✓ @else {{ sprintf('%02d', $loop->iteration) }} @endif</span>
                            <span>{{ $stepLabel }}</span>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <div class="of-layout">
            <div class="of-main">
                <section class="of-card of-products" id="order-products" aria-labelledby="order-products-title">
                    <div class="of-section-heading"><div><p class="of-eyebrow">01 / {{ __('TUYỂN CHỌN CỦA BẠN') }}</p><h2 id="order-products-title">{{ __('Sản phẩm trong đơn hàng') }}</h2></div><span class="of-count">{{ $order->items->sum('quantity') }} {{ __('sản phẩm') }}</span></div>
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
                                    @include('partials.order-item-gift')
                                    <div class="detail-item-unitprice">
                                        {{ __('Đơn giá:') }} {{ number_format($item->price, 0, ',', '.') }}{{ __('₫ ×') }} {{ $item->quantity }}
                                    </div>
                                </div>
                                <div class="detail-item-total">
                                    {{ number_format($item->price * $item->quantity, 0, ',', '.') }} {{ __('VNĐ') }}
                                </div>
                                @include('partials.order-item-review')
                            </div>
                        @endforeach
                    </div>
                </section>
                <section class="of-card" aria-labelledby="order-delivery-title">
                    <div class="of-section-heading"><div><p class="of-eyebrow">02 / {{ __('ĐIỂM ĐẾN') }}</p><h2 id="order-delivery-title">{{ __('Thông tin giao nhận') }}</h2></div>@include('partials.icon', ['name'=>'truck', 'size'=>24])</div>
                    <div class="of-delivery-grid">
                        <div><span class="of-label">{{ __('Người nhận:') }}</span><strong>{{ $order->name }}</strong><p>{{ $order->phone }}</p></div>
                        <div><span class="of-label">{{ __('Địa chỉ giao:') }}</span><p class="of-preserve">{{ $order->address }}</p></div>
                    </div>
                    @if($order->note)<div class="of-note"><span class="of-label">{{ __('Ghi chú đơn hàng:') }}</span><p class="of-preserve">{{ $order->note }}</p></div>@endif
                </section>
                @if($order->gift_wrap || $order->gift_card || $order->gift_message || $order->gift_delivery_date)
                    <section class="of-card of-gift" aria-labelledby="order-gift-title">
                        <div class="of-section-heading"><h2 id="order-gift-title">{{ __('Dịch vụ quà tặng cao cấp') }}</h2>@include('partials.icon', ['name'=>'gift', 'size'=>24])</div>
                        <dl class="of-gift-details">
                            @if($order->gift_wrap)<div><dt>{{ __('Gói quà:') }}</dt><dd>{{ $order->gift_wrap }}</dd></div>@endif
                            @if($order->gift_card)<div><dt>{{ __('Thiệp tặng:') }}</dt><dd>{{ $order->gift_card }}</dd></div>@endif
                            @if($order->gift_delivery_date)<div><dt>{{ __('Ngày giao quà:') }}</dt><dd>{{ \Carbon\Carbon::parse($order->gift_delivery_date)->format('d/m/Y') }}</dd></div>@endif
                        </dl>
                        @if($order->gift_message)<div class="of-gift-message"><span class="of-label">{{ __('Lời chúc gửi kèm:') }}</span><blockquote>“{{ $order->gift_message }}”</blockquote></div>@endif
                    </section>
                @endif
                <details class="of-history of-card">
                    <summary><span>@include('partials.icon', ['name'=>'clock', 'size'=>20]) {{ __('Lịch sử xử lý đơn hàng') }}</span><span class="of-expand" aria-hidden="true">+</span></summary>
                    @include('partials.order-timeline')
                </details>
            </div>
            <aside class="of-sidebar" aria-label="{{ __('Tổng kết chi phí') }}">
                <section class="of-receipt">
                    <div class="of-receipt-top"><p class="of-eyebrow">SOOPI / {{ __('CHI TIẾT THANH TOÁN') }}</p><h2>{{ __('Tổng kết chi phí') }}</h2><span class="of-receipt-id">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span></div>
                    <div class="of-receipt-body">
                        <dl class="of-costs">
                            <div><dt>{{ __('Tiền hàng') }}</dt><dd>{{ number_format($subtotal, 0, ',', '.') }} ₫</dd></div>
                            <div><dt>{{ __('Cước vận chuyển (GHN)') }}</dt><dd>{{ number_format($order->ghn_total_fee, 0, ',', '.') }} ₫</dd></div>
                            @if($order->discount_amount > 0)<div class="of-discount"><dt>{{ __('Giảm giá') }} @if($order->coupon_code)<small>{{ $order->coupon_code }}</small>@endif</dt><dd>−{{ number_format($order->discount_amount, 0, ',', '.') }} ₫</dd></div>@endif
                            @if($order->points_used > 0)<div class="of-discount"><dt>{{ __('Điểm thành viên') }} <small>{{ $order->points_used }} {{ __('điểm') }}</small></dt><dd>−{{ number_format($order->points_used * 1000, 0, ',', '.') }} ₫</dd></div>@endif
                            <div class="of-total"><dt>{{ __('Tổng cộng') }}</dt><dd>{{ number_format($order->total_price, 0, ',', '.') }} <small>VNĐ</small></dd></div>
                        </dl>
                        <div class="of-payment"><span class="of-label">{{ __('Hình thức:') }}</span>@include('user.payment._method', ['order'=>$order])</div>
                        @if($order->status === 'pending' && $order->payment_expires_at)<p class="of-deadline">{{ __('Hạn thanh toán:') }} <strong>{{ $order->payment_expires_at->format('d/m/Y H:i') }}</strong></p>@endif
                    @if($order->status === 'pending' && $order->paymentTransactions->contains('gateway', 'sepay') && ($order->is_demo ? \App\Support\DemoMode::enabled() : app(\App\Services\SePayService::class)->ready()) && app(\App\Services\SePayService::class)->canPay($order))
                        <div class="of-payment-action">
                            <a href="{{ route('user.orders.sepay.pay', $order->id) }}" class="of-button of-button-primary">
                                @include('partials.icon', ['name' => 'bank', 'size' => '1em'])
                                <span>{{ __('Thanh toán qua SePay') }}</span>
                            </a>
                        </div>
                    @endif
                    </div>
                </section>
                <div class="of-support"><p>{{ __('Cần hỗ trợ đơn hàng?') }}</p><a class="of-button" href="{{ route('store.contact') }}">{{ __('Liên hệ Soopi') }} ↗</a></div>
                @if(!$cancelled && $order->user_id === auth()->id() && in_array($order->shipping_status, ['pending', 'ready_to_pick']))
                    <form class="of-cancel" method="POST" action="{{ route('orders.cancel', $order->id) }}" data-order-cancel data-confirm="{{ __('Bạn có chắc chắn muốn hủy đơn hàng này? Hệ thống sẽ tự động đồng bộ yêu cầu hủy sang Giao Hàng Nhanh (GHN).') }}">
                        @csrf<button type="submit">{{ __('Hủy đơn hàng này') }}</button>
                    </form>
                @endif
            </aside>
        </div>
        <p class="of-signature">{{ __('Một mùi hương. Một câu chuyện của riêng bạn.') }} <span>— Soopi</span></p>
    </div>
</div>
@endsection
