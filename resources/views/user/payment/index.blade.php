@extends('layouts.store')

@section('title', 'Thanh toán & Giao hàng GHN · Soopi')

@section('content')
<div class="checkout-page-wrapper">
    <div class="store-container">
        {{-- Breadcrumb --}}
        <nav class="checkout-breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a>
            <span>/</span>
            <a href="{{ route('cart.index') }}">Giỏ hàng</a>
            <span>/</span>
            <span class="active">Thanh toán & Vận chuyển GHN</span>
        </nav>

        <div class="checkout-header-title">
            <span class="badge-tag">Soopi Delivery</span>
            <h1>Hoàn tất đơn hàng</h1>
            <p>Tính cước phí vận chuyển chính xác thời gian thực qua Giao Hàng Nhanh (GHN)</p>
        </div>

        @if ($errors->any())
            <div class="checkout-alert-error">
                <strong>Đã có lỗi xảy ra:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('payment.process') }}" id="checkoutPaymentForm" class="checkout-grid-container">
            @csrf
            <input type="hidden" name="checkout_key" value="{{ old('checkout_key', (string) \Illuminate\Support\Str::uuid()) }}">
            <input type="hidden" id="total_price_input" value="{{ $totalPrice }}">

            {{-- Cột Trái: Thông tin nhận hàng & Địa chỉ GHN --}}
            <div class="checkout-col-main">
                <div class="checkout-card">
                    <div class="card-section-header">
                        <div class="icon-circle">1</div>
                        <div>
                            <h2>Thông tin người nhận</h2>
                            <small>Vui lòng cung cấp chính xác để nhân viên giao hàng liên hệ</small>
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group-item">
                            <label for="name">Họ và tên người nhận <span class="req">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" required placeholder="Ví dụ: Nguyễn Thị Thu Hà">
                        </div>

                        <div class="form-group-item">
                            <label for="phone">Số điện thoại <span class="req">*</span></label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" required maxlength="10" minlength="10" pattern="0[0-9]{9}" title="Số điện thoại phải gồm đúng 10 chữ số (bắt đầu bằng số 0)" placeholder="Ví dụ: 0912345678" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                            @error('phone')
                                <span class="text-danger" style="font-size: 12px; color: #ef4444; margin-top: 4px; display: block;">{{ $message }}</span>
                            @else
                                <small class="field-hint" style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">Số điện thoại chỉ được đúng 10 chữ số (bắt đầu bằng số 0)</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="checkout-card">
                    <div class="card-section-header">
                        <div class="icon-circle">2</div>
                        <div>
                            <h2>Địa chỉ giao hàng (GHN)</h2>
                            <small>Chọn khu vực để hệ thống kết nối GHN tính phí tự động</small>
                        </div>
                    </div>

                    <div class="ghn-partner-banner">
                        <div class="ghn-badge-logo">
                            <span class="truck-icon">@include('partials.icon', ['name' => 'truck', 'size' => '1em'])</span>
                            <strong>Giao Hàng Nhanh (GHN Express)</strong>
                        </div>
                        <span class="ghn-status-live">● Kết nối API trực tiếp</span>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group-item">
                            <label for="province_select">Tỉnh / Thành phố <span class="req">*</span></label>
                            <select id="province_select" class="form-select" required>
                                <option value="">-- Đang tải Tỉnh/Thành... --</option>
                            </select>
                        </div>

                        <div class="form-group-item">
                            <label for="district_select">Quận / Huyện <span class="req">*</span></label>
                            <select id="district_select" name="to_district_id" class="form-select" required disabled>
                                <option value="">-- Chọn Tỉnh/Thành trước --</option>
                            </select>
                        </div>

                        <div class="form-group-item">
                            <label for="ward_select">Phường / Xã <span class="req">*</span></label>
                            <select id="ward_select" name="to_ward_code" class="form-select" required disabled>
                                <option value="">-- Chọn Quận/Huyện trước --</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group-item" style="margin-top: 16px;">
                        <label for="address">Địa chỉ chi tiết (Số nhà, tên đường, ngõ ngách) <span class="req">*</span></label>
                        <textarea id="address" name="address" rows="2" required placeholder="Ví dụ: Số 18, Ngõ 45 Đường Láng">{{ old('address') }}</textarea>
                    </div>

                    <div class="form-group-item" style="margin-top: 14px;">
                        <label for="note">Ghi chú đơn hàng (Tùy chọn)</label>
                        <input type="text" id="note" name="note" value="{{ old('note') }}" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi đến...">
                    </div>
                </div>

                {{-- Dịch Vụ Gói Quà Cao Cấp & Thiệp Chúc Mừng --}}
                <div class="checkout-card" id="giftServiceCard">
                    <div class="card-section-header">
                        <div class="icon-circle">@include('partials.icon', ['name' => 'gift', 'size' => '1em'])</div>
                        <div>
                            <h2>Dịch Vụ Gói Quà Cao Cấp & Thiệp Chúc Mừng</h2>
                            <small>Món quà hoàn hảo trao tận tay người thương</small>
                        </div>
                    </div>

                    <div style="background: #fff8fb; border: 1.5px solid #fbcfe8; border-radius: 14px; padding: 16px; margin-bottom: 16px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; color: #be185d; font-size: 15px;">
                            <input type="checkbox" id="giftWrapToggle" name="enable_gift_service" value="1" style="width: 18px; height: 18px; accent-color: #be185d;">
                            <span>Yêu cầu Gói Quà Cao Cấp & Thiệp Chúc Mừng</span>
                        </label>
                        <p style="margin: 4px 0 0 28px; font-size: 13px; color: #715865;">Đóng hộp cứng cao cấp kèm nơ lụa, xịt hương thơm tinh tế trước khi đóng gói.</p>
                    </div>

                    <div id="giftOptionsBox" style="display: none; animation: fadeInGift 0.3s ease;">
                        <div class="form-row-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                            <div class="form-group-item">
                                <label for="gift_wrap">Mẫu giấy & hộp quà:</label>
                                <select id="gift_wrap" name="gift_wrap" class="form-select">
                                    <option value="Nhung đỏ rượu vang (Wine Velvet)">Nhung đỏ rượu vang quý phái</option>
                                    <option value="Giấy Kraft Paris cổ điển (Vintage Kraft)">Giấy Kraft Paris vintage mộc mạc</option>
                                    <option value="Lụa hồng phấn kiêu kỳ (Blush Pink)">Lụa hồng phấn ngọt ngào</option>
                                    <option value="Đen huyền bí sang trọng (Midnight Black)">Đen huyền bí sang trọng</option>
                                </select>
                            </div>
                            <div class="form-group-item">
                                <label for="gift_card">Mẫu thiệp chúc mừng:</label>
                                <select id="gift_card" name="gift_card" class="form-select">
                                    <option value="Sinh nhật (Happy Birthday)">Sinh nhật (Happy Birthday)</option>
                                    <option value="Kỷ niệm (Happy Anniversary)">Kỷ niệm (Happy Anniversary)</option>
                                    <option value="Tình yêu (With Love)">Tình yêu ngọt ngào (With Love)</option>
                                    <option value="Tri ân & Cảm ơn (Thank You)">Tri ân & Cảm ơn (Thank You)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group-item" style="margin-bottom: 14px;">
                            <label for="gift_message">Lời nhắn chúc mừng (in lên thiệp):</label>
                            <textarea id="gift_message" name="gift_message" rows="2" placeholder="Ví dụ: Chúc em sinh nhật vui vẻ, luôn rạng rỡ và ngát hương thơm mỗi ngày nhé!"></textarea>
                        </div>

                        <div class="form-group-item">
                            <label for="gift_delivery_date">Ngày giao mong muốn (để nhận đúng ngày đặc biệt):</label>
                            <input type="date" id="gift_delivery_date" name="gift_delivery_date" min="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                </div>

                <div class="checkout-card">
                    <div class="card-section-header">
                        <div class="icon-circle">3</div>
                        <div>
                            <h2>Phương thức thanh toán</h2>
                            <small>Đơn giản, an toàn và tiện lợi</small>
                        </div>
                    </div>

                    <div class="payment-method-box">
                        {{-- 1. Thanh toán COD --}}
                        <div class="payment-option selected" id="label_cod" onclick="choosePayment('cod')">
                            <input type="radio" name="_payment_choice" value="cod" id="radio_cod" checked>
                            <div class="payment-option-body">
                                <div class="opt-title">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 1.25rem;">@include('partials.icon', ['name' => 'cash', 'size' => '1em'])</span>
                                        <strong>Thanh toán khi nhận hàng (COD)</strong>
                                    </div>
                                    <span class="badge-popular">Phổ biến</span>
                                </div>
                                <p>Nhận hàng, kiểm tra tem seal nước hoa chính hãng trước khi thanh toán cho shipper GHN.</p>
                            </div>
                        </div>

                        {{-- 2. Thanh toán MoMo (Theo đúng tài liệu Lab) --}}
                        <div class="payment-option" id="label_momo" onclick="choosePayment('momo')">
                            <input type="radio" name="_payment_choice" value="momo" id="radio_momo">
                            <div class="payment-option-body">
                                <div class="opt-title">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <img src="{{ asset('images/payments/momo.svg') }}" alt="MoMo" style="height: 22px; width: 22px; object-fit: contain; border-radius: 4px;">
                                        <strong>Cổng thanh toán MoMo (Online)</strong>
                                    </div>
                                    <span class="badge-popular" style="background: #a50064; color: #fff;">MoMo Sandbox</span>
                                </div>
                                <p>Chuyển hướng đến cổng thanh toán MoMo: Hỗ trợ quét mã QR MoMo, Thẻ ATM Nội Địa (Napas) & Thẻ Quốc Tế.</p>

                                <div class="momo-feature-tags" style="display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap;">
                                    <span style="font-size: 0.76rem; background: #fdf2f8; color: #db2777; border: 1px solid #fbcfe8; padding: 4px 10px; border-radius: 6px; font-weight: 600;">@include('partials.icon', ['name' => 'phone', 'size' => '1em']) Ví MoMo QR</span>
                                    <span style="font-size: 0.76rem; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 4px 10px; border-radius: 6px; font-weight: 600;">@include('partials.icon', ['name' => 'bank', 'size' => '1em']) Thẻ ATM Nội Địa (Napas)</span>
                                    <span style="font-size: 0.76rem; background: #fefce8; color: #854d0e; border: 1px solid #fef08a; padding: 4px 10px; border-radius: 6px; font-weight: 600;">@include('partials.icon', ['name' => 'card', 'size' => '1em']) Thẻ Quốc Tế (Visa/Master)</span>
                                </div>
                            </div>
                        </div>



                        {{-- Hidden input duy nhất gửi phương thức thanh toán lên server (cod hoặc momo theo lab) --}}
                        <input type="hidden" name="payment_method" id="selected_payment_method" value="cod">
                    </div>
                </div>
            </div>

            {{-- Cột Phải: Tóm tắt đơn hàng & Phí ship GHN --}}
            <div class="checkout-col-side">
                <div class="order-summary-box">
                    <h3 class="summary-box-title">Đơn hàng của bạn</h3>

                    <div class="summary-items-list">
                        @if(isset($cartItems) && count($cartItems) > 0)
                            @foreach ($cartItems as $item)
                                <div class="summary-item-row">
                                    <div class="item-thumb-box">
                                        @if($item['product']?->image_src)
                                            <img src="{{ $item['product']->image_src }}" alt="{{ $item['product']->name }}">
                                        @else
                                            <div class="thumb-placeholder">@include('partials.icon', ['name' => 'flower', 'size' => '1em'])</div>
                                        @endif
                                        <span class="item-qty-badge">{{ $item['quantity'] }}</span>
                                    </div>
                                    <div class="item-details">
                                        <h4 class="item-title">{{ $item['product']?->name }}</h4>
                                        <span class="item-meta">{{ $item['volume_ml'] }}ml · {{ $item['weight'] ?? 200 }}g</span>
                                        <div class="item-price">{{ number_format($item['price'], 0, ',', '.') }}₫</div>
                                    </div>
                                    <div class="item-subtotal">
                                        {{ number_format($item['total'], 0, ',', '.') }}₫
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <div class="summary-cost-breakdown">
                        <div class="cost-row">
                            <span>Tiền hàng</span>
                            <strong id="subtotal_text">{{ number_format($totalPrice, 0, ',', '.') }} VNĐ</strong>
                        </div>
                        <div class="cost-row">
                            <span>Tổng khối lượng tính phí</span>
                            <strong style="color: #db2777; font-weight: 700;">{{ isset($totalWeight) ? $totalWeight : 200 }} g</strong>
                        </div>
                        <div class="ht-checkout-promos">
                            <label>Mã ưu đãi<input name="coupon_code" value="{{ old('coupon_code') }}" placeholder="Nhập mã nếu có" maxlength="30"></label>
                            @if($availableCoupons->isNotEmpty())<small>Mã hiện có: @foreach($availableCoupons as $coupon)<strong>{{ $coupon->code }}</strong>{{ !$loop->last ? ', ' : '' }}@endforeach</small>@endif
                            <label>Điểm thành viên muốn dùng<input name="points_used" type="number" min="0" max="{{ min($loyaltyBalance, floor($totalPrice * .2 / 1000)) }}" value="{{ old('points_used', 0) }}"></label>
                            <small>Bạn có {{ $loyaltyBalance }} điểm · 1 điểm giảm 1.000₫ · tối đa 20% tiền hàng. Ưu đãi được xác nhận khi đặt hàng.</small>
                            @error('coupon_code')<span class="text-danger">{{ $message }}</span>@enderror
                            @error('points_used')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="cost-row"><span>Ưu đãi dự tính</span><strong id="discount_preview">0 VNĐ</strong></div>
                        <div class="cost-row shipping-row">
                            <span>Cước vận chuyển GHN</span>
                            <strong id="shipping_fee_text" class="fee-waiting">-- Chọn địa chỉ --</strong>
                        </div>
                        <div class="cost-row total-highlight">
                            <span>Tổng thanh toán</span>
                            <strong id="final_total_text" class="final-price">{{ number_format($totalPrice, 0, ',', '.') }} VNĐ</strong>
                        </div>
                    </div>

                    <button type="submit" class="btn-confirm-checkout" id="btnSubmitPayment">
                        <span>XÁC NHẬN ĐẶT HÀNG</span>
                        <span class="btn-icon">→</span>
                    </button>

                    <div class="safe-checkout-badge">
                        <span>@include('partials.icon', ['name' => 'lock', 'size' => '1em']) Bảo mật thông tin đặt hàng · Cam kết chính hãng 100%</span>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
