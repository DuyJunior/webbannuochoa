@extends('layouts.store')
@section('title', __('Thanh toán SePay · Soopi'))

@section('content')
@php
    $statusMessages = ['waiting' => __('Đang chờ xác nhận thanh toán từ ngân hàng…'), 'expired' => __('Đơn hàng không còn nhận thanh toán. Nếu đã chuyển tiền, vui lòng liên hệ cửa hàng.'), 'retry' => __('Chưa kết nối được để kiểm tra. Hệ thống sẽ thử lại; không chuyển tiền lần nữa.'), 'unavailable' => __('Vui lòng mở lại đơn hàng để kiểm tra trạng thái.')];
@endphp
<section class="sepay-page store-container" data-sepay-payment data-status-url="{{ route('user.orders.sepay.status', $order) }}" data-expires-at="{{ $order->payment_expires_at->toIso8601String() }}">
    <div class="sepay-heading">
        <p class="sepay-eyebrow">SEPAY · {{ $receiver['bank'] }}</p>
        <h1>{{ __('Quét QR để thanh toán') }}</h1>
        <p>{{ __('Mở ứng dụng ngân hàng, quét mã và giữ nguyên số tiền cùng nội dung chuyển khoản.') }}</p>
    </div>
    <div class="sepay-layout">
        <div class="sepay-card sepay-qr-card">
            <img data-payment-qr src="{{ $qrUrl }}" alt="{{ __('Mã QR chuyển khoản ngân hàng') }}" width="320" height="360" referrerpolicy="no-referrer">
            <p class="sepay-status" data-payment-status role="status" aria-live="polite">{{ __('Đang chờ xác nhận thanh toán từ ngân hàng…') }}</p>
            <p>{{ __('Thời gian còn lại:') }} <strong data-payment-countdown>—</strong></p>
            <p class="sepay-hint">{{ __('Nếu đã chuyển tiền, vui lòng chờ xác nhận và không chuyển lại.') }}</p>
        </div>
        <div class="sepay-card">
            <h2>{{ __('Thông tin chuyển khoản') }}</h2>
            <dl class="sepay-details">
                <div><dt>{{ __('Đơn hàng') }}</dt><dd>#{{ $order->id }}</dd></div>
                <div><dt>{{ __('Ngân hàng') }}</dt><dd>{{ $receiver['bank'] }}</dd></div>
                <div><dt>{{ __('Chủ tài khoản') }}</dt><dd>{{ $receiver['account_name'] }}</dd></div>
                <div><dt>{{ __('Tài khoản nhận') }}</dt><dd>{{ $receiver['sub_account'] ?: $receiver['account_number'] }}</dd></div>
                <div><dt>{{ __('Số tiền') }}</dt><dd class="sepay-amount">{{ number_format($payment->amount, 0, ',', '.') }}₫</dd></div>
                <div><dt>{{ __('Nội dung chuyển khoản') }}</dt><dd><strong>{{ $payment->gateway_order_id }}</strong></dd></div>
            </dl>
            <p class="sepay-hint">{{ __('Website chỉ xác nhận sau khi nhận thông báo hợp lệ từ SePay. Bạn không cần nhập số thẻ hay OTP tại đây.') }}</p>
            <a class="sepay-back" href="{{ route('orders.show', $order) }}">{{ __('Xem đơn hàng') }} →</a>
            <noscript><p>{{ __('Vui lòng tải lại trang đơn hàng để xem trạng thái sau khi chuyển khoản.') }}</p></noscript>
        </div>
    </div>
</section>
<style>
.sepay-page{padding-top:40px;padding-bottom:64px;max-width:1000px}.sepay-heading{text-align:center;margin-bottom:28px}.sepay-heading h1{font-size:clamp(26px,4vw,38px);margin:10px 0}.sepay-eyebrow{font-size:12px;letter-spacing:.16em;color:#846a46;font-weight:700}.sepay-layout{display:grid;grid-template-columns:1.1fr 1fr;gap:24px}.sepay-card{padding:28px;border:1px solid #e8e2d9;border-radius:20px;background:#fff}.sepay-card h2{font-size:21px;margin:0 0 24px}.sepay-qr-card{text-align:center}.sepay-qr-card img{display:block;margin:auto;max-width:100%;height:auto;border-radius:10px}.sepay-qr-card img[hidden]{display:none}.sepay-status{font-weight:600;margin-top:22px;color:#655236}.sepay-hint{font-size:14px;line-height:1.7;color:#6b6258}.sepay-details{margin:0}.sepay-details>div{padding:14px 0;border-bottom:1px solid #eee8df}.sepay-details dt{font-size:13px;color:#736c63;margin-bottom:5px}.sepay-details dd{margin:0;overflow-wrap:anywhere;font-weight:600}.sepay-details .sepay-amount{font-size:26px;color:#7d5930}.sepay-back{display:inline-block;margin-top:14px;color:#6c4b2b;font-weight:600}@media(max-width:680px){.sepay-layout{grid-template-columns:1fr}.sepay-card{padding:22px}.sepay-page{padding-top:28px}}
</style>
<script>
(() => {
    const root = document.querySelector('[data-sepay-payment]');
    const status = root.querySelector('[data-payment-status]');
    const countdown = root.querySelector('[data-payment-countdown]');
    const qr = root.querySelector('[data-payment-qr]');
    const expires = Date.parse(root.dataset.expiresAt);
    const messages = @json($statusMessages);
    let stopped = false;
    let timer;
    const tick = () => {
        const seconds = Math.max(0, Math.ceil((expires - Date.now()) / 1000));
        countdown.textContent = `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
        if (seconds === 0) { qr.hidden = true; status.textContent = messages.expired; }
    };
    tick();
    const ticker = setInterval(tick, 1000);
    async function poll() {
        if (stopped) return;
        if (!document.hidden) {
            try {
                const response = await fetch(root.dataset.statusUrl, { headers: {Accept: 'application/json'}, cache: 'no-store', signal: AbortSignal.timeout(8000) });
                if ([401, 403, 404].includes(response.status)) {
                    stopped = true; qr.hidden = true; status.textContent = messages.unavailable;
                } else if (!response.ok) {
                    throw new Error('status');
                } else {
                    const result = await response.json();
                    if (result.status === 'paid') { stopped = true; window.location.assign(result.order_url); return; }
                    if (!result.can_pay) { stopped = true; qr.hidden = true; status.textContent = messages.expired; }
                    else if (Date.now() < expires) status.textContent = messages.waiting;
                }
            } catch { if (Date.now() < expires) status.textContent = messages.retry; }
        }
        if (!stopped) timer = setTimeout(poll, 5000);
    }
    timer = setTimeout(poll, 3000);
    window.addEventListener('pagehide', () => { stopped = true; clearTimeout(timer); clearInterval(ticker); });
})();
</script>
@endsection
