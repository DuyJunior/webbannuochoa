{{-- FIRST-VISIT DISCOUNT POPUP (cookie-based, shows once per session) --}}
@php
    $welcomeCoupon = \App\Models\Coupon::where('code', 'HATHUFIRST')->first();
    $showWelcomeCoupon = $welcomeCoupon && $welcomeCoupon->isAvailableFor((int) $welcomeCoupon->minimum_order);
@endphp
@if($showWelcomeCoupon)
<div id="ht-discount-popup" class="ht-popup-overlay" aria-modal="true" role="dialog" aria-labelledby="ht-popup-title" hidden>
    <div class="ht-popup-card">
        <button class="ht-popup-close" id="ht-popup-close" aria-label="Đóng">&times;</button>
        <div class="ht-popup-visual">
            <span class="ht-popup-icon">@include('partials.icon', ['name' => 'flower', 'size' => '1em'])</span>
            <div class="ht-popup-petals" aria-hidden="true">
                <span></span><span></span><span></span><span></span><span></span>
            </div>
        </div>
        <span class="ht-popup-eyebrow">CHÀO MỪNG BẠN ĐẾN VỚI</span>
        <h2 id="ht-popup-title" class="ht-popup-brand">Soopi<br><em>Perfume Studio</em></h2>
        <p class="ht-popup-sub">Ưu đãi <strong>{{ number_format($welcomeCoupon->value, 0, ',', '.') }}{{ $welcomeCoupon->type === 'percent' ? '%' : '₫' }}</strong> cho đơn hàng đủ điều kiện với mã:</p>
        <div class="ht-popup-code-wrap">
            <span class="ht-popup-code" id="ht-popup-code">{{ $welcomeCoupon->code }}</span>
            <button class="ht-popup-copy" id="ht-popup-copy" aria-label="Sao chép mã">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                <span>Sao chép</span>
            </button>
        </div>
        <p class="ht-popup-terms">Áp dụng cho đơn từ {{ number_format($welcomeCoupon->minimum_order, 0, ',', '.') }}₫@if($welcomeCoupon->expires_at) · Hết hạn {{ $welcomeCoupon->expires_at->format('d/m/Y') }} @endif</p>
        <a href="{{ route('home') }}#san-pham" class="ht-popup-cta" id="ht-popup-shop">
            Khám phá ngay →
        </a>
        <button class="ht-popup-skip" id="ht-popup-skip">Để sau, cảm ơn</button>
    </div>
</div>

