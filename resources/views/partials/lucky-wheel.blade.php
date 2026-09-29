{{-- VÒNG QUAY MAY MẮN NHẬN MÃ GIẢM GIÁ (LUCKY SPIN WHEEL) --}}
<div id="ht-lucky-spin-wrapper">
    <button type="button" id="ht-spin-floating-btn" title="Vòng quay hương thơm may mắn">
        <span class="spin-icon">@include('partials.icon', ['name' => 'wheel', 'size' => '1em'])</span>
        <span class="spin-label">Vòng Quay May Mắn</span>
    </button>

    {{-- Modal Vòng Quay --}}
    <div id="ht-spin-modal" class="spin-modal-backdrop" hidden>
        <button type="button" id="ht-spin-backdrop-close" class="spin-backdrop-close-btn" aria-label="Đóng vòng quay" title="Đóng vòng quay (Esc)">@include('partials.icon', ['name' => 'close', 'size' => '1em']) Đóng</button>
        <div class="spin-modal-card">
            <button type="button" id="ht-spin-close" class="spin-close-btn" aria-label="Đóng vòng quay" title="Đóng vòng quay (Esc)">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            {{-- 1. Phần Vòng Quay (Hiển thị ban đầu) --}}
            <div id="spinMainSection" class="spin-main-section">
                <div class="spin-card-header">
                    <span class="spin-eyebrow">@include('partials.icon', ['name' => 'flower', 'size' => '1em']) SOOPI PERFUME</span>
                    <h2>Vòng Quay <em>Hương Thơm</em></h2>
                    <p>Quay là trúng voucher giảm giá & quà tặng sample!</p>
                </div>

                <div class="spin-wheel-container" id="spinWheelContainer">
                    <div class="spin-pointer">▼</div>
                    <canvas id="htWheelCanvas" width="280" height="280"></canvas>
                    <button type="button" id="htSpinActionBtn" class="spin-center-btn" title="Bấm để quay ngay">
                        <span>QUAY<br>NGAY</span>
                    </button>
                </div>
            </div>

            {{-- 2. Phần Kết Quả Trúng Thưởng (Tự động thay thế vòng quay khi trúng, gọn gàng không tràn màn hình) --}}
            <div id="spinResultBox" class="spin-result-box" style="display:none;">
                <div class="result-confetti">@include('partials.icon', ['name' => 'party', 'size' => '1em']) @include('partials.icon', ['name' => 'gift', 'size' => '1em']) @include('partials.icon', ['name' => 'ribbon', 'size' => '1em'])</div>
                <h3 id="spinResultTitle">Chúc mừng bạn đã trúng!</h3>
                <p id="spinResultDesc">Mã ưu đãi đã sẵn sàng. Nhập mã này tại giỏ hàng để nhận ưu đãi:</p>
                
                <div class="spin-code-copy-row" id="spinCodeRow">
                    <span class="spin-code" id="spinRewardCode">SPIN50K</span>
                    <button type="button" class="ht-button ht-button-primary" id="copyRewardBtn">Sao chép mã</button>
                </div>

                <div class="spin-result-actions">
                    <a href="{{ route('home') }}#san-pham" class="ht-button ht-button-primary" id="applySpinShopBtn">@include('partials.icon', ['name' => 'bag', 'size' => '1em']) Mua Sắm Ngay</a>
                    <button type="button" class="ht-button ht-button-outline" id="closeAfterSpinBtn">@include('partials.icon', ['name' => 'close', 'size' => '1em']) Đóng lại</button>
                </div>

                <div class="mt-3">
                    <button type="button" id="spinBackToWheelBtn" class="btn-back-to-wheel">@include('partials.icon', ['name' => 'wheel', 'size' => '1em']) Xem lại vòng quay</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#ht-spin-floating-btn {
    position: fixed;
    bottom: 24px;
    left: 24px;
    z-index: 999990;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #be185d, #9d174d);
    color: #fff;
    border: none;
    border-radius: 30px;
    padding: 10px 18px;
    font-size: 13.5px;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(190, 24, 93, 0.45);
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s;
    animation: pulseWheel 3s ease-in-out infinite;
}
@keyframes pulseWheel {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
#ht-spin-floating-btn:hover {
    transform: scale(1.08) translateY(-2px);
    box-shadow: 0 12px 30px rgba(190, 24, 93, 0.6);
}
.spin-icon { font-size: 20px; animation: spinRotate 6s linear infinite; display: inline-block; }
@keyframes spinRotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Modal Backdrop: Căn giữa hoàn toàn màn hình, không bị tràn cuộn */
.spin-modal-backdrop {
    position: fixed;
    inset: 0;
    z-index: 999999;
    background: rgba(18, 9, 14, 0.78);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    animation: fadeInModal 0.25s ease;
}
.spin-modal-backdrop[hidden] { display: none !important; }
@keyframes fadeInModal {
    from { opacity: 0; }
    to { opacity: 1; }
}
.spin-backdrop-close-btn {
    position: fixed;
    top: 18px;
    right: 22px;
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.45);
    color: #ffffff;
    font-weight: 700;
    font-size: 13.5px;
    padding: 8px 18px;
    border-radius: 30px;
    cursor: pointer;
    z-index: 1000000;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35);
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}
.spin-backdrop-close-btn:hover {
    background: #be185d;
    border-color: #be185d;
    transform: scale(1.05);
}

