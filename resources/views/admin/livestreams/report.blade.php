@extends('layouts.admin')

@section('title', 'Doanh thu · '.$livestream->title)
@section('page_title', 'Doanh thu livestream')
@section('styles')<link rel="stylesheet" href="{{ asset('css/livestream-admin.css') }}">@endsection

@section('content')
<div class="live-admin">
    <header class="live-admin-hero">
        <div><p class="live-admin-kicker">Báo cáo buổi phát</p><h2>{{ $livestream->title }}</h2><p>{{ $livestream->status === 'ended' ? 'Buổi live đã kết thúc. Doanh thu tiếp tục cập nhật khi đơn được thanh toán.' : 'Doanh thu được cập nhật khi đơn hàng được thanh toán.' }}</p></div>
        <div class="live-admin-actions"><a class="live-admin-btn" href="{{ route('admin.livestreams.index') }}">← Tất cả buổi live</a></div>
    </header>
    <section class="live-revenue-panel" aria-labelledby="report-title">
        <div class="live-admin-panel-heading"><div><span class="live-admin-kicker">Giờ Việt Nam</span><h3 id="report-title">Kết quả bán hàng</h3></div><small>Doanh thu hàng từ live đã thu tiền, sau ưu đãi và chưa gồm phí giao hàng.</small></div>
        <div class="live-revenue-grid">
            @foreach(['all' => 'Cả buổi live', 'today' => 'Hôm nay', 'week' => '7 ngày qua', 'month' => '30 ngày qua'] as $key => $label)
                <div><span>{{ $label }}</span><strong>{{ number_format($periods[$key]['revenue'], 0, ',', '.') }}đ</strong><small>{{ $periods[$key]['orders'] }} đơn đã thu · {{ $periods[$key]['units'] }} sản phẩm</small><small>Chờ thu: {{ number_format($periods[$key]['pending_value'], 0, ',', '.') }}đ / {{ $periods[$key]['pending_orders'] }} đơn</small></div>
            @endforeach
        </div>
        <p>Chỉ tính sản phẩm khách đã mở từ buổi live này. Khoảng thời gian theo ngày đặt hàng; đơn bị hủy và hoàn tiền không được cộng.</p>
    </section>
    <section class="live-funnel" aria-label="Mức độ quan tâm trong buổi live">
        <div><span>Khách đã xem</span><strong>{{ number_format($viewerTotal, 0, ',', '.') }}</strong><small>Thiết bị hoặc phiên truy cập duy nhất</small></div>
        <div><span>Lượt mở sản phẩm</span><strong>{{ number_format($clickTotal, 0, ',', '.') }}</strong><small>Từ kệ nước hoa của buổi live</small></div>
        <div><span>Đơn đã thu tiền</span><strong>{{ number_format($periods['all']['orders'], 0, ',', '.') }}</strong><small>Có sản phẩm được gắn nguồn live</small></div>
    </section>
    @if($clickedProducts->isNotEmpty())
        <section class="live-admin-panel" style="margin-bottom:22px">
            <div class="live-admin-panel-heading"><h3>Sản phẩm được quan tâm</h3><small>Lượt bấm từ kệ live</small></div>
            <div class="live-report-products">
                @foreach($clickedProducts as $item)
                    <div class="live-report-product"><strong>{{ $item['name'] }}</strong><span>{{ number_format($item['clicks'], 0, ',', '.') }} lượt mở</span></div>
                @endforeach
            </div>
        </section>
    @endif
    <section class="live-admin-panel">
        <div class="live-admin-panel-heading"><h3>Sản phẩm đã giới thiệu</h3><small>{{ $livestream->products->count() }} sản phẩm</small></div>
        @include('admin.livestreams.products', ['products' => $livestream->products, 'readOnly' => true])
    </section>
</div>
@endsection