/* Checkout Page Luxury Styling */
.checkout-page-wrapper {
    padding: 32px 0 80px;
    background: #faf8f9;
    min-height: 80vh;
}
.checkout-breadcrumb {
    display: flex;
    gap: 8px;
    font-size: 0.85rem;
    color: #6b7280;
    margin-bottom: 24px;
}
.checkout-breadcrumb a {
    color: #4b5563;
    text-decoration: none;
    transition: color 0.2s;
}
.checkout-breadcrumb a:hover {
    color: #db2777;
}
.checkout-breadcrumb .active {
    color: #db2777;
    font-weight: 600;
}
.checkout-header-title {
    margin-bottom: 30px;
}
.checkout-header-title .badge-tag {
    display: inline-block;
    background: #fce7f3;
    color: #be185d;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    padding: 4px 10px;
    border-radius: 9999px;
    margin-bottom: 8px;
    text-transform: uppercase;
}
.checkout-header-title h1 {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 2.2rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 6px 0;
}
.checkout-header-title p {
    color: #6b7280;
    font-size: 0.95rem;
    margin: 0;
}

.checkout-alert-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
}
.checkout-alert-error ul {
    margin: 8px 0 0 18px;
    padding: 0;
}

.checkout-grid-container {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 32px;
    align-items: flex-start;
}

