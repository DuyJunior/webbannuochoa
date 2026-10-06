@extends('layouts.store')

@section('title', __('Thanh toán & Giao hàng GHN · Soopi'))

@section('content')
@php
    $checkoutInput = fn (string $key, mixed $fallback = '') => is_scalar(old($key, $fallback)) ? old($key, $fallback) : $fallback;
    $checkoutKey = $checkoutInput('checkout_key');
    $checkoutKey = \Illuminate\Support\Str::isUuid($checkoutKey) ? $checkoutKey : (string) \Illuminate\Support\Str::uuid();
    $giftRequested = in_array(old('enable_gift_service'), [true, 1, '1'], true);
    $sepayAvailable = \App\Support\DemoMode::enabled() || app(\App\Services\SePayService::class)->ready();
    $paymentChoice = $sepayAvailable && $checkoutInput('payment_method') === 'sepay' ? 'sepay' : 'cod';
@endphp
<div class="checkout-page-wrapper">
    <div class="store-container">
        {{-- Breadcrumb --}}
        <nav class="checkout-breadcrumb">
            <a href="{{ route('home') }}">{{ __('Trang chủ') }}</a>
            <span>/</span>
            <a href="{{ route('cart.index') }}">{{ __('Giỏ hàng') }}</a>
            <span>/</span>
            <span class="active">{{ __('Thanh toán & Vận chuyển GHN') }}</span>
        </nav>

        <div class="checkout-header-title">
            <span class="badge-tag">Soopi Delivery</span>
            <h1>{{ __('Hoàn tất đơn hàng') }}</h1>
            <p>{{ __('Tính cước phí vận chuyển chính xác thời gian thực qua Giao Hàng Nhanh (GHN)') }}</p>
        </div>

        @if ($errors->any())
            <div class="checkout-alert-error">
                <strong>{{ __('Đã có lỗi xảy ra:') }}</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('payment.process') }}" id="checkoutPaymentForm" class="checkout-grid-container">
            @csrf
            <input type="hidden" name="selection" value="1">
            @foreach($selectedKeys as $selectedKey)
                <input type="hidden" name="selected_items[]" value="{{ $selectedKey }}">
            @endforeach
            <input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">
            <input type="hidden" id="total_price_input" value="{{ $totalPrice }}">

            {{-- Cột Trái: Thông tin nhận hàng & Địa chỉ GHN --}}
            <div class="checkout-col-main">
                <div class="checkout-card">
                    <div class="card-section-header">
                        <div class="icon-circle">1</div>
                        <div>
                            <h2>{{ __('Thông tin người nhận') }}</h2>
                            <small>{{ __('Vui lòng cung cấp chính xác để nhân viên giao hàng liên hệ') }}</small>
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group-item">
                            <label for="name">{{ __('Họ và tên người nhận') }} <span class="req">*</span></label>
                            <input type="text" id="name" name="name" value="{{ $checkoutInput('name', auth()->user()?->name) }}" required placeholder="{{ __('Ví dụ: Nguyễn Thị Thu Hà') }}">
                        </div>

                        <div class="form-group-item">
                            <label for="phone">{{ __('Số điện thoại') }} <span class="req">*</span></label>
                            <input type="tel" id="phone" name="phone" value="{{ $checkoutInput('phone', auth()->user()?->phone) }}" required maxlength="10" minlength="10" pattern="0[0-9]{9}" title="{{ __('Số điện thoại phải gồm đúng 10 chữ số (bắt đầu bằng số 0)') }}" placeholder="{{ __('Ví dụ: 0912345678') }}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                            @error('phone')
                                <span class="text-danger" style="font-size: 12px; color: #ef4444; margin-top: 4px; display: block;">{{ $message }}</span>
                            @else
                                <small class="field-hint" style="font-size: 11px; color: #64748b; margin-top: 4px; display: block;">{{ __('Số điện thoại chỉ được đúng 10 chữ số (bắt đầu bằng số 0)') }}</small>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="checkout-card">
                    <div class="card-section-header">
                        <div class="icon-circle">2</div>
                        <div>
                            <h2>{{ __('Địa chỉ giao hàng (GHN)') }}</h2>
                            <small>{{ __('Chọn khu vực để hệ thống kết nối GHN tính phí tự động') }}</small>
                        </div>
                    </div>

                    @include('user.payment.partials.location-assistant')

                    <div class="ghn-partner-banner">
                        <div class="ghn-badge-logo">
                            <span class="truck-icon">@include('partials.icon', ['name' => 'truck', 'size' => '1em'])</span>
                            <strong>{{ __('Giao Hàng Nhanh (GHN Express)') }}</strong>
                        </div>
                        <span class="ghn-status-live">{{ __('● Kết nối API trực tiếp') }}</span>
                    </div>

                    <p class="delivery-location-filled" data-location-applied-summary role="status" hidden></p>
                    <div class="form-row-3">
                        <div class="form-group-item">
                            <label for="province_select">{{ __('Tỉnh / Thành phố') }} <span class="req">*</span></label>
                            <select id="province_select" name="to_province_id" class="form-select" required>
                                <option value="">{{ __('-- Đang tải Tỉnh/Thành... --') }}</option>
                            </select>
                        </div>

                        <div class="form-group-item">
                            <label for="district_select">{{ __('Quận / Huyện') }} <span class="req">*</span></label>
                            <select id="district_select" name="to_district_id" class="form-select" required disabled>
                                <option value="">{{ __('-- Chọn Tỉnh/Thành trước --') }}</option>
                            </select>
                        </div>

                        <div class="form-group-item">
                            <label for="ward_select">{{ __('Phường / Xã') }} <span class="req">*</span></label>
                            <select id="ward_select" name="to_ward_code" class="form-select" required disabled>
                                <option value="">{{ __('-- Chọn Quận/Huyện trước --') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group-item" style="margin-top: 16px;">
                        <label for="address">{{ __('Địa chỉ chi tiết (Số nhà, tên đường, ngõ ngách)') }} <span class="req">*</span></label>
                        <textarea id="address" name="address" rows="2" required placeholder="{{ __('Ví dụ: Số 18, Ngõ 45 Đường Láng') }}">{{ $checkoutInput('address') }}</textarea>
                    </div>

                    <div class="form-group-item" style="margin-top: 14px;">
                        <label for="note">{{ __('Ghi chú đơn hàng (Tùy chọn)') }}</label>
                        <input type="text" id="note" name="note" maxlength="500" value="{{ is_string(old('note')) ? old('note') : '' }}" placeholder="{{ __('Ví dụ: Giao giờ hành chính, gọi trước khi đến...') }}">
                    </div>
                </div>

                {{-- Dịch Vụ Gói Quà Cao Cấp & Thiệp Chúc Mừng --}}
                <div class="checkout-card" id="giftServiceCard">
                    @if(session('gift_finder_message'))
                        <p class="public-flash">{{ __('Đã giữ lời nhắn. Khi thanh toán, chọn dịch vụ thiệp quà để gửi kèm đơn hàng.') }}</p>
                    @endif
                    <div class="card-section-header">
                        <div class="icon-circle">@include('partials.icon', ['name' => 'gift', 'size' => '1em'])</div>
                        <div>
                            <h2>{{ __('Dịch Vụ Gói Quà Cao Cấp & Thiệp Chúc Mừng') }}</h2>
                            <small>{{ __('Món quà hoàn hảo trao tận tay người thương') }}</small>
                        </div>
                    </div>

                    <div style="background: #fff8fb; border: 1.5px solid #fbcfe8; border-radius: 14px; padding: 16px; margin-bottom: 16px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; color: #be185d; font-size: 15px;">
                            <input type="checkbox" id="giftWrapToggle" name="enable_gift_service" value="1" @checked($giftRequested) style="width: 18px; height: 18px; accent-color: #be185d;">
                            <span>{{ __('Yêu cầu Gói Quà Cao Cấp & Thiệp Chúc Mừng') }}</span>
                        </label>
                        <p style="margin: 4px 0 0 28px; font-size: 13px; color: #715865;">{{ __('Đóng hộp cứng cao cấp kèm nơ lụa, xịt hương thơm tinh tế trước khi đóng gói.') }}</p>
                    </div>

                    <div id="giftOptionsBox" style="display: {{ $giftRequested ? 'block' : 'none' }}; animation: fadeInGift 0.3s ease;">
                        <div class="form-row-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
                            <div class="form-group-item">
                                <label for="gift_wrap">{{ __('Mẫu giấy & hộp quà:') }}</label>
                                <select id="gift_wrap" name="gift_wrap" class="form-select" @disabled(!$giftRequested)>
                                    <option value="Nhung đỏ rượu vang (Wine Velvet)" @selected(old('gift_wrap') === 'Nhung đỏ rượu vang (Wine Velvet)')>{{ __('Nhung đỏ rượu vang quý phái') }}</option>
                                    <option value="Giấy Kraft Paris cổ điển (Vintage Kraft)" @selected(old('gift_wrap') === 'Giấy Kraft Paris cổ điển (Vintage Kraft)')>{{ __('Giấy Kraft Paris vintage mộc mạc') }}</option>
                                    <option value="Lụa hồng phấn kiêu kỳ (Blush Pink)" @selected(old('gift_wrap') === 'Lụa hồng phấn kiêu kỳ (Blush Pink)')>{{ __('Lụa hồng phấn ngọt ngào') }}</option>
                                    <option value="Đen huyền bí sang trọng (Midnight Black)" @selected(old('gift_wrap') === 'Đen huyền bí sang trọng (Midnight Black)')>{{ __('Đen huyền bí sang trọng') }}</option>
                                </select>
                            </div>
                            <div class="form-group-item">
                                <label for="gift_card">{{ __('Mẫu thiệp chúc mừng:') }}</label>
                                <select id="gift_card" name="gift_card" class="form-select" @disabled(!$giftRequested)>
                                    <option value="Sinh nhật (Happy Birthday)" @selected(old('gift_card') === 'Sinh nhật (Happy Birthday)')>{{ __('Sinh nhật (Happy Birthday)') }}</option>
                                    <option value="Kỷ niệm (Happy Anniversary)" @selected(old('gift_card') === 'Kỷ niệm (Happy Anniversary)')>{{ __('Kỷ niệm (Happy Anniversary)') }}</option>
                                    <option value="Tình yêu (With Love)" @selected(old('gift_card') === 'Tình yêu (With Love)')>{{ __('Tình yêu ngọt ngào (With Love)') }}</option>
                                    <option value="Tri ân & Cảm ơn (Thank You)" @selected(old('gift_card') === 'Tri ân & Cảm ơn (Thank You)')>{{ __('Tri ân & Cảm ơn (Thank You)') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group-item" style="margin-bottom: 14px;">
                            <label for="gift_message">{{ __('Lời nhắn chúc mừng (in lên thiệp):') }}</label>
                            <textarea id="gift_message" name="gift_message" rows="2" maxlength="1000" @disabled(!$giftRequested) placeholder="{{ __('Ví dụ: Chúc em sinh nhật vui vẻ, luôn rạng rỡ và ngát hương thơm mỗi ngày nhé!') }}">{{ $checkoutInput('gift_message', session('gift_finder_message', '')) }}</textarea>
                        </div>

                        <div class="form-group-item">
                            <label for="gift_delivery_date">{{ __('Ngày giao mong muốn (để nhận đúng ngày đặc biệt):') }}</label>
                            <input type="date" id="gift_delivery_date" name="gift_delivery_date" value="{{ $checkoutInput('gift_delivery_date') }}" min="{{ date('Y-m-d') }}" @disabled(!$giftRequested)>
                        </div>
                    </div>
                </div>

                <div class="checkout-card">
                    <div class="card-section-header">
                        <div class="icon-circle">3</div>
                        <div>
                            <h2>{{ __('Phương thức thanh toán') }}</h2>
                            <small>{{ __('Đơn giản, an toàn và tiện lợi') }}</small>
                        </div>
                    </div>

                    <div class="payment-method-box">
                        {{-- 1. Thanh toán COD --}}
                        <label class="payment-option {{ $paymentChoice === 'cod' ? 'selected' : '' }}" id="label_cod">
                            <input type="radio" name="_payment_choice" value="cod" id="radio_cod" onchange="choosePayment(this.value)" @checked($paymentChoice === 'cod')>
                            <div class="payment-option-body">
                                <div class="opt-title">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-size: 1.25rem;">@include('partials.icon', ['name' => 'cash', 'size' => '1em'])</span>
                                        <strong>{{ __('Thanh toán khi nhận hàng (COD)') }}</strong>
                                    </div>
                                    <span class="badge-popular">{{ __('Phổ biến') }}</span>
                                </div>
                                <p>{{ __('Nhận hàng, kiểm tra tem seal nước hoa chính hãng trước khi thanh toán cho shipper GHN.') }}</p>
                            </div>
                        </label>

                        @if($sepayAvailable)
                        <label class="payment-option {{ $paymentChoice === 'sepay' ? 'selected' : '' }}" id="label_sepay">
                            <input type="radio" name="_payment_choice" value="sepay" id="radio_sepay" onchange="choosePayment(this.value)" @checked($paymentChoice === 'sepay')>
                            <div class="payment-option-body">
                                <div class="opt-title">
                                    <strong>@include('partials.icon', ['name' => 'bank', 'size' => '1em']) {{ __('Chuyển khoản ngân hàng (SePay)') }}</strong>
                                    <span class="badge-popular">{{ \App\Support\DemoMode::enabled() ? __('Mô phỏng') : 'VietQR · '.config('sepay.bank') }}</span>
                                </div>
                                <p>{{ __('Quét QR bằng ứng dụng ngân hàng. Đơn hàng tự động cập nhật khi nhận đủ tiền với đúng nội dung chuyển khoản.') }}</p>
                            </div>
                        </label>
                        @else
                            <p class="text-muted">{{ __('Thanh toán SePay đang tạm ngưng. Vui lòng chọn COD hoặc thử lại sau.') }}</p>
                        @endif

                        {{-- Hidden input duy nhất gửi phương thức thanh toán lên server (cod hoặc sepay) --}}
                        <input type="hidden" name="payment_method" id="selected_payment_method" value="{{ $paymentChoice }}">
                    </div>
                </div>
            </div>

            {{-- Cột Phải: Tóm tắt đơn hàng & Phí ship GHN --}}
            <div class="checkout-col-side">
                <div class="order-summary-box">
                    <h3 class="summary-box-title">{{ __('Đơn hàng của bạn') }}</h3>

                    <div class="summary-items-list">
                        @if(isset($cartItems) && count($cartItems) > 0)
                            @foreach ($cartItems as $item)
                                <div class="summary-item-row">
                                    <div class="item-thumb-box">
                                        @if($item['product']?->image_src)
                                            <img src="{{ $item['product']->image_src }}" alt="{{ $item['product']->name }}">
                                        @else
                                            <div class="thumb-placeholder">@include('partials.brand-mark', ['size' => '1em'])</div>
                                        @endif
                                        <span class="item-qty-badge">{{ $item['quantity'] }}</span>
                                    </div>
                                    <div class="item-details">
                                        <h4 class="item-title">{{ $item['title'] }}</h4>
                                        <span class="item-meta">{{ $item['volume_label'] }} · {{ $item['weight'] ?? 200 }}g</span>
                                        @if(!empty($item['sample_names']))<p class="item-meta">{{ __('Mẫu 5ml:') }} {{ implode(' · ', $item['sample_names']) }}</p>@endif
                                        @if($item['is_gift_bundle'])<p class="item-meta">{{ __('Hộp quà & thiệp đã gồm trong giá combo.') }}</p>@endif
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
                            <span>{{ __('Tiền hàng') }}</span>
                            <strong id="subtotal_text">{{ number_format($totalPrice, 0, ',', '.') }} {{ __('VNĐ') }}</strong>
                        </div>
                        <div class="cost-row">
                            <span>{{ __('Tổng khối lượng tính phí') }}</span>
                            <strong style="color: #db2777; font-weight: 700;">{{ isset($totalWeight) ? $totalWeight : 200 }} g</strong>
                        </div>
                        <div class="ht-checkout-promos">
                            <label>{{ __('Mã ưu đãi') }}<input name="coupon_code" value="{{ $checkoutInput('coupon_code') }}" placeholder="{{ __('Nhập mã nếu có') }}" maxlength="30"></label>
                            @if($availableCoupons->isNotEmpty())<small>{{ __('Mã hiện có:') }} @foreach($availableCoupons as $coupon)<strong>{{ $coupon->code }}</strong>{{ !$loop->last ? ', ' : '' }}@endforeach</small>@endif
                            <label>{{ __('Điểm thành viên muốn dùng') }}<input name="points_used" type="number" min="0" max="{{ min($loyaltyBalance, floor($totalPrice * .2 / 1000)) }}" value="{{ $checkoutInput('points_used', 0) }}"></label>
                            <small>{{ __('Bạn có') }} {{ $loyaltyBalance }} {{ __('điểm · 1 điểm giảm 1.000₫ · tối đa 20% tiền hàng. Ưu đãi được xác nhận khi đặt hàng.') }}</small>
                            @error('coupon_code')<span class="text-danger">{{ $message }}</span>@enderror
                            @error('points_used')<span class="text-danger">{{ $message }}</span>@enderror
                        </div>
                        <div class="cost-row"><span>{{ __('Ưu đãi dự tính') }}</span><strong id="discount_preview">{{ __('0 VNĐ') }}</strong></div>
                        <div class="cost-row shipping-row">
                            <span>{{ __('Cước vận chuyển GHN') }}</span>
                            <strong id="shipping_fee_text" class="fee-waiting" aria-live="polite">{{ __('-- Chọn địa chỉ --') }}</strong>
                        </div>
                        <button type="button" id="shipping_retry" class="shipping-retry" hidden>{{ __('Thử kết nối giao hàng lại ↻') }}</button>
                        <div class="cost-row total-highlight">
                            <span>{{ __('Tổng thanh toán') }}</span>
                            <strong id="final_total_text" class="final-price">{{ number_format($totalPrice, 0, ',', '.') }} {{ __('VNĐ') }}</strong>
                        </div>
                    </div>

                    <p class="store-privacy-note">{{ __('Thông tin người nhận được dùng để xử lý và giao đơn hàng.') }} <a href="{{ route('store.privacy') }}" target="_blank" rel="noopener">{{ __('Chính sách riêng tư ↗') }}</a></p>
                    <button type="submit" class="btn-confirm-checkout" id="btnSubmitPayment">
                        <span>{{ __('XÁC NHẬN ĐẶT HÀNG') }}</span>
                        <span class="btn-icon">→</span>
                    </button>

                    <div class="safe-checkout-badge">
                        <span>@include('partials.icon', ['name' => 'lock', 'size' => '1em']) {{ __('Bảo mật thông tin đặt hàng · Cam kết chính hãng 100%') }}</span>
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
    font-family: 'Playfair Display', 'Soopi Serif', Georgia, serif;
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
.btn-confirm-checkout:disabled {
    opacity: .55;
    cursor: wait;
    transform: none;
    box-shadow: none;
}
.shipping-retry { color: #784a60; background: transparent; border: 0; text-decoration: underline; cursor: pointer; text-align: left; padding: 6px 0; }
.shipping-retry[hidden] { display: none; }
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
        const syncGiftOptions = () => {
            giftOptionsBox.style.display = giftWrapToggle.checked ? 'block' : 'none';
            giftOptionsBox.querySelectorAll('input, select, textarea').forEach(field => {
                field.disabled = !giftWrapToggle.checked;
            });
        };
        giftWrapToggle.addEventListener('change', syncGiftOptions);
        syncGiftOptions();
    }
});

function choosePayment(method) {
    const radioCod = document.getElementById('radio_cod');
    const radioSePay = document.getElementById('radio_sepay');
    const labelCod = document.getElementById('label_cod');
    const labelSePay = document.getElementById('label_sepay');
    const selectedInput = document.getElementById('selected_payment_method');

    if (method === 'sepay') {
        if (radioSePay) radioSePay.checked = true;
        if (radioCod) radioCod.checked = false;
        if (labelSePay) labelSePay.classList.add('selected');
        if (labelCod) labelCod.classList.remove('selected');
        if (selectedInput) selectedInput.value = 'sepay';
    } else {
        if (radioCod) radioCod.checked = true;
        if (radioSePay) radioSePay.checked = false;
        if (labelCod) labelCod.classList.add('selected');
        if (labelSePay) labelSePay.classList.remove('selected');
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
    let shippingReady = false;
    const submitButton = document.getElementById('btnSubmitPayment');
    const shippingRetry = document.getElementById('shipping_retry');
    function renderTotal() {
        const coupon = coupons.find(item => item.code === (couponInput?.value || '').trim().toUpperCase());
        const couponDiscount = coupon ? Math.min(subtotal, coupon.type === 'percent' ? Math.floor(subtotal * coupon.value / 100) : coupon.value) : 0;
        const maxPoints = Math.min({{ $loyaltyBalance }}, Math.floor((subtotal - couponDiscount) * 0.2 / 1000));
        const points = Math.min(maxPoints, Math.max(0, parseInt(pointsInput?.value || '0', 10) || 0));
        const savings = couponDiscount + points * 1000;
        discountPreview.textContent = savings ? '- ' + new Intl.NumberFormat('vi-VN').format(savings) + (window.soopiT || (text => text))(" VNĐ") : (window.soopiT || (text => text))("0 VNĐ");
        const finalAmount = Math.max(0, subtotal + currentShippingFee - savings);
        finalTotalText.textContent = new Intl.NumberFormat('vi-VN').format(finalAmount) + (window.soopiT || (text => text))(" VNĐ");
        if (totalPriceInput) totalPriceInput.value = finalAmount;
    }
    couponInput?.addEventListener('input', renderTotal);
    pointsInput?.addEventListener('input', renderTotal);
    function resetShippingFee(message = (window.soopiT || (text => text))("-- Chờ chọn Phường/Xã --")) {
        shippingReady = false;
        submitButton.disabled = true;
        currentShippingFee = 0;
        shippingFeeText.textContent = message;
        renderTotal();
    }
    function applyShippingFee(fee) {
        shippingReady = true;
        submitButton.disabled = false;
        currentShippingFee = fee;
        shippingFeeText.textContent = '+ ' + new Intl.NumberFormat('vi-VN').format(fee) + (window.soopiT || (text => text))(" VNĐ");
        renderTotal();
    }
    resetShippingFee();

    // Each dependent request owns a version so an older response cannot replace a new address.
    const versions = { province: 0, district: 0, ward: 0, fee: 0 };
    const requests = {};
    const restoreAddress = {{ Illuminate\Support\Js::from(['province' => (string) $checkoutInput('to_province_id'), 'district' => (string) $checkoutInput('to_district_id'), 'ward' => (string) $checkoutInput('to_ward_code')]) }};
    let retryShipping = null;

    function invalidate(...names) {
        names.forEach(name => {
            versions[name] += 1;
            requests[name]?.abort();
        });
        shippingRetry.hidden = true;
    }
    function optionsFor(select, placeholder, data = [], valueKey = '', labelKey = '') {
        select.replaceChildren(new Option(placeholder, ''));
        data.forEach(item => select.add(new Option(String(item[labelKey]), String(item[valueKey]))));
        select.disabled = data.length === 0;
    }
    function offerRetry(callback, message) {
        retryShipping = callback;
        shippingRetry.hidden = false;
        resetShippingFee(message);
    }
    async function requestJSON(name, url, options = {}) {
        const controller = new AbortController();
        requests[name] = controller;
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(url, { ...options, signal: controller.signal, headers: { Accept: 'application/json', ...options.headers } });
            if (!response.ok) throw new Error('Shipping request failed');
            return await response.json();
        } finally {
            clearTimeout(timeout);
        }
    }
    async function loadProvinces(restore = {}) {
        invalidate('province', 'district', 'ward', 'fee');
        const version = versions.province;
        resetShippingFee((window.soopiT || (text => text))("Đang tải khu vực giao hàng…"));
        optionsFor(provinceSelect, (window.soopiT || (text => text))("-- Đang tải Tỉnh/Thành... --"));
        optionsFor(districtSelect, (window.soopiT || (text => text))("-- Chọn Tỉnh/Thành trước --"));
        optionsFor(wardSelect, (window.soopiT || (text => text))("-- Chọn Quận/Huyện trước --"));
        try {
            const response = await requestJSON('province', "{{ route('locations.provinces') }}");
            if (version !== versions.province) return;
            if (!Array.isArray(response.data) || !response.data.length) throw new Error('No provinces');
            optionsFor(provinceSelect, (window.soopiT || (text => text))("-- Chọn Tỉnh/Thành phố --"), response.data, 'ProvinceID', 'ProvinceName');
            resetShippingFee();
            if (restore.province) {
                provinceSelect.value = restore.province;
                if (provinceSelect.value) await loadDistricts(restore);
            }
        } catch {
            if (version !== versions.province) return;
            optionsFor(provinceSelect, (window.soopiT || (text => text))("-- Chưa tải được tỉnh/thành --"));
            offerRetry(() => loadProvinces(restore), (window.soopiT || (text => text))("Chưa tải được khu vực. Vui lòng thử lại."));
        }
    }
    async function loadDistricts(restore = {}) {
        invalidate('district', 'ward', 'fee');
        const version = versions.district;
        const province = provinceSelect.value;
        resetShippingFee();
        optionsFor(districtSelect, province ? (window.soopiT || (text => text))("-- Đang tải Quận/Huyện... --") : (window.soopiT || (text => text))("-- Chọn Tỉnh/Thành trước --"));
        optionsFor(wardSelect, (window.soopiT || (text => text))("-- Chọn Quận/Huyện trước --"));
        if (!province) return;
        try {
            const response = await requestJSON('district', districtsUrl.replace('__PROVINCE__', encodeURIComponent(province)));
            if (version !== versions.district || province !== provinceSelect.value) return;
            if (!Array.isArray(response.data) || !response.data.length) throw new Error('No districts');
            optionsFor(districtSelect, (window.soopiT || (text => text))("-- Chọn Quận/Huyện --"), response.data, 'DistrictID', 'DistrictName');
            if (restore.district) {
                districtSelect.value = restore.district;
                if (districtSelect.value) await loadWards(restore);
            }
        } catch {
            if (version !== versions.district) return;
            optionsFor(districtSelect, (window.soopiT || (text => text))("-- Chưa tải được quận/huyện --"));
            offerRetry(() => loadDistricts(restore), (window.soopiT || (text => text))("Chưa tải được quận/huyện. Vui lòng thử lại."));
        }
    }
    async function loadWards(restore = {}) {
        invalidate('ward', 'fee');
        const version = versions.ward;
        const district = districtSelect.value;
        resetShippingFee();
        optionsFor(wardSelect, district ? (window.soopiT || (text => text))("-- Đang tải Phường/Xã... --") : (window.soopiT || (text => text))("-- Chọn Quận/Huyện trước --"));
        if (!district) return;
        try {
            const response = await requestJSON('ward', wardsUrl.replace('__DISTRICT__', encodeURIComponent(district)));
            if (version !== versions.ward || district !== districtSelect.value) return;
            if (!Array.isArray(response.data) || !response.data.length) throw new Error('No wards');
            optionsFor(wardSelect, (window.soopiT || (text => text))("-- Chọn Phường/Xã --"), response.data, 'WardCode', 'WardName');
            if (restore.ward) {
                wardSelect.value = restore.ward;
                if (wardSelect.value) await loadFee();
            }
        } catch {
            if (version !== versions.ward) return;
            optionsFor(wardSelect, (window.soopiT || (text => text))("-- Chưa tải được phường/xã --"));
            offerRetry(() => loadWards(restore), (window.soopiT || (text => text))("Chưa tải được phường/xã. Vui lòng thử lại."));
        }
    }
    async function loadFee() {
        invalidate('fee');
        const version = versions.fee;
        const district = districtSelect.value;
        const ward = wardSelect.value;
        resetShippingFee(ward && district ? (window.soopiT || (text => text))("Đang tính phí giao hàng…") : (window.soopiT || (text => text))("Chờ chọn Phường/Xã"));
        if (!ward || !district) return;
        try {
            const response = await requestJSON('fee', "{{ route('locations.fee') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    to_district_id: Number(district), to_ward_code: ward, selection: 1,
                    selected_items: {{ Illuminate\Support\Js::from($selectedKeys) }}
                })
            });
            if (version !== versions.fee || district !== districtSelect.value || ward !== wardSelect.value) return;
            const fee = Number(response.data?.total);
            if (Number(response.code) !== 200 || response.data?.total == null || !Number.isFinite(fee) || fee < 0) throw new Error('Invalid shipping quote');
            applyShippingFee(Math.round(fee));
        } catch {
            if (version !== versions.fee) return;
            offerRetry(loadFee, (window.soopiT || (text => text))("Chưa lấy được phí giao hàng. Vui lòng thử lại."));
        }
    }
    shippingRetry.addEventListener('click', () => retryShipping?.());
    provinceSelect.addEventListener('change', () => loadDistricts());
    districtSelect.addEventListener('change', () => loadWards());
    wardSelect.addEventListener('change', loadFee);
    loadProvinces(restoreAddress);

    document.addEventListener('soopi:delivery-location', async (event) => {
        // Reuse the guarded GHN cascade, including server-calculated shipping fees.
        try {
            await loadProvinces(event.detail);
        } finally {
            event.detail.complete?.();
        }
    });
    document.addEventListener('soopi:delivery-location-cancel', () => {
        // Cancel stale automatic selections while keeping choices already made.
        loadProvinces({ province: provinceSelect.value, district: districtSelect.value, ward: wardSelect.value });
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
                    window.soopiToast((window.soopiT || (text => text))("Số điện thoại chỉ được gồm đúng 10 chữ số (bắt đầu bằng số 0)."), 'warning');
                    phoneInput.focus();
                    return false;
                }
            }

            if (!provinceSelect.value || !districtSelect.value || !wardSelect.value) {
                e.preventDefault();
                window.soopiToast((window.soopiT || (text => text))("Vui lòng chọn đầy đủ Tỉnh/Thành, Quận/Huyện và Phường/Xã để hệ thống GHN tính chính xác cước vận chuyển trước khi xác nhận đặt hàng."), 'warning');
                if (!provinceSelect.value) provinceSelect.focus();
                else if (!districtSelect.value) districtSelect.focus();
                else wardSelect.focus();
                return false;
            }
            if (!shippingReady) {
                e.preventDefault();
                shippingFeeText.textContent = (window.soopiT || (text => text))("Vui lòng đợi phí giao hàng hoặc bấm thử lại trước khi đặt hàng.");
                shippingFeeText.scrollIntoView({ block: 'nearest' });
            }
        });
    }
});

</script>
@endsection
