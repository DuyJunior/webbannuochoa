@extends('layouts.store')
@section('title', __('Lịch sử đơn hàng · Soopi'))
@section('robots', 'noindex, nofollow')
@section('content')
<div class="order-archive">
    <div class="store-container">
        <nav class="oa-breadcrumb" aria-label="{{ __('Điều hướng') }}">
            <a href="{{ route('home') }}">{{ __('Trang chủ') }}</a><span>/</span><span>{{ __('Đơn hàng của tôi') }}</span>
        </nav>
        <header class="oa-hero">
            <div class="oa-hero-copy">
                <span class="oa-eyebrow">SOOPI / {{ __('KHÔNG GIAN CỦA BẠN') }}</span>
                <h1>{{ __('Những mùi hương') }}<br><em>{{ __('bạn đã chọn.') }}</em></h1>
                <p>{{ __('Mỗi lựa chọn, một dấu ấn riêng. Theo dõi những mùi hương đang trên hành trình đến với bạn.') }}</p>
                <a class="oa-text-link" href="{{ route('home') }}#san-pham">{{ __('Khám phá bộ sưu tập') }} @include('partials.icon', ['name'=>'arrow','size'=>18])</a>
            </div>
            <div class="oa-hero-art" aria-hidden="true">
                <img src="{{ asset('images/journal/detail.webp') }}" alt="" width="600" height="400">
                <div class="oa-art-caption"><span>SOOPI PERFUME STUDIO</span><span>{{ __('Hương thơm của riêng bạn.') }}</span></div>
            </div>
        </header>
        <div class="oa-section-heading">
            <div><span class="oa-eyebrow">01 / {{ __('LỊCH SỬ ĐƠN HÀNG') }}</span><h2>{{ __('Tuyển chọn của bạn') }}</h2></div>
            <div class="oa-count"><strong>{{ str_pad($orders->total(), 2, '0', STR_PAD_LEFT) }}</strong><span>{{ __('đơn hàng') }}</span></div>
        </div>
        <div class="oa-list">
            @forelse($orders as $order)
                @php $journey = \App\Support\OrderJourney::for($order); @endphp
                <article class="ol-card oa-order">
                    <div class="oa-order-main">
                        <header class="oa-order-heading">
                            <div class="oa-order-id"><span class="oa-eyebrow">{{ __('ĐƠN HÀNG') }}</span><h3>#DH{{ str_pad($order->id,5,'0',STR_PAD_LEFT) }}</h3><time datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->format('d.m.Y') }} <span>·</span> {{ $order->created_at->format('H:i') }}</time></div>
                            <span class="oa-status is-{{ $journey['tone'] }}"><i aria-hidden="true"></i>{{ $journey['label'] }}</span>
                        </header>
                        <div class="oa-products">
                            @foreach($order->items as $item)
                                @php $prod = $item->product ?? $item->perfume; @endphp
                                <div class="oa-product">
                                    <div class="oa-product-image">
                                        @if($prod && $prod->image_src)<img src="{{ $prod->image_src }}" alt="{{ $item->display_name }}" loading="lazy">@else@include('partials.brand-mark',['size'=>'40px'])@endif
                                    </div>
                                    <div class="oa-product-copy">
                                        @if($prod?->brand)<span class="oa-eyebrow">{{ $prod->brand }}</span>@endif
                                        <h4>@if($prod?->is_active)<a href="{{ route('perfumes.show',$prod) }}">{{ $item->display_name }}</a>@else{{ $item->display_name }}@endif</h4>
                                        <div class="oa-product-meta"><span>{{ $item->volume_label }}</span><span>{{ __('Số lượng:') }} {{ $item->quantity }}</span></div>
                                        <strong class="oa-price">{{ number_format($item->price,0,',','.') }} <small>₫</small></strong>
                                        @include('partials.order-item-samples')
                                        @include('partials.order-item-gift')
                                    </div>
                                    <div class="oa-product-review">@include('partials.order-item-review')</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="oa-journey">
                            <div class="oa-journey-heading"><span class="oa-eyebrow">{{ __('HÀNH TRÌNH ĐƠN HÀNG') }}</span>@include('partials.icon',['name'=>'truck','size'=>19])</div>
                            @if($journey['step'])
                                <ol class="ol-stepper" aria-label="{{ __('Tiến trình giao hàng') }}">
                                    @foreach([__('Đã đặt hàng'), __('Chuẩn bị & vận chuyển'), __('Đang giao'), __('Hoàn thành')] as $label)
                                        <li class="{{ $loop->iteration <= $journey['step'] ? 'is-reached' : '' }} {{ $loop->iteration < $journey['step'] ? 'is-past' : '' }}" @if($loop->iteration === $journey['step']) aria-current="step" @endif><span class="ol-dot" aria-hidden="true">{{ $loop->iteration < $journey['step'] ? '✓' : '' }}</span><span>{{ $label }}</span></li>
                                    @endforeach
                                </ol>
                            @else<p class="oa-state-note">{{ __('Xem chi tiết để kiểm tra cập nhật gần nhất của đơn hàng.') }}</p>@endif
                        </div>
                    </div>
                    <aside class="oa-receipt">
                        <div class="oa-receipt-heading"><span class="oa-eyebrow">{{ __('TÓM TẮT ĐƠN HÀNG') }}</span>@include('partials.brand-mark',['size'=>'25px'])</div>
                        <div class="oa-receipt-total"><span>{{ __('Tổng thanh toán') }}</span><strong>{{ number_format($order->total_price,0,',','.') }} <small>₫</small></strong></div>
                        <div class="oa-shipping-fee"><span>{{ __('Cước vận chuyển') }}</span><span>{{ number_format($order->ghn_total_fee,0,',','.') }} ₫</span></div>
                        <div class="oa-payment">@include('user.payment._method',['order'=>$order])</div>
                        <div class="oa-destination"><span class="oa-eyebrow">{{ __('GỬI ĐẾN') }}</span><strong>{{ $order->name }}</strong><p>{{ $order->address }}</p></div>
                        @if($order->ghn_order_code)<div class="oa-waybill"><span>{{ __('Mã vận đơn') }}</span><strong>GHN · {{ $order->ghn_order_code }}</strong></div>@endif
                        <div class="oa-actions">
                            @if($order->status === 'pending' && $order->paymentTransactions->contains('gateway','sepay') && ($order->is_demo ? \App\Support\DemoMode::enabled() : app(\App\Services\SePayService::class)->ready()) && app(\App\Services\SePayService::class)->canPay($order))<a class="oa-button oa-button-secondary" href="{{ route('user.orders.sepay.pay',$order) }}">{{ __('Thanh toán qua SePay') }}</a>@endif
                            <a class="oa-button" href="{{ route('orders.show',$order) }}#order-journey-title">{{ __('Theo dõi đơn hàng') }} @include('partials.icon',['name'=>'arrow','size'=>17])</a>
                            <a class="oa-detail-link" href="{{ route('orders.show',$order) }}">{{ __('Xem chi tiết đơn hàng') }} @include('partials.icon',['name'=>'external','size'=>14])</a>
                        </div>
                    </aside>
                </article>
            @empty
                <div class="oa-empty">@include('partials.brand-mark',['size'=>'42px'])<h2>{{ __('Câu chuyện của bạn bắt đầu từ đây.') }}</h2><p>{{ __('Bạn chưa có đơn hàng nào') }}</p><a class="oa-button" href="{{ route('home') }}#san-pham">{{ __('Khám phá sản phẩm') }} ↗</a></div>
            @endforelse
        </div>
        <div class="ol-pagination">{{ $orders->links() }}</div>
        <footer class="oa-closing"><span>SOOPI</span><p>{{ __('Một mùi hương. Một câu chuyện của riêng bạn.') }}</p><a href="{{ route('store.contact') }}">{{ __('Cần hỗ trợ đơn hàng?') }} ↗</a></footer>
    </div>
</div>
@endsection