.checkout-col-main {
    display: flex;
    flex-direction: column;
    gap: 24px;
}
.checkout-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 26px;
    border: 1px solid #f3e8ee;
    box-shadow: 0 4px 20px rgba(219, 39, 119, 0.04);
}
.card-section-header {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
    padding-bottom: 14px;
    border-bottom: 1px solid #fdf2f8;
}
.card-section-header .icon-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #fdf2f8;
    color: #db2777;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    border: 1px solid #fbcfe8;
}
.card-section-header h2 {
    font-size: 1.15rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}
.card-section-header small {
    color: #6b7280;
    font-size: 0.82rem;
}

.ghn-partner-banner {
    background: #fff7ed;
    border: 1px solid #ffedd5;
    border-radius: 10px;
    padding: 12px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}
.ghn-badge-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #c2410c;
    font-size: 0.9rem;
}
.ghn-status-live {
    font-size: 0.78rem;
    font-weight: 600;
    color: #16a34a;
}

.form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.form-row-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 14px;
}
.form-group-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.form-group-item label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #374151;
}
.form-group-item .req {
    color: #e11d48;
}
.form-group-item input,
.form-group-item select,
.form-group-item textarea {
    width: 100%;
    padding: 11px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9rem;
    background: #ffffff;
    transition: all 0.2s;
    outline: none;
    box-sizing: border-box;
}
.form-group-item input:focus,
.form-group-item select:focus,
.form-group-item textarea:focus {
    border-color: #f472b6;
    box-shadow: 0 0 0 3px rgba(244, 114, 182, 0.18);
}
.form-group-item select:disabled {
    background: #f9fafb;
    color: #9ca3af;
    cursor: not-allowed;
}

