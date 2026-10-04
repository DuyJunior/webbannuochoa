@extends('layouts.admin')
@section('title', __('Đối soát giao dịch SePay'))
@section('page_title', __('Đối soát giao dịch SePay'))
@section('styles')
    @include('admin.finance._styles')
@endsection
@section('content')
@php
    $results = ['paid' => __('Đã khớp thanh toán'), 'unmatched' => __('Không tìm thấy mã thanh toán'), 'amount_mismatch' => __('Sai số tiền'), 'duplicate_payment' => __('Chuyển tiền thêm cho đơn đã thanh toán'), 'late_payment' => __('Đến muộn · cần hoàn tiền'), 'demo_order' => __('Chuyển vào đơn mô phỏng'), 'order_not_payable' => __('Đơn không thể nhận thanh toán')];
@endphp
<p><a href="{{ route('admin.finance.transactions') }}">← {{ __('Quản lý giao dịch') }}</a></p>
<div class="finance-explainer">{{ __('Danh sách các khoản tiền vào đã xác thực chữ ký và tài khoản nhận. Sai tiền, sai mã hoặc chuyển thêm cần đối soát với ngân hàng; hệ thống không tự cộng dồn hay hoàn tiền. Giao dịch gửi lặp cùng ID chỉ được ghi nhận một lần.') }}</div>
<div class="admin-card mb-4">
    <form method="GET" class="d-flex flex-wrap" style="gap:12px;align-items:end">
        <div><label for="sepay-search">{{ __('Mã thanh toán hoặc ID SePay') }}</label><input class="form-control" id="sepay-search" name="search" maxlength="64" value="{{ $filters['search'] ?? '' }}"></div>
        <div><label for="sepay-result">{{ __('Kết quả đối soát') }}</label><select class="form-control" id="sepay-result" name="result"><option value="">{{ __('Tất cả') }}</option>@foreach($results as $value => $label)<option value="{{ $value }}" @selected(($filters['result'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <button type="submit" class="btn btn-pink">{{ __('Lọc') }}</button>
    </form>
</div>
<div class="admin-card"><div class="table-responsive">
    <table class="table table-admin finance-table">
        <thead><tr><th>{{ __('Giao dịch') }}</th><th>{{ __('Đơn hàng') }}</th><th>{{ __('Số tiền nhận') }}</th><th>{{ __('Số tiền đơn') }}</th><th>{{ __('Kết quả') }}</th></tr></thead>
        <tbody>@forelse($receipts as $receipt)
            <tr>
                <td><strong>SePay #{{ $receipt->provider_id }}</strong><div>{{ $receipt->reference_code ?: '—' }}</div><small>{{ \Carbon\Carbon::parse($receipt->received_at)->format('d/m/Y H:i:s') }}</small></td>
                <td>@if($receipt->order_id)<a href="{{ route('admin.orders.show', $receipt->order_id) }}">#{{ $receipt->order_id }}</a>@endif<div>{{ $receipt->payment_code ?: '—' }}</div></td>
                <td>{{ number_format($receipt->amount, 0, ',', '.') }} ₫</td>
                <td>{{ $receipt->expected_amount !== null ? number_format($receipt->expected_amount, 0, ',', '.').' ₫' : '—' }}</td>
                <td>{{ $results[$receipt->result] ?? $receipt->result }}
                    @if($receipt->result === 'paid' && $receipt->order_status === 'paid' && $receipt->payment_status === 'paid' && !$receipt->ghn_order_code)
                        <div class="text-warning">{{ $receipt->shipment_attempted_at ? __('Chưa xác nhận vận đơn. Kiểm tra GHN trước khi tạo lại.') : __('Đang chờ worker tạo vận đơn.') }}</div>
                    @endif
                </td>
            </tr>
        @empty<tr><td colspan="5">{{ __('Chưa có giao dịch SePay phù hợp.') }}</td></tr>@endforelse</tbody>
    </table>
</div>{{ $receipts->links('pagination::bootstrap-4') }}</div>
@endsection
