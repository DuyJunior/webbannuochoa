@extends('layouts.admin')
@section('title', 'Quản lý giao dịch')
@section('page_title', 'Quản lý giao dịch')
@section('styles')
    @include('admin.finance._styles')
@endsection
@section('content')
<div class="finance-heading">
    <div><h2>Quản lý giao dịch</h2><p class="finance-muted mb-2">Tra cứu thanh toán và ghi nhận đối soát COD có lịch sử.</p><span class="finance-mode {{ $filters['mode'] === 'demo' ? 'demo' : '' }}">{{ $filters['mode'] === 'demo' ? 'DỮ LIỆU MÔ PHỎNG · DEMO' : 'GIAO DỊCH THỰC' }}</span></div>
    <div class="finance-actions"><a class="btn btn-outline-pink" href="{{ route('admin.finance.index', array_diff_key($filters, ['page' => true])) }}">Tổng quan</a><a class="btn btn-pink" href="{{ route('admin.finance.export', array_diff_key($filters, ['page' => true])) }}"><i class="fa-solid fa-download" aria-hidden="true"></i> Xuất CSV</a></div>
</div>
@include('admin.finance._filters', ['filterRoute' => 'admin.finance.transactions'])
@include('admin.finance._metrics')
<div class="finance-explainer">Chỉ COD được cập nhật thủ công tại đây. Đơn đã hủy hoặc đang/đã hoàn hàng không được thu tiền. “Đã hoàn tiền” ghi nhận khoản hoàn đã thực hiện bên ngoài và cần mã chứng từ với giao dịch thực; thao tác này không chuyển tiền, không thay đổi giao hàng hoặc tồn kho.</div>
@include('admin.finance._table', ['canReconcile' => true])
@endsection