<style>
/* ── DISCOUNT POPUP ── */
.ht-popup-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(50,25,35,.55);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    animation: ht-popup-bg-in .35s ease;
}
.ht-popup-overlay[hidden] { display: none !important; }
@keyframes ht-popup-bg-in { from { opacity: 0; } to { opacity: 1; } }
.ht-popup-card {
    background: #fff;
    border-radius: 24px;
    padding: 44px 40px 36px;
    max-width: 420px;
    width: 100%;
    text-align: center;
    position: relative;
    box-shadow: 0 30px 80px rgba(90,30,55,.22);
    animation: ht-popup-card-in .45s cubic-bezier(0.34,1.56,0.64,1) both;
    animation-delay: .1s;
    overflow: hidden;
}
@keyframes ht-popup-card-in {
    from { opacity: 0; transform: translateY(30px) scale(.94); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.ht-popup-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 5px;
    background: linear-gradient(90deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888, #c27090);
}
.ht-popup-close {
    position: absolute;
    top: 14px; right: 18px;
    background: #f5eaee;
    border: none;
    width: 30px; height: 30px;
    border-radius: 50%;
    font-size: 18px;
    line-height: 1;
    cursor: pointer;
    color: #9d7080;
    transition: background .15s, color .15s;
}
.ht-popup-close:hover { background: #f0d0dc; color: #6d3050; }
.ht-popup-visual { position: relative; margin-bottom: 10px; }
.ht-popup-icon {
    display: block;
    font-size: 44px;
    line-height: 1;
    animation: ht-pulse 2.5s ease-in-out infinite;
}
@keyframes ht-pulse { 0%,100% { transform: scale(1); } 50% { transform: scale(1.12); } }
.ht-popup-petals {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 90px; height: 90px;
    pointer-events: none;
}
.ht-popup-petals span {
    position: absolute;
    top: 50%; left: 50%;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #f0a8c0;
    opacity: .55;
    animation: ht-petal 3s ease-in-out infinite;
}
.ht-popup-petals span:nth-child(1){ transform: translate(-50%,-50%) rotate(0deg)   translateY(-38px); animation-delay: 0s; }
.ht-popup-petals span:nth-child(2){ transform: translate(-50%,-50%) rotate(72deg)  translateY(-38px); animation-delay: .6s; }
.ht-popup-petals span:nth-child(3){ transform: translate(-50%,-50%) rotate(144deg) translateY(-38px); animation-delay: 1.2s; }
.ht-popup-petals span:nth-child(4){ transform: translate(-50%,-50%) rotate(216deg) translateY(-38px); animation-delay: 1.8s; }
.ht-popup-petals span:nth-child(5){ transform: translate(-50%,-50%) rotate(288deg) translateY(-38px); animation-delay: 2.4s; }
@keyframes ht-petal { 0%,100% { opacity: .55; transform: translate(-50%,-50%) rotate(var(--r,0deg)) translateY(-38px) scale(1); } 50% { opacity: .8; transform: translate(-50%,-50%) rotate(var(--r,0deg)) translateY(-42px) scale(1.2); } }
.ht-popup-eyebrow {
    display: block;
    font-size: 10px;
    letter-spacing: .2em;
    color: #c27090;
    font-weight: 700;
    margin-bottom: 8px;
}
.ht-popup-brand {
    font: 400 clamp(26px,6vw,34px) Georgia, serif;
    color: #3b2c34;
    margin: 0 0 14px;
    line-height: 1.15;
}
.ht-popup-brand em { color: #c27090; }
.ht-popup-sub {
    color: #6b5660;
    font-size: 15.5px;
    margin-bottom: 18px;
    line-height: 1.5;
}
.ht-popup-sub strong { color: #c2476a; }
.ht-popup-code-wrap {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #fff0f5, #ffe8f0);
    border: 2px dashed #e8a0b8;
    border-radius: 12px;
    padding: 12px 18px;
    margin-bottom: 10px;
}
.ht-popup-code {
    font: 700 22px 'Courier New', monospace;
    letter-spacing: 3px;
    color: #c2476a;
}
.ht-popup-copy {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #c2476a;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s, transform .15s;
    white-space: nowrap;
}
.ht-popup-copy:hover { background: #a33558; transform: scale(1.04); }
.ht-popup-copy.copied { background: #2e7d32; }
.ht-popup-terms {
    font-size: 12px;
    color: #a08090;
    margin: 4px 0 22px;
}
.ht-popup-cta {
    display: block;
    background: linear-gradient(135deg, #c27090, #a8456a);
    color: #fff;
    text-decoration: none;
    border-radius: 12px;
    padding: 14px 24px;
    font-weight: 700;
    font-size: 15px;
    margin-bottom: 12px;
    transition: transform .18s, box-shadow .18s;
    box-shadow: 0 6px 20px rgba(194,112,144,.38);
}
.ht-popup-cta:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(194,112,144,.5); color: #fff; }
.ht-popup-skip {
    background: none;
    border: none;
    color: #a08090;
    font-size: 13px;
    cursor: pointer;
    text-decoration: underline;
    text-underline-offset: 3px;
    padding: 0;
}
.ht-popup-skip:hover { color: #6d3050; }
@media (max-width: 480px) {
    .ht-popup-card { padding: 36px 24px 28px; }
    .ht-popup-code { font-size: 18px; letter-spacing: 2px; }
}
</style>

<script>
(function () {
    var COOKIE = 'ht_discount_seen';
    var DELAY  = 4000; // ms before popup appears

    function getCookie(name) {
        var m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
        return m ? m.pop() : '';
    }
    function setCookie(name, value, days) {
        var expires = new Date();
        expires.setDate(expires.getDate() + days);
        document.cookie = name + '=' + value + '; path=/; expires=' + expires.toUTCString() + '; SameSite=Lax';
    }

    if (getCookie(COOKIE)) return; // Already seen

    var popup    = document.getElementById('ht-discount-popup');
    var closeBtn = document.getElementById('ht-popup-close');
    var skipBtn  = document.getElementById('ht-popup-skip');
    var copyBtn  = document.getElementById('ht-popup-copy');
    var shopBtn  = document.getElementById('ht-popup-shop');
    var codeEl   = document.getElementById('ht-popup-code');

    if (!popup) return;

    function closePopup() {
        popup.hidden = true;
        setCookie(COOKIE, '1', 30);
    }

    setTimeout(function () {
        popup.hidden = false;
    }, DELAY);

    closeBtn && closeBtn.addEventListener('click', closePopup);
    skipBtn  && skipBtn.addEventListener('click', closePopup);
    shopBtn  && shopBtn.addEventListener('click', closePopup);

    // Close on overlay click
    popup.addEventListener('click', function (e) {
        if (e.target === popup) closePopup();
    });

    // Copy code
    if (copyBtn && codeEl) {
        copyBtn.addEventListener('click', function () {
            var code = codeEl.textContent.trim();
            if (navigator.clipboard) {
                navigator.clipboard.writeText(code);
            } else {
                var ta = document.createElement('textarea');
                ta.value = code;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            copyBtn.classList.add('copied');
            copyBtn.querySelector('span').textContent = 'Đã sao chép!';
            setTimeout(function () {
                copyBtn.classList.remove('copied');
                copyBtn.querySelector('span').textContent = 'Sao chép';
            }, 2500);
        });
    }

    // ESC key close
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !popup.hidden) closePopup();
    });
})();
</script>
@endif
