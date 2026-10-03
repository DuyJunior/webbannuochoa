@extends('layouts.admin')

@section('title', __('Doanh thu · ').$livestream->title)
@section('page_title', __('Doanh thu livestream'))
@section('styles')<link rel="stylesheet" href="{{ asset('css/livestream-admin.css') }}">@endsection

@section('content')
<div class="live-admin">
    <header class="live-admin-hero">
        <div><p class="live-admin-kicker">{{ __('Báo cáo buổi phát') }}</p><h2>{{ $livestream->title }}</h2><p>{{ $livestream->status === 'ended' ? __('Buổi live đã kết thúc. Doanh thu tiếp tục cập nhật khi đơn được thanh toán.') : __('Doanh thu được cập nhật khi đơn hàng được thanh toán.') }}</p></div>
        <div class="live-admin-actions"><a class="live-admin-btn" href="{{ route('admin.livestreams.index') }}">{{ __('← Tất cả buổi live') }}</a></div>
    </header>
    <section class="live-revenue-panel" aria-labelledby="report-title">
        <div class="live-admin-panel-heading"><div><span class="live-admin-kicker">{{ __('Giờ Việt Nam') }}</span><h3 id="report-title">{{ __('Kết quả bán hàng') }}</h3></div><small>{{ __('Doanh thu hàng từ live đã thu tiền, sau ưu đãi và chưa gồm phí giao hàng.') }}</small></div>
        <div class="live-revenue-grid">
            @foreach(['all' => __('Cả buổi live'), 'today' => __('Hôm nay'), 'week' => __('7 ngày qua'), 'month' => __('30 ngày qua')] as $key => $label)
                <div><span>{{ __($label) }}</span><strong>{{ number_format($periods[$key]['revenue'], 0, ',', '.') }}{{ __('đ') }}</strong><small>{{ $periods[$key]['orders'] }} {{ __('đơn đã thu ·') }} {{ $periods[$key]['units'] }} {{ __('sản phẩm') }}</small><small>{{ __('Chờ thu:') }} {{ number_format($periods[$key]['pending_value'], 0, ',', '.') }}{{ __('đ /') }} {{ $periods[$key]['pending_orders'] }} {{ __('đơn') }}</small></div>
            @endforeach
        </div>
        <p>{{ __('Chỉ tính sản phẩm khách đã mở từ buổi live này. Khoảng thời gian theo ngày đặt hàng; đơn bị hủy và hoàn tiền không được cộng.') }}</p>
    </section>
    <section class="live-funnel" aria-label="{{ __('Mức độ quan tâm trong buổi live') }}">
        <div><span>{{ __('Khách đã xem') }}</span><strong>{{ number_format($viewerTotal, 0, ',', '.') }}</strong><small>{{ __('Thiết bị hoặc phiên truy cập duy nhất') }}</small></div>
        <div><span>{{ __('Lượt mở sản phẩm') }}</span><strong>{{ number_format($clickTotal, 0, ',', '.') }}</strong><small>{{ __('Từ kệ nước hoa của buổi live') }}</small></div>
        <div><span>{{ __('Đơn đã thu tiền') }}</span><strong>{{ number_format($periods['all']['orders'], 0, ',', '.') }}</strong><small>{{ __('Có sản phẩm được gắn nguồn live') }}</small></div>
    </section>
    @if($clickedProducts->isNotEmpty())
        <section class="live-admin-panel" style="margin-bottom:22px">
            <div class="live-admin-panel-heading"><h3>{{ __('Sản phẩm được quan tâm') }}</h3><small>{{ __('Lượt bấm từ kệ live') }}</small></div>
            <div class="live-report-products">
                @foreach($clickedProducts as $item)
                    <div class="live-report-product"><strong>{{ $item['name'] }}</strong><span>{{ number_format($item['clicks'], 0, ',', '.') }} {{ __('lượt mở') }}</span></div>
                @endforeach
            </div>
        </section>
    @endif
    <section class="live-admin-panel">
        <div class="live-admin-panel-heading"><h3>{{ __('Sản phẩm đã giới thiệu') }}</h3><small>{{ $livestream->products->count() }} {{ __('sản phẩm') }}</small></div>
        @include('admin.livestreams.products', ['products' => $livestream->products, 'readOnly' => true])
    </section>
</div>
@endsection
