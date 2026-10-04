@extends('layouts.admin')
@section('title', __('Quản lý giao dịch'))
@section('page_title', __('Quản lý giao dịch'))
@section('styles')
    @include('admin.finance._styles')
@endsection
@section('content')
<p><a href="{{ route('admin.finance.sepay') }}">{{ __('Đối soát giao dịch SePay') }} →</a></p>
<div class="finance-heading">
    <div><span class="finance-mode {{ $filters['mode'] === 'demo' ? 'demo' : '' }}">{{ $filters['mode'] === 'demo' ? __('DỮ LIỆU MÔ PHỎNG · DEMO') : __('GIAO DỊCH THỰC') }}</span></div>
    <div class="finance-actions"><a class="btn btn-outline-pink" href="{{ route('admin.finance.index', array_diff_key($filters, ['page' => true])) }}">{{ __('Tổng quan') }}</a><a class="btn btn-pink" href="{{ route('admin.finance.export', array_diff_key($filters, ['page' => true])) }}"><i class="fa-solid fa-download" aria-hidden="true"></i> {{ __('Xuất CSV') }}</a></div>
</div>
@include('admin.finance._filters', ['filterRoute' => 'admin.finance.transactions'])
@include('admin.finance._metrics')
<div class="finance-explainer">{{ __('Chỉ COD được ghi nhận thu tiền thủ công. SePay xác nhận thu tiền qua webhook; admin chỉ ghi nhận hoàn tiền đã thực hiện bên ngoài. Hoàn tiền thực cần mã chứng từ; thao tác này không chuyển tiền, không thay đổi giao hàng hoặc tồn kho.') }}</div>
@include('admin.finance._table', ['canReconcile' => true])
@endsection
