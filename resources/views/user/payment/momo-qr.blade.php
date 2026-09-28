@extends('layouts.store')

@section('title', 'Thanh toán MoMo · Soopi')

@section('content')
<div class="momo-qr-page">
    <div class="store-container">

        <nav class="momo-breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('orders.index') }}">Đơn hàng của tôi</a>
            <span>/</span>
            <span class="active">Thanh toán MoMo</span>
        </nav>

        <div class="momo-pay-layout">

            {{-- Cột trái: QR MoMo --}}
            <div class="momo-main-col">
                <div class="momo-card">
                    <div class="momo-card-header">
                        <img src="{{ asset('images/payments/momo.svg') }}" alt="MoMo" class="momo-logo">
                        <div>
                            <h2>Thanh toán qua MoMo</h2>
                            <small>Quét mã QR bằng app MoMo để thanh toán nhanh</small>
                        </div>
                        <span class="momo-auto-badge">Tự động</span>
                    </div>

                    {{-- QR Code MoMo --}}
                    <div class="momo-qr-body">
                        <div class="momo-qr-frame">
                            {{-- QR code MoMo sử dụng QR server với dữ liệu đơn hàng --}}
                            <img
                                src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data={{ urlencode('2|99|0387350999|HA+THU+PERFUME||' . (int)$order->total_price . '|0|HATHU' . $order->id) }}&color=a50064&bgcolor=ffffff&margin=12"
                                alt="QR MoMo"
                                class="momo-qr-img"
                                id="momoQrImg"
                            >
                            <div class="momo-qr-center-logo">
                                <img src="{{ asset('images/payments/momo.svg') }}" alt="M" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">
                            </div>
                        </div>

                        <div class="momo-scan-steps">
                            <div class="scan-step">
                                <span class="step-num">1</span>
                                <span>Mở ứng dụng <strong>MoMo</strong> trên điện thoại</span>
                            </div>
                            <div class="scan-step">
                                <span class="step-num">2</span>
                                <span>Nhấn biểu tượng <strong>Quét QR</strong> trên thanh điều hướng</span>
                            </div>
                            <div class="scan-step">
                                <span class="step-num">3</span>
                                <span>Hướng camera vào mã QR phía trên để quét</span>
                            </div>
                            <div class="scan-step">
                                <span class="step-num">4</span>
                                <span>Kiểm tra thông tin và <strong>xác nhận thanh toán</strong></span>
                            </div>
                        </div>
                    </div>

                    {{-- Thông tin thanh toán --}}
                    <div class="momo-info-rows">
                        <div class="momo-info-row">
                            <span class="momo-info-label">Số điện thoại MoMo</span>
                            <div class="momo-info-val-wrap">
                                <span id="momo-phone" class="momo-info-value"><strong>0387350999</strong></span>
                                <button class="momo-copy-btn" onclick="copyMomoText('momo-phone', this)">📋</button>
                            </div>
                        </div>
                        <div class="momo-info-row">
                            <span class="momo-info-label">Chủ tài khoản</span>
                            <div class="momo-info-val-wrap">
                                <span class="momo-info-value"><strong>HA THU PERFUME</strong></span>
                            </div>
                        </div>
                        <div class="momo-info-row highlight">
                            <span class="momo-info-label">Số tiền</span>
                            <div class="momo-info-val-wrap">
                                <span class="momo-info-value momo-amount">{{ number_format($order->total_price, 0, ',', '.') }} VNĐ</span>
                                <button class="momo-copy-btn" onclick="copyMomoAmount({{ (int)$order->total_price }}, this)">📋</button>
                            </div>
                        </div>
                        <div class="momo-info-row highlight">
                            <span class="momo-info-label">Nội dung</span>
                            <div class="momo-info-val-wrap">
                                <span id="momo-note" class="momo-info-value momo-note-val"><strong>HATHU{{ $order->id }}</strong></span>
                                <button class="momo-copy-btn" onclick="copyMomoText('momo-note', this)">📋</button>
                            </div>
                        </div>
                    </div>

                    {{-- Nút redirect sang MoMo app --}}
                    @if($momoUrl)
                    <div class="momo-redirect-section">
                        <a href="{{ $momoUrl }}" class="btn-open-momo" id="btnOpenMomo">
                            <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png" alt="MoMo" style="height:22px;border-radius:4px;" onerror="this.style.display='none'">
                            Mở ứng dụng MoMo để thanh toán
                        </a>
                        <p class="redirect-hint">Hoặc click nút trên nếu đang dùng điện thoại có cài app MoMo</p>
                    </div>
                    @endif

                    <div class="momo-notice-box">
                        ℹ️ <strong>Lưu ý:</strong> Vui lòng ghi đúng nội dung <strong>HATHU{{ $order->id }}</strong> để hệ thống tự xác nhận. Đơn hàng sẽ được xử lý trong vòng 5–10 phút sau khi thanh toán thành công.
                    </div>

                    {{-- Nút xác nhận đã thanh toán --}}
                    <form method="POST" action="{{ route('user.orders.confirm.payment', $order) }}" style="margin: 0 24px 20px;">
                        @csrf
                        <input type="hidden" name="gateway" value="momo">
                        <button type="submit" style="width:100%; padding:14px; background:linear-gradient(135deg, #10b981, #059669); color:#fff; border:none; border-radius:12px; font-weight:700; font-size:1rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 14px rgba(16,185,129,0.3); transition: transform 0.15s;" onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                            <span>✅</span>
                            <span>Tôi Đã Chuyển Tiền Thành Công</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Cột phải: Tóm tắt đơn hàng --}}
            <div class="momo-side-col">
                <div class="momo-summary-box">
                    <h3 class="momo-summary-title">📦 Đơn hàng #{{ $order->id }}</h3>

                    <div class="momo-sum-items">
                        @foreach($order->items as $item)
                            @php $prod = $item->product ?? $item->perfume; @endphp
                            <div class="momo-sum-row">
                                <div class="momo-sum-thumb">
                                    @if($prod && $prod->image_src)
                                        <img src="{{ $prod->image_src }}" alt="{{ $prod->name }}">
                                    @else
                                        <span>🌸</span>
                                    @endif
                                    <span class="momo-qty-badge">{{ $item->quantity }}</span>
                                </div>
                                <div class="momo-sum-info">
                                    <span class="momo-sum-name">{{ $prod->name ?? 'Nước hoa' }}</span>
                                    <span class="momo-sum-meta">{{ $item->volume_ml ?? 100 }}ml · {{ number_format($item->price, 0, ',', '.') }}₫</span>
                                </div>
                                <span class="momo-sum-price">{{ number_format($item->price * $item->quantity, 0, ',', '.') }}₫</span>
                            </div>
                        @endforeach
                    </div>

                    @php $itemsTotal = $order->items->sum(fn($i) => $i->price * $i->quantity); @endphp
                    <div class="momo-sum-costs">
                        <div class="momo-cost-row">
                            <span>Tiền hàng</span>
                            <span>{{ number_format($itemsTotal, 0, ',', '.') }} VNĐ</span>
                        </div>
                        <div class="momo-cost-row">
                            <span>Phí vận chuyển</span>
                            <span>{{ number_format($order->ghn_total_fee ?? 0, 0, ',', '.') }} VNĐ</span>
                        </div>
                        <div class="momo-cost-row momo-total-row">
                            <span>Tổng thanh toán</span>
                            <strong class="momo-total-price">{{ number_format($order->total_price, 0, ',', '.') }} VNĐ</strong>
                        </div>
                    </div>

                    <div class="momo-receiver">
                        <h4>📬 Giao đến</h4>
                        <p><strong>{{ $order->name }}</strong></p>
                        <p>📞 {{ $order->phone }}</p>
                        <p>📍 {{ $order->address }}</p>
                    </div>

                    <div class="momo-timer-box">
                        <span>⏰</span>
                        <div>
                            <strong>Thời hạn thanh toán</strong>
                            <div id="momo-countdown" class="momo-countdown">15:00</div>
                        </div>
                    </div>

                    <a href="{{ route('orders.index') }}" class="btn-momo-back">Xem lịch sử đơn hàng</a>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.momo-qr-page { padding: 32px 0 80px; background: #faf8f9; min-height: 80vh; }
.momo-breadcrumb { display: flex; gap: 8px; font-size: 0.85rem; color: #6b7280; margin-bottom: 24px; }
.momo-breadcrumb a { color: #4b5563; text-decoration: none; }
.momo-breadcrumb a:hover { color: #a50064; }
.momo-breadcrumb .active { color: #a50064; font-weight: 600; }
.momo-pay-layout { display: grid; grid-template-columns: 1fr 330px; gap: 28px; align-items: flex-start; }
.momo-card { background: #fff; border-radius: 20px; overflow: hidden; border: 1px solid #f9e8f3; box-shadow: 0 6px 28px rgba(165,0,100,0.08); }
.momo-card-header { display: flex; align-items: center; gap: 14px; padding: 20px 24px 18px; background: linear-gradient(135deg, #a50064 0%, #d82d8b 100%); color: #fff; }
.momo-logo { height: 38px; border-radius: 8px; background: #fff; padding: 3px; }
.momo-card-header h2 { font-size: 1.1rem; font-weight: 700; color: #fff; margin: 0; }
.momo-card-header small { color: rgba(255,255,255,0.8); font-size: 0.8rem; }
.momo-auto-badge { margin-left: auto; background: rgba(255,255,255,0.25); color: #fff; font-size: 0.72rem; font-weight: 700; padding: 3px 10px; border-radius: 9999px; border: 1px solid rgba(255,255,255,0.3); white-space: nowrap; }
.momo-qr-body { display: flex; gap: 24px; padding: 28px 24px 20px; align-items: flex-start; }
.momo-qr-frame { position: relative; display: inline-block; border: 4px solid #a50064; border-radius: 18px; padding: 6px; background: #fff; box-shadow: 0 6px 24px rgba(165,0,100,0.18); flex-shrink: 0; }
.momo-qr-img { width: 200px; height: 200px; border-radius: 12px; display: block; }
.momo-qr-center-logo { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 8px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
.momo-qr-center-logo img { width: 32px; height: 32px; border-radius: 6px; object-fit: contain; }
.momo-scan-steps { flex: 1; display: flex; flex-direction: column; gap: 12px; }
.scan-step { display: flex; align-items: flex-start; gap: 10px; font-size: 0.87rem; color: #374151; }
.step-num { display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: linear-gradient(135deg, #a50064, #d82d8b); color: #fff; font-size: 0.75rem; font-weight: 700; flex-shrink: 0; margin-top: 1px; }
.momo-info-rows { margin: 0 24px 20px; border: 1px solid #fce7f3; border-radius: 14px; overflow: hidden; }
.momo-info-row { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #fdf2f8; }
.momo-info-row:last-child { border-bottom: none; }
.momo-info-row.highlight { background: #fdf2f8; }
.momo-info-label { width: 130px; flex-shrink: 0; font-size: 0.82rem; color: #6b7280; }
.momo-info-val-wrap { display: flex; align-items: center; gap: 8px; flex: 1; }
.momo-info-value { font-size: 0.92rem; color: #111827; }
.momo-amount { font-size: 1.15rem; font-weight: 800; color: #a50064; }
.momo-note-val strong { color: #d82d8b; font-size: 1rem; }
.momo-copy-btn { background: none; border: 1px solid #fce7f3; border-radius: 6px; padding: 2px 7px; cursor: pointer; font-size: 0.85rem; transition: all 0.2s; }
.momo-copy-btn:hover { background: #fdf2f8; }
.momo-copy-btn.copied { background: #d1fae5; border-color: #6ee7b7; color: #065f46; }
.momo-redirect-section { padding: 16px 24px; border-top: 1px solid #fdf2f8; text-align: center; }
.btn-open-momo { display: inline-flex; align-items: center; gap: 10px; background: linear-gradient(135deg, #a50064 0%, #d82d8b 100%); color: #fff; font-weight: 700; font-size: 0.95rem; padding: 13px 24px; border-radius: 12px; text-decoration: none; box-shadow: 0 4px 16px rgba(165,0,100,0.3); transition: all 0.25s; }
.btn-open-momo:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(165,0,100,0.4); }
.redirect-hint { margin: 10px 0 0; font-size: 0.78rem; color: #9ca3af; }
.momo-notice-box { margin: 0 24px 24px; background: #fdf2f8; border: 1px solid #fce7f3; border-radius: 10px; padding: 12px 16px; font-size: 0.82rem; color: #7e1450; line-height: 1.5; }
/* Summary box */
.momo-summary-box { background: #fff; border-radius: 16px; padding: 22px; border: 1px solid #f9e8f3; box-shadow: 0 4px 20px rgba(165,0,100,0.05); position: sticky; top: 90px; }
.momo-summary-title { font-size: 1rem; font-weight: 700; color: #111827; margin: 0 0 16px; }
.momo-sum-items { max-height: 200px; overflow-y: auto; margin-bottom: 14px; }
.momo-sum-row { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px dashed #fdf2f8; }
.momo-sum-thumb { width: 40px; height: 40px; border-radius: 8px; background: #fafafa; border: 1px solid #fce7f3; flex-shrink: 0; position: relative; display: flex; align-items: center; justify-content: center; }
.momo-sum-thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 7px; }
.momo-qty-badge { position: absolute; top: -5px; right: -5px; background: #a50064; color: #fff; font-size: 0.65rem; font-weight: 700; width: 16px; height: 16px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.momo-sum-info { flex: 1; min-width: 0; }
.momo-sum-name { display: block; font-size: 0.82rem; font-weight: 600; color: #1f2937; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.momo-sum-meta { font-size: 0.72rem; color: #9ca3af; }
.momo-sum-price { font-size: 0.82rem; font-weight: 600; color: #374151; white-space: nowrap; }
.momo-sum-costs { border-top: 1px solid #f3f4f6; padding-top: 12px; margin-bottom: 14px; display: flex; flex-direction: column; gap: 8px; }
.momo-cost-row { display: flex; justify-content: space-between; font-size: 0.85rem; color: #4b5563; }
.momo-total-row { border-top: 1px dashed #fce7f3; padding-top: 8px; font-weight: 600; }
.momo-total-price { color: #a50064; font-size: 1.05rem; }
.momo-receiver { background: #fdf2f8; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; font-size: 0.83rem; }
.momo-receiver h4 { font-size: 0.85rem; font-weight: 700; color: #7e1450; margin: 0 0 8px; }
.momo-receiver p { margin: 3px 0; color: #4b5563; }
.momo-timer-box { display: flex; align-items: center; gap: 12px; background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; }
.momo-timer-box span { font-size: 1.4rem; }
.momo-timer-box strong { font-size: 0.8rem; color: #92400e; display: block; }
.momo-countdown { font-size: 1.6rem; font-weight: 800; color: #b45309; font-family: 'Courier New', monospace; }
.momo-countdown.urgent { color: #dc2626; animation: blink 1s infinite; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.5} }
.btn-momo-back { display: block; text-align: center; padding: 12px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; color: #374151; font-weight: 600; font-size: 0.88rem; text-decoration: none; transition: background 0.2s; }
.btn-momo-back:hover { background: #f3f4f6; }
@media (max-width: 900px) {
    .momo-pay-layout { grid-template-columns: 1fr; }
    .momo-summary-box { position: static; }
    .momo-qr-body { flex-direction: column; align-items: center; }
}
</style>

<script>
function copyMomoText(id, btn) {
    const el = document.getElementById(id);
    const text = el.innerText.trim();
    navigator.clipboard.writeText(text).then(() => {
        btn.textContent = '✓';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = '📋'; btn.classList.remove('copied'); }, 2000);
    });
}
function copyMomoAmount(amount, btn) {
    navigator.clipboard.writeText(String(amount)).then(() => {
        btn.textContent = '✓';
        btn.classList.add('copied');
        setTimeout(() => { btn.textContent = '📋'; btn.classList.remove('copied'); }, 2000);
    });
}
// Countdown 15 phút
(function() {
    let s = 15 * 60;
    const el = document.getElementById('momo-countdown');
    if (!el) return;
    const t = setInterval(() => {
        s--;
        const m = Math.floor(s / 60), sec = s % 60;
        el.textContent = String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
        if (s <= 180) el.classList.add('urgent');
        if (s <= 0) { clearInterval(t); el.textContent = '00:00'; }
    }, 1000);
})();
</script>
@endsection