/* Card gọn gàng, cố định chiều cao hợp lý */
.spin-modal-card {
    background: #ffffff;
    border-radius: 24px;
    max-width: 380px;
    width: 100%;
    margin: auto;
    padding: 22px 20px 18px;
    position: relative;
    box-shadow: 0 24px 60px rgba(0,0,0,0.35);
    text-align: center;
    border: 2px solid #fbcfe8;
    max-height: calc(100vh - 32px);
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.spin-close-btn {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #fdf2f8;
    border: 1.5px solid #fbcfe8;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9d174d;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 20;
    box-shadow: 0 2px 8px rgba(157, 23, 77, 0.15);
}
.spin-close-btn:hover {
    background: #be185d;
    color: #ffffff;
    border-color: #be185d;
    transform: rotate(90deg) scale(1.08);
}
.spin-card-header h2 {
    font: 600 clamp(18px, 3.5vw, 22px) 'Playfair Display', Georgia, serif;
    color: #2b1f26;
    margin: 2px 0 3px;
}
.spin-card-header h2 em { color: #c2476a; font-style: italic; }
.spin-card-header p {
    font-size: 12.5px;
    color: #715865;
    margin: 0 0 10px;
    line-height: 1.35;
}
.spin-eyebrow {
    font-size: 10px;
    letter-spacing: 1.5px;
    color: #c2476a;
    font-weight: 700;
}

/* Vòng quay thu gọn 280px */
.spin-wheel-container {
    position: relative;
    width: 280px;
    height: 280px;
    margin: 0 auto 4px;
}
#htWheelCanvas {
    width: 280px;
    height: 280px;
    border-radius: 50%;
    box-shadow: 0 8px 24px rgba(194, 71, 106, 0.22);
    border: 5px solid #fff0f5;
    display: block;
}
.spin-pointer {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 20px;
    color: #be185d;
    z-index: 10;
    text-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.spin-center-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 62px;
    height: 62px;
    border-radius: 50%;
    background: linear-gradient(135deg, #db2777, #9d174d);
    color: #fff;
    border: 3.5px solid #fff;
    box-shadow: 0 4px 14px rgba(157, 23, 77, 0.4);
    font: 700 11px/1.2 sans-serif;
    cursor: pointer;
    transition: transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 5;
}
.spin-center-btn:hover {
    transform: translate(-50%, -50%) scale(1.08);
}

/* Khung kết quả thay thế vòng quay gọn gàng */
.spin-result-box {
    background: #fff0f5;
    border: 2px dashed #f472b6;
    border-radius: 18px;
    padding: 16px 14px;
    animation: popIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes popIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
.result-confetti {
    font-size: 1.5rem;
    margin-bottom: 2px;
}
.spin-result-box h3 {
    font: 600 18px 'Playfair Display', Georgia, serif;
    color: #be185d;
    margin: 4px 0 4px;
}
.spin-result-box p {
    font-size: 12.5px;
    color: #4a3540;
    margin: 0 0 10px;
    line-height: 1.4;
}
.spin-code-copy-row {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 10px;
}
.spin-code {
    font: 700 16px 'Courier New', monospace;
    letter-spacing: 1.5px;
    color: #be185d;
    background: #fff;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid #fbcfe8;
    display: flex;
    align-items: center;
}
.spin-result-actions {
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
    margin-top: 10px;
    flex-wrap: wrap;
}
.spin-result-actions .ht-button-primary {
    background: linear-gradient(135deg, #be185d, #9d174d);
    color: #fff;
    padding: 9px 18px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 13px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(190, 24, 93, 0.35);
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: none;
    cursor: pointer;
}
.spin-result-actions .ht-button-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(190, 24, 93, 0.45);
}
.spin-result-actions .ht-button-outline {
    background: #fff;
    border: 1.5px solid #d1d5db;
    color: #4b5563;
    padding: 9px 16px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}
.spin-result-actions .ht-button-outline:hover {
    background: #f3f4f6;
    color: #111827;
}
.btn-back-to-wheel {
    background: none;
    border: none;
    color: #be185d;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: underline;
    opacity: 0.85;
}
.btn-back-to-wheel:hover {
    opacity: 1;
}
</style>

<script>
(function () {
    const rewards = [
        { label: 'Giảm 50.000₫', code: 'SPIN50K', color: '#fbcfe8', textColor: '#831843' },
        { label: 'Freeship 30K', code: 'FREESHIP', color: '#fce7f3', textColor: '#9d174d' },
        { label: 'Giảm 10% Đơn', code: 'SPIN10', color: '#fda4af', textColor: '#881337' },
        { label: 'Sample 5ml', code: 'SPIN50K', color: '#fdf2f8', textColor: '#9f1239' },
        { label: 'Giảm 100.000₫', code: 'SPIN100K', color: '#f472b6', textColor: '#ffffff' },
        { label: 'Chúc may mắn', code: '', color: '#fce7f3', textColor: '#701a75' },
        { label: 'Giảm 50.000₫', code: 'SPIN50K', color: '#fbcfe8', textColor: '#831843' },
        { label: 'Freeship 30K', code: 'FREESHIP', color: '#fda4af', textColor: '#881337' },
    ];

    const canvas = document.getElementById('htWheelCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const totalSegments = rewards.length;
    const arc = (2 * Math.PI) / totalSegments;
    let currentAngle = 0;
    let isSpinning = false;

    function drawWheel() {
        const cx = canvas.width / 2;
        const cy = canvas.height / 2;
        const radius = cx - 8;

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        rewards.forEach((r, i) => {
            const angle = currentAngle + i * arc;
            ctx.beginPath();
            ctx.fillStyle = r.color;
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, radius, angle, angle + arc);
            ctx.fill();
            ctx.strokeStyle = '#fff';
            ctx.lineWidth = 2;
            ctx.stroke();

            // Text
            ctx.save();
            ctx.translate(cx, cy);
            ctx.rotate(angle + arc / 2);
            ctx.textAlign = 'right';
            ctx.fillStyle = r.textColor;
            ctx.font = 'bold 11px sans-serif';
            ctx.fillText(r.label, radius - 14, 4);
            ctx.restore();
        });
    }

    drawWheel();

    // Elements
    const floatBtn = document.getElementById('ht-spin-floating-btn');
    const modal = document.getElementById('ht-spin-modal');
    const closeBtn = document.getElementById('ht-spin-close');
    const backdropCloseBtn = document.getElementById('ht-spin-backdrop-close');
    const closeAfterSpinBtn = document.getElementById('closeAfterSpinBtn');
    const applyShopBtn = document.getElementById('applySpinShopBtn');
    const spinActionBtn = document.getElementById('htSpinActionBtn');
    const mainSection = document.getElementById('spinMainSection');
    const resultBox = document.getElementById('spinResultBox');
    const resultTitle = document.getElementById('spinResultTitle');
    const resultDesc = document.getElementById('spinResultDesc');
    const rewardCode = document.getElementById('spinRewardCode');
    const copyBtn = document.getElementById('copyRewardBtn');
    const codeRow = document.getElementById('spinCodeRow');
    const backToWheelBtn = document.getElementById('spinBackToWheelBtn');

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
    }

    function openModal() {
        if (!modal) return;
        modal.hidden = false;
        // Mặc định hiện phần vòng quay
        if (mainSection) mainSection.style.display = 'block';
        if (resultBox) resultBox.style.display = 'none';
    }

    function showWheelView() {
        if (mainSection) mainSection.style.display = 'block';
        if (resultBox) resultBox.style.display = 'none';
    }

    function showResultView() {
        if (mainSection) mainSection.style.display = 'none';
        if (resultBox) resultBox.style.display = 'block';
    }

    floatBtn && floatBtn.addEventListener('click', openModal);
    closeBtn && closeBtn.addEventListener('click', closeModal);
    backdropCloseBtn && backdropCloseBtn.addEventListener('click', closeModal);
    closeAfterSpinBtn && closeAfterSpinBtn.addEventListener('click', closeModal);
    backToWheelBtn && backToWheelBtn.addEventListener('click', showWheelView);

    // Mua Sắm Ngay -> đóng modal và cuộn mượt đến phần sản phẩm
    applyShopBtn && applyShopBtn.addEventListener('click', function (e) {
        closeModal();
        const sanPhamEl = document.getElementById('san-pham');
        if (sanPhamEl) {
            e.preventDefault();
            sanPhamEl.scrollIntoView({ behavior: 'smooth' });
            try { history.pushState(null, null, '#san-pham'); } catch (err) {}
        }
    });

    // Bấm ra ngoài backdrop -> đóng modal
    modal && modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    // Bấm phím Escape -> đóng modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal && !modal.hidden) {
            closeModal();
        }
    });

    // Spin animation
    spinActionBtn && spinActionBtn.addEventListener('click', function () {
        if (isSpinning) return;
        isSpinning = true;

        // Choose winning segment (favor a real voucher: 0, 1, 2, 4)
        const winIndex = [0, 1, 2, 4, 6][Math.floor(Math.random() * 5)];
        const stopAngle = (totalSegments - winIndex) * arc - (arc / 2) - (Math.PI / 2);
        const extraRounds = 5 + Math.floor(Math.random() * 3);
        const targetAngle = currentAngle + (extraRounds * 2 * Math.PI) + (stopAngle - (currentAngle % (2 * Math.PI)));

        const duration = 3600;
        const startTime = performance.now();
        const startAngle = currentAngle;

        function animate(now) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);
            // Ease out cubic
            const ease = 1 - Math.pow(1 - progress, 3);
            currentAngle = startAngle + (targetAngle - startAngle) * ease;
            drawWheel();

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                isSpinning = false;
                const reward = rewards[winIndex];
                if (reward.code) {
                    resultTitle.textContent = ' Bạn Đã Trúng ' + reward.label + '!';
                    resultDesc.textContent = 'Mã ưu đãi đã sẵn sàng. Nhập mã này tại giỏ hàng để nhận giảm giá ngay:';
                    rewardCode.textContent = reward.code;
                    codeRow.style.display = 'flex';
                } else {
                    resultTitle.textContent = 'Chúc bạn may mắn lần sau!';
                    resultDesc.textContent = 'Đừng buồn nhé, bạn có thể quay lại vào ngày mai!';
                    codeRow.style.display = 'none';
                }
                // Chuyển sang khung kết quả gọn gàng ngay tại chỗ sau khi quay xong 600ms
                setTimeout(() => {
                    showResultView();
                }, 600);
            }
        }

        requestAnimationFrame(animate);
    });

    copyBtn && copyBtn.addEventListener('click', function () {
        const c = rewardCode.textContent.trim();
        if (navigator.clipboard) navigator.clipboard.writeText(c);
        this.textContent = 'Đã chép!';
        setTimeout(() => { this.textContent = 'Sao chép mã'; }, 2000);
    });
})();
</script>
