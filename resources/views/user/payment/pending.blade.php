@extends('layouts.store')
@section('title', __('Thanh toán demo'))
@section('content')
<section style="max-width:720px;margin:48px auto;padding:28px;border:1px solid #d4b77d;border-radius:20px;background:#fff">
    <p style="color:#8b5b15;font-weight:bold">{{ __('DEMO LOCAL · KHÔNG THU TIỀN THẬT') }}</p>
    <h1>{{ __('Mô phỏng thanh toán đơn #') }}{{ $order->id }}</h1>
    <p>{{ __('Tổng tiền:') }} <strong>{{ number_format($order->total_price, 0, ',', '.') }}{{ __('đ') }}</strong></p>
    <p>{{ __('Không nhập số thẻ, OTP hoặc thông tin ngân hàng. Không gọi MoMo hay tạo vận đơn thật.') }}</p>
    @if(session('error'))<p role="alert">{{ session('error') }}</p>@endif
    @foreach($errors->all() as $error)<p role="alert">{{ $error }}</p>@endforeach
    <form method="POST" action="{{ route('user.orders.confirm.payment', $order) }}" style="display:grid;gap:16px;margin:24px 0">
        @csrf
        <label for="scenario">{{ __('Kịch bản kiểm thử') }}</label>
        <select id="scenario" name="scenario" style="padding:12px">
            <option value="success">{{ __('Thanh toán thành công') }}</option>
            <option value="declined">{{ __('Thẻ bị từ chối') }}</option>
            <option value="insufficient">{{ __('Không đủ số dư') }}</option>
            <option value="limit">{{ __('Vượt hạn mức') }}</option>
        </select>
        <button class="luxury-auth-btn" type="submit">{{ __('Chạy mô phỏng') }}</button>
    </form>
    <a href="{{ route('orders.show', $order) }}">{{ __('Quay lại đơn hàng') }}</a>
</section>
@endsection
