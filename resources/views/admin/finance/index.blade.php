@extends('layouts.admin')
@section('title', 'Tổng quan tài chính')
@section('page_title', 'Tổng quan tài chính')
@section('styles')
    @include('admin.finance._styles')
@endsection
@section('content')
<div class="finance-heading">
    <div><h2>Tổng quan tài chính</h2><p class="finance-muted mb-2">Theo dõi giá trị đơn và trạng thái thanh toán trên toàn bộ kết quả đã lọc.</p><span class="finance-mode {{ $filters['mode'] === 'demo' ? 'demo' : '' }}">{{ $filters['mode'] === 'demo' ? 'DỮ LIỆU MÔ PHỎNG · DEMO' : 'GIAO DỊCH THỰC' }}</span></div>
    <div class="finance-actions"><a class="btn btn-outline-pink" href="{{ route('admin.finance.transactions', array_diff_key($filters, ['page' => true])) }}">Quản lý giao dịch</a><a class="btn btn-pink" href="{{ route('admin.finance.export', array_diff_key($filters, ['page' => true])) }}"><i class="fa-solid fa-download" aria-hidden="true"></i> Xuất CSV</a></div>
</div>
@include('admin.finance._filters', ['filterRoute' => 'admin.finance.index'])
@include('admin.finance._metrics')
<div class="finance-explainer">Số liệu dùng ngày tạo đơn đến thời điểm hiện tại và tổng giá trị đơn, bao gồm phí giao hàng. Đây là tổng hợp trạng thái thanh toán hiện tại, không phải lợi nhuận hay sao kê dòng tiền. Mỗi đơn chỉ lấy một giao dịch đại diện: ưu tiên giao dịch đã thu/chờ hoàn/đã hoàn, sau đó chọn mã giao dịch mới nhất.</div>
<div class="finance-charts">
    <div class="admin-card">
        <h3 class="finance-chart-title">Phân bổ trạng thái thanh toán</h3>
        @foreach($paymentLabels as $status => $label)
            @php($row = $statusTotals->get($status))
            <div class="finance-bar-row">
                <div class="finance-bar-label"><span>{{ $label }} · {{ number_format($row?->order_count ?? 0) }} đơn</span><strong>{{ number_format($row?->total_amount ?? 0, 0, ',', '.') }} ₫</strong></div>
                <div class="finance-bar-track" role="img" aria-label="{{ $label }}: {{ $row?->order_count ?? 0 }} đơn"><div class="finance-bar-fill" style="width:{{ $totals->order_count ? round(($row?->order_count ?? 0) / $totals->order_count * 100, 2) : 0 }}%"></div></div>
            </div>
        @endforeach
        <p class="finance-muted mb-0">Chiều dài thanh biểu thị tỷ lệ số đơn.</p>
    </div>
    <div class="admin-card">
        <h3 class="finance-chart-title">Theo phương thức thanh toán</h3>
        @foreach($gatewayLabels as $gateway => $label)
            @if($gateway !== 'demo' || $filters['mode'] === 'demo')
                @php($row = $methodTotals->get($gateway))
                <div class="finance-bar-row">
                    <div class="finance-bar-label"><span>{{ $label }} · {{ number_format($row?->order_count ?? 0) }} đơn</span><strong>{{ number_format($row?->total_amount ?? 0, 0, ',', '.') }} ₫</strong></div>
                    <div class="finance-bar-track" role="img" aria-label="{{ $label }}: {{ $row?->order_count ?? 0 }} đơn"><div class="finance-bar-fill" style="width:{{ $totals->order_count ? round(($row?->order_count ?? 0) / $totals->order_count * 100, 2) : 0 }}%;background:#8e769b"></div></div>
                    <div class="finance-muted mt-1">Giá trị đơn đã thanh toán: <strong>{{ number_format($row?->paid_amount ?? 0, 0, ',', '.') }} ₫</strong></div>
                </div>
            @endif
        @endforeach
        <p class="finance-muted mb-0">Giá trị đã thanh toán chỉ tính đơn đang ở trạng thái “Đã thanh toán”, không gồm đơn chờ hoàn/đã hoàn tiền. Đơn cũ thiếu thông tin phương thức được ghi là “Chưa xác định”.</p>
    </div>
</div>
@include('admin.finance._table', ['canReconcile' => false])
@endsection