.payment-method-box {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.payment-option {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 20px;
    border: 1.5px solid #fce7f3;
    background: #ffffff;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    width: 100%;
    box-sizing: border-box;
}
.payment-option.selected {
    border: 1.5px solid #db2777;
    background: #ffffff;
    box-shadow: 0 4px 16px rgba(219, 39, 119, 0.08);
}
.payment-option input[type="radio"] {
    width: 20px !important;
    min-width: 20px !important;
    max-width: 20px !important;
    height: 20px !important;
    min-height: 20px !important;
    padding: 0 !important;
    margin: 2px 0 0 0 !important;
    background: transparent !important;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    flex: 0 0 20px !important;
    flex-shrink: 0 !important;
    flex-grow: 0 !important;
    accent-color: #db2777;
    cursor: pointer;
}
.payment-option-body {
    flex: 1 1 auto;
    min-width: 0;
    width: 100%;
}
.payment-option-body .opt-title {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 4px;
}
.payment-option-body strong {
    font-size: 0.95rem;
    color: #111827;
}
.badge-popular {
    background: #fce7f3;
    color: #db2777;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
}
.payment-option-body p {
    font-size: 0.83rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
}

/* ATM Payment Badges */
.badge-atm-domestic {
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
    white-space: nowrap;
}
.badge-atm-international {
    background: #fef3c7;
    color: #92400e;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 9999px;
    white-space: nowrap;
}

/* Bank Logo Chips */
.atm-bank-logos {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}
.bank-chip {
    font-size: 0.7rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    letter-spacing: 0.03em;
}
.bank-chip.vcb  { background: #dcfce7; color: #166534; }
.bank-chip.bidv { background: #fee2e2; color: #991b1b; }
.bank-chip.agr  { background: #fef9c3; color: #713f12; }
.bank-chip.tcb  { background: #fce7f3; color: #9d174d; }
.bank-chip.mb   { background: #ede9fe; color: #4c1d95; }
.bank-chip.visa { background: #1a1f71; color: #fff; }
.bank-chip.master { background: #eb001b; color: #fff; }
.bank-chip.jcb  { background: #003087; color: #fff; }
.bank-chip.amex { background: #2e77bc; color: #fff; }



/* Order Summary Sidebar */
.order-summary-box {
    background: #ffffff;
    border-radius: 16px;
    padding: 24px;
    border: 1px solid #f3e8ee;
    box-shadow: 0 6px 24px rgba(219, 39, 119, 0.05);
    position: sticky;
    top: 90px;
}
.summary-box-title {
    font-size: 1.15rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 18px 0;
    padding-bottom: 12px;
    border-bottom: 1px solid #f3f4f6;
}
.summary-items-list {
    max-height: 240px;
    overflow-y: auto;
    margin-bottom: 18px;
    padding-right: 4px;
}
.summary-item-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px dashed #f3f4f6;
}
.item-thumb-box {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    border: 1px solid #f3e8ee;
    position: relative;
    background: #fafafa;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.item-thumb-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 7px;
}
.thumb-placeholder {
    font-size: 1.2rem;
}
.item-qty-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #db2777;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.item-details {
    flex: 1;
    min-width: 0;
}
.item-title {
    font-size: 0.88rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 2px 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.item-meta {
    font-size: 0.75rem;
    color: #9ca3af;
}
.item-price {
    font-size: 0.78rem;
    color: #6b7280;
}
.item-subtotal {
    font-size: 0.88rem;
    font-weight: 600;
    color: #1f2937;
}

.summary-cost-breakdown {
    padding: 14px 0;
    border-top: 1px solid #f3f4f6;
    border-bottom: 1px solid #f3f4f6;
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.cost-row {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    color: #4b5563;
}
.cost-row strong {
    color: #111827;
}
.fee-waiting {
    color: #f59e0b !important;
    font-size: 0.85rem;
}
.total-highlight {
    padding-top: 8px;
    border-top: 1px dashed #e5e7eb;
    font-size: 1.05rem;
    font-weight: 700;
}
.total-highlight .final-price {
    color: #db2777;
    font-size: 1.2rem;
}

.btn-confirm-checkout {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #f472b6 0%, #db2777 100%);
    color: #ffffff;
    border: none;
    border-radius: 12px;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(219, 39, 119, 0.28);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.25s ease;
}
.btn-confirm-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(219, 39, 119, 0.38);
}
.safe-checkout-badge {
    margin-top: 14px;
    text-align: center;
    font-size: 0.76rem;
    color: #9ca3af;
}

@media (max-width: 900px) {
    .checkout-grid-container {
        grid-template-columns: 1fr;
    }
    .form-row-2, .form-row-3 {
        grid-template-columns: 1fr;
    }
    .order-summary-box {
        position: static;
    }
}
</style>

{{-- Đoạn script GHN bắt buộc theo tài liệu hướng dẫn (Trang 18 - 22) --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const giftWrapToggle = document.getElementById('giftWrapToggle');
    const giftOptionsBox = document.getElementById('giftOptionsBox');
    if (giftWrapToggle && giftOptionsBox) {
        giftWrapToggle.addEventListener('change', function () {
            giftOptionsBox.style.display = this.checked ? 'block' : 'none';
        });
    }
});

function choosePayment(method) {
    const radioCod = document.getElementById('radio_cod');
    const radioMomo = document.getElementById('radio_momo');
    const labelCod = document.getElementById('label_cod');
    const labelMomo = document.getElementById('label_momo');
    const selectedInput = document.getElementById('selected_payment_method');

    if (method === 'momo') {
        if (radioMomo) radioMomo.checked = true;
        if (radioCod) radioCod.checked = false;
        if (labelMomo) labelMomo.classList.add('selected');
        if (labelCod) labelCod.classList.remove('selected');
        if (selectedInput) selectedInput.value = 'momo';
    } else {
        if (radioCod) radioCod.checked = true;
        if (radioMomo) radioMomo.checked = false;
        if (labelCod) labelCod.classList.add('selected');
        if (labelMomo) labelMomo.classList.remove('selected');
        if (selectedInput) selectedInput.value = 'cod';
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const provinceSelect = document.getElementById('province_select');
    const districtSelect = document.getElementById('district_select');
    const wardSelect = document.getElementById('ward_select');
    const shippingFeeText = document.getElementById('shipping_fee_text');
    const finalTotalText = document.getElementById('final_total_text');
    const totalPriceInput = document.getElementById('total_price_input');

    const districtsUrl = "{{ route('locations.districts', ['provinceId' => '__PROVINCE__']) }}";
    const wardsUrl = "{{ route('locations.wards', ['districtId' => '__DISTRICT__']) }}";

    // Lấy tiền hàng an toàn từ input ẩn
    const subtotal = parseInt(totalPriceInput ? totalPriceInput.value : 0) || 0;

    @php($couponPreview = $availableCoupons->map(fn ($coupon) => ['code' => $coupon->code, 'type' => $coupon->type, 'value' => $coupon->value])->values())
    const coupons = {{ Illuminate\Support\Js::from($couponPreview) }};
    const couponInput = document.querySelector('[name="coupon_code"]');
    const pointsInput = document.querySelector('[name="points_used"]');
    const discountPreview = document.getElementById('discount_preview');
    let currentShippingFee = 0;
    function renderTotal() {
        const coupon = coupons.find(item => item.code === (couponInput?.value || '').trim().toUpperCase());
        const couponDiscount = coupon ? Math.min(subtotal, coupon.type === 'percent' ? Math.floor(subtotal * coupon.value / 100) : coupon.value) : 0;
        const maxPoints = Math.min({{ $loyaltyBalance }}, Math.floor((subtotal - couponDiscount) * 0.2 / 1000));
        const points = Math.min(maxPoints, Math.max(0, parseInt(pointsInput?.value || '0', 10) || 0));
        const savings = couponDiscount + points * 1000;
        discountPreview.textContent = savings ? '- ' + new Intl.NumberFormat('vi-VN').format(savings) + ' VNĐ' : '0 VNĐ';
        const finalAmount = Math.max(0, subtotal + currentShippingFee - savings);
        finalTotalText.textContent = new Intl.NumberFormat('vi-VN').format(finalAmount) + ' VNĐ';
        if (totalPriceInput) totalPriceInput.value = finalAmount;
    }
    couponInput?.addEventListener('input', renderTotal);
    pointsInput?.addEventListener('input', renderTotal);
    function resetShippingFee(message = '-- Chờ chọn Phường/Xã --') {
        currentShippingFee = 0;
        shippingFeeText.textContent = message;
        renderTotal();
    }
    function applyShippingFee(fee) {
        currentShippingFee = fee;
        shippingFeeText.textContent = '+ ' + new Intl.NumberFormat('vi-VN').format(fee) + ' VNĐ';
        renderTotal();
    }
    renderTotal();

    // 1. Tải danh sách Tỉnh/Thành phố từ GHN
    fetch("{{ route('locations.provinces') }}")
        .then(res => res.json())
        .then(res => {
            if (res.data) {
                let options = '<option value="">-- Chọn Tỉnh/Thành phố --</option>';
                res.data.forEach(p => {
                    options += `<option value="${p.ProvinceID}">${p.ProvinceName}</option>`;
                });
                provinceSelect.innerHTML = options;
            } else {
                provinceSelect.innerHTML = '<option value="">-- Không tải được tỉnh/thành --</option>';
            }
        })
        .catch(err => {
            console.error("Lỗi load tỉnh thành:", err);
            provinceSelect.innerHTML = '<option value="">-- Lỗi kết nối GHN --</option>';
        });

    // 2. Khi chọn Tỉnh -> Tải Quận/Huyện
    provinceSelect.addEventListener('change', function () {
        districtSelect.innerHTML = '<option value="">-- Đang tải Quận/Huyện... --</option>';
        districtSelect.disabled = true;
        wardSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện trước --</option>';
        wardSelect.disabled = true;
        resetShippingFee('Chờ chọn Phường/Xã');

        if (!this.value) {
            districtSelect.innerHTML = '<option value="">-- Chọn Tỉnh/Thành trước --</option>';
            return;
        }

        fetch(districtsUrl.replace('__PROVINCE__', this.value))
            .then(res => res.json())
            .then(res => {
                if (res.data) {
                    let options = '<option value="">-- Chọn Quận/Huyện --</option>';
                    res.data.forEach(d => {
                        options += `<option value="${d.DistrictID}">${d.DistrictName}</option>`;
                    });
                    districtSelect.innerHTML = options;
                    districtSelect.disabled = false;
                } else {
                    districtSelect.innerHTML = '<option value="">-- Không tải được quận/huyện --</option>';
                }
            })
            .catch(err => {
                console.error("Lỗi load quận huyện:", err);
                districtSelect.innerHTML = '<option value="">-- Lỗi kết nối GHN --</option>';
            });
    });

    // 3. Khi chọn Quận/Huyện -> Tải Phường/Xã
    districtSelect.addEventListener('change', function () {
        wardSelect.innerHTML = '<option value="">-- Đang tải Phường/Xã... --</option>';
        wardSelect.disabled = true;
        resetShippingFee('Chờ chọn Phường/Xã');

        if (!this.value) {
            wardSelect.innerHTML = '<option value="">-- Chọn Quận/Huyện trước --</option>';
            return;
        }

        fetch(wardsUrl.replace('__DISTRICT__', this.value))
            .then(res => res.json())
            .then(res => {
                if (res.data) {
                    let options = '<option value="">-- Chọn Phường/Xã --</option>';
                    res.data.forEach(w => {
                        options += `<option value="${w.WardCode}">${w.WardName}</option>`;
                    });
                    wardSelect.innerHTML = options;
                    wardSelect.disabled = false;
                } else {
                    wardSelect.innerHTML = '<option value="">-- Không tải được phường/xã --</option>';
                }
            })
            .catch(err => {
                console.error("Lỗi load phường xã:", err);
                wardSelect.innerHTML = '<option value="">-- Lỗi kết nối GHN --</option>';
            });
    });

    // 4. Khi chọn Phường/Xã -> Tính cước vận chuyển GHN theo đúng khối lượng giỏ hàng
    wardSelect.addEventListener('change', function () {
        if (!this.value || !districtSelect.value) {
            resetShippingFee('Chờ chọn Phường/Xã');
            return;
        }

        shippingFeeText.innerHTML = `<span style="color: #db2777; font-weight: 600;"><span style="display:inline-block; animation: spin 1s linear infinite;">@include('partials.icon', ['name' => 'hourglass', 'size' => '1em'])</span> Đang kết nối GHN tính phí...</span>`;

        fetch("{{ route('locations.fee') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                to_district_id: parseInt(districtSelect.value),
                to_ward_code: String(this.value)
            })
        })
        .then(res => res.json())
        .then(res => {
            if (res.code === 200 && res.data) {
                const fee = parseInt(res.data.total) || 0;
                applyShippingFee(fee);
            } else {
                shippingFeeText.innerHTML = `<span style="color: #ea580c; font-size: 0.85rem;">${res.message || 'Chưa hỗ trợ tuyến'}</span>`;
                resetShippingFee('Chưa hỗ trợ tuyến');
            }
        })
        .catch(err => {
            console.error("Lỗi tính phí:", err);
            shippingFeeText.innerHTML = '<span style="color: #dc2626; font-size: 0.85rem;">Lỗi kết nối GHN</span>';
            resetShippingFee('Lỗi kết nối GHN');
        });
    });

    // Ràng buộc số điện thoại chỉ đúng 10 chữ số (bắt đầu bằng số 0)
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
    }

    const checkoutForm = document.getElementById('checkoutPaymentForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            if (phoneInput) {
                const val = phoneInput.value.trim();
                if (!/^0[0-9]{9}$/.test(val)) {
                    e.preventDefault();
                    alert('Số điện thoại chỉ được gồm đúng 10 chữ số (bắt đầu bằng số 0).');
                    phoneInput.focus();
                    return false;
                }
            }

            if (!provinceSelect.value || !districtSelect.value || !wardSelect.value) {
                e.preventDefault();
                alert('Vui lòng chọn đầy đủ Tỉnh/Thành, Quận/Huyện và Phường/Xã để hệ thống GHN tính chính xác cước vận chuyển trước khi xác nhận đặt hàng.');
                if (!provinceSelect.value) provinceSelect.focus();
                else if (!districtSelect.value) districtSelect.focus();
                else wardSelect.focus();
                return false;
            }
        });
    }
});

</script>
@endsection
