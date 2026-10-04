<div class="sepay-compact" data-payment-qr data-qr-layout="compact">
    <img class="sepay-compact-brand" src="{{ asset('images/payments/vietqr-mark.png') }}" alt="VietQR" width="112" height="39">
    <div class="sepay-compact-code">
        <img src="{{ $qrUrl }}" alt="{{ __('Mã QR chuyển khoản ngân hàng') }}" width="360" height="360" referrerpolicy="no-referrer">
    </div>
    <div class="sepay-compact-network">
        <img class="sepay-compact-napas" src="{{ asset('images/payments/napas247-mark.png') }}" alt="NAPAS 247" width="436" height="90">
        <span class="sepay-compact-divider" aria-hidden="true"></span>
        @if (strcasecmp($receiver['bank'], 'BIDV') === 0)
            <img class="sepay-compact-bank" src="{{ asset('images/payments/bidv-mark.png') }}" alt="BIDV" width="831" height="311">
        @else
            <span class="sepay-compact-bank-name">{{ $receiver['bank'] }}</span>
        @endif
    </div>
</div>
<style>
.sepay-compact{box-sizing:border-box;width:320px;max-width:100%;margin:0 auto;padding:8px 10px;background:#fff;text-align:center}.sepay-compact[hidden]{display:none}.sepay-compact img{display:block;max-width:100%;height:auto;border-radius:0}.sepay-compact .sepay-compact-brand{width:112px;margin:0 auto 12px}.sepay-compact-code{width:272px;max-width:100%;margin:0 auto;border:1px solid #173b72;box-sizing:border-box}.sepay-compact-code img{width:100%;margin:0}.sepay-compact-network{display:flex;align-items:center;justify-content:center;gap:14px;margin-top:4px}.sepay-compact .sepay-compact-napas{width:98px;margin:0}.sepay-compact .sepay-compact-bank{width:112px;margin:0}.sepay-compact-divider{height:26px;width:1px;background:#173b72}.sepay-compact-bank-name{font-weight:700;color:#173b72}
</style>
