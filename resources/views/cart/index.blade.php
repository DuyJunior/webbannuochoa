@extends('layouts.store')

@section('title', __('Giỏ hàng · Soopi'))

@section('content')
<section class="store-container cart-page-modern">
    {{-- Header & Breadcrumb --}}
    <div class="cart-top-bar">
        <a class="cart-back-btn" href="{{ route('home') }}#san-pham">
            {{ __('← Tiếp tục mua sắm') }}
        </a>
        <div class="cart-title-wrap">
            <h1 class="cart-page-title">{{ __('Giỏ hàng của bạn') }}</h1>
            <span class="cart-item-count-pill" id="headerItemCountPill">{{ $items->sum('quantity') }} {{ __('sản phẩm') }}</span>
        </div>
    </div>

    @if (isset($errors) && $errors->any())
        <div class="cart-alert-error" role="alert">
            <span class="alert-icon">@include('partials.icon', ['name' => 'warning', 'size' => '1em'])</span>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @foreach ($unavailableItems as $unavailable)
        <div class="cart-alert-error" role="alert">
            <div><strong>{{ $unavailable['name'] }}</strong><br>{{ $unavailable['reason'] }}</div>
            <form method="POST" action="{{ route('cart.remove', $unavailable['item_key']) }}">
                @csrf @method('DELETE')
                <button class="btn-item-remove" type="submit">{{ __('Xóa sản phẩm không khả dụng') }}</button>
            </form>
        </div>
    @endforeach
    @if ($items->isEmpty())
        <div class="cart-empty-box">
            <div class="empty-icon">@include('partials.icon', ['name' => 'bag', 'size' => '1em'])</div>
            <h2>{{ __('Giỏ hàng của bạn đang trống') }}</h2>
            <p>{{ __('Hãy khám phá những tuyệt tác mùi hương chính hãng tại Soopi.') }}</p>
            <a class="btn-empty-shop" href="{{ route('home') }}#san-pham">{{ __('Khám phá sản phẩm ngay') }}</a>
        </div>
    @else
        {{-- Free Shipping Banner --}}
        <div class="cart-freeship-banner">
            <div class="freeship-content">
                <span class="freeship-icon">@include('partials.icon', ['name' => 'truck', 'size' => '1em'])</span>
                <span>{{ __('Phí vận chuyển được tính theo địa chỉ nhận hàng ở bước thanh toán.') }}</span>
            </div>
            <span class="freeship-tag">{{ __('Phí được báo trước khi đặt') }}</span>
        </div>

        <div class="cart-main-grid">
            {{-- Left: Item List --}}
            <div class="cart-items-list">
                {{-- Select All Control Bar --}}
                <div class="cart-select-all-card">
                    <label class="custom-cart-checkbox-label" for="selectAllCart">
                        <input type="checkbox" id="selectAllCart" class="custom-cart-checkbox-input" checked>
                        <span class="custom-cart-checkbox-box"></span>
                        <span class="select-all-label-text">{{ __('Chọn tất cả (') }}<span id="totalItemTypesCount">{{ $items->count() }}</span> {{ __('loại sản phẩm)') }}</span>
                    </label>
                    <div class="cart-selected-status-badge">
                        {{ __('Đã chọn:') }} <strong id="selectedTypesCount">{{ $items->count() }}</strong> / {{ $items->count() }}
                    </div>
                </div>

                @foreach ($items as $item)
                    @php
                        $product = $item['product'];
                        $itemKey = $item['item_key'];
                    @endphp
                    <article class="cart-item-card is-selected" id="item-card-{{ md5($itemKey) }}">
                        {{-- Checkbox Select --}}
                        <div class="item-select-checkbox-wrap">
                            <label class="custom-cart-checkbox-label" for="check_{{ md5($itemKey) }}" title="{{ __('Chọn sản phẩm này để thanh toán') }}">
                                <input type="checkbox"
                                       name="selected_items[]"
                                       form="checkoutSelectionForm"
                                       value="{{ $itemKey }}"
                                       id="check_{{ md5($itemKey) }}"
                                       class="custom-cart-checkbox-input cart-item-checkbox"
                                       checked
                                       data-line-total="{{ $item['line_total'] }}"
                                       data-unit-price="{{ $item['unit_price'] }}"
                                       data-quantity="{{ $item['quantity'] }}"
                                       data-card-id="item-card-{{ md5($itemKey) }}">
                                <span class="custom-cart-checkbox-box"></span>
                            </label>
                        </div>

                        <a class="item-img-link" href="{{ route('perfumes.show', $product) }}">
                            @if ($product->image_src)
                                <img src="{{ $product->image_src }}" alt="{{ $product->name }}">
                            @else
                                <div class="item-placeholder-img">{{ mb_substr($product->brand, 0, 1) }}</div>
                            @endif
                        </a>

                        <div class="item-details">
                            <span class="item-brand">{{ $product->brand }}</span>
                            <h2 class="item-title">
                                <a href="{{ route('perfumes.show', $product) }}">{{ ($item['is_discovery_box'] || $item['is_gift_bundle']) ? $item['custom_title'] : $product->name }}</a>
                            </h2>
                            
                            {{-- Dung tích --}}
                            <div class="item-option-badge volume-badge">
                                <span>@include('partials.icon', ['name' => 'drop', 'size' => '1em']) {{ __('Dung tích:') }} <strong>{{ $item['volume_label'] ?? ($item['volume_ml'].'ml') }}</strong></span>
                            </div>

                            @if($item['is_discovery_box'])
                                <p class="item-sample-summary">{{ $item['engrave_text'] }}</p>
                            @endif
                            @if($item['is_gift_bundle'])
                                <p class="item-sample-summary"><strong>{{ __('Hai mẫu 5ml:') }}</strong> {{ implode(' · ', $item['sample_names']) }}</p>
                            @endif

                            {{-- Dịch vụ quà tặng & khắc tên --}}
                            @if(!empty($item['has_gift']) || !empty($item['has_engrave']))
                                <div class="item-addons-group">
                                    @if(!empty($item['has_gift']))
                                        <span class="addon-badge gift-badge">
                                            @include('partials.icon', ['name' => 'gift', 'size' => '1em']) {{ $item['is_gift_bundle'] ? __('Hộp quà & thiệp đã gồm trong giá combo') : ($item['is_discovery_box'] ? __('Giá trọn hộp mẫu thử') : __('Gói quà Luxury & Thiệp (+50.000₫)')) }}
                                        </span>
                                    @endif
                                    @if(!empty($item['has_engrave']) && !empty($item['engrave_text']))
                                        <span class="addon-badge engrave-badge">
                                            @include('partials.icon', ['name' => 'pen', 'size' => '1em']) {{ __('Khắc Laser: "') }}<strong>{{ $item['engrave_text'] }}</strong>"
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div class="item-unit-price">
                                {{ __('Đơn giá:') }} <strong>{{ number_format($item['unit_price'], 0, ',', '.') }}₫</strong>
                            </div>
                        </div>

                        <div class="item-actions-wrapper">
                            {{-- Quantity Picker --}}
                            <form method="POST" action="{{ route('cart.update', $itemKey) }}" class="item-qty-form">
                                @csrf @method('PATCH')
                                <div class="custom-qty-picker" data-quantity-picker>
                                    <button type="button" data-quantity-minus class="qty-btn minus" aria-label="{{ __('Giảm số lượng') }}">−</button>
                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="{{ max(1, $item['max_quantity']) }}" class="qty-input" aria-label="Số lượng {{ ($item['is_discovery_box'] || $item['is_gift_bundle']) ? $item['custom_title'] : $product->name }}">
                                    <button type="button" data-quantity-plus class="qty-btn plus" aria-label="{{ __('Tăng số lượng') }}">+</button>
                                </div>
                                <button class="btn-qty-update" type="submit" title="{{ __('Cập nhật số lượng') }}">{{ __('Cập nhật') }}</button>
                            </form>

                            {{-- Line Total --}}
                            <div class="item-line-total">
                                <span class="total-label">{{ __('Thành tiền') }}</span>
                                <strong class="total-value">{{ number_format($item['line_total'], 0, ',', '.') }}₫</strong>
                            </div>

                            {{-- Remove Button --}}
                            <form method="POST" action="{{ route('cart.remove', $itemKey) }}">
                                @csrf @method('DELETE')
                                <button class="btn-item-remove" type="submit" title="{{ __('Xóa khỏi giỏ hàng') }}" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                    @include('partials.icon', ['name' => 'close', 'size' => '1em']) {{ __('Xóa') }}
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach

                <div class="cart-guarantee-box">
                    <div class="guarantee-item">
                        <span>@include('partials.icon', ['name' => 'shield', 'size' => '1em'])</span>
                        <div><strong>{{ __('100% Chính hãng') }}</strong><small>{{ __('Cam kết nguồn gốc rõ ràng') }}</small></div>
                    </div>
                    <div class="guarantee-item">
                        <span>@include('partials.icon', ['name' => 'refresh', 'size' => '1em'])</span>
                        <div><strong>{{ __('Đổi trả') }} {{ config('storefront.return_days') }} {{ __('ngày') }}</strong><small><a href="{{ route('store.faq') }}#doi-tra">{{ __('Xem điều kiện đổi trả') }}</a></small></div>
                    </div>
                    <div class="guarantee-item">
                        <span>@include('partials.icon', ['name' => 'box', 'size' => '1em'])</span>
                        <div><strong>{{ __('Đóng gói an toàn') }}</strong><small>{{ __('3 lớp chống sốc chuyên dụng') }}</small></div>
                    </div>
                </div>
            </div>

            {{-- Right: Order Summary (Tách riêng khỏi Thanh toán) --}}
            <aside class="cart-checkout-sidebar">
                <div class="checkout-card-box">
                    <div class="purchase-summary-heading">
                        <h2 class="checkout-box-title">{{ __('Tóm tắt đơn hàng') }}</h2>
                        <span class="purchase-summary-badge">@include('partials.icon', ['name' => 'bag', 'size' => '1em'])</span>
                    </div>

                    <div class="checkout-summary-lines">
                        <div class="summary-line">
                            <span>{{ __('Số lượng sản phẩm') }}</span>
                            <strong id="checkoutSelectedQuantity">{{ $items->sum('quantity') }} {{ __('món') }}</strong>
                        </div>
                        <div class="summary-line">
                            <span>{{ __('Tạm tính tiền hàng') }}</span>
                            <strong id="subtotalDisplay">{{ number_format($subtotal, 0, ',', '.') }}₫</strong>
                        </div>
                        <div class="summary-line">
                            <span>{{ __('Phí vận chuyển (GHN)') }}</span>
                            <span class="purchase-shipping-note">{{ __('Tính ở bước thanh toán') }}</span>
                        </div>
                        <div class="summary-line total-line">
                            <span>{{ __('Ước tính tổng tiền') }}</span>
                            <strong class="grand-total-amount" id="final_total_text">{{ number_format($subtotal, 0, ',', '.') }}₫</strong>
                        </div>
                    </div>

                    {{-- Warning message when no items checked --}}
                    <div id="noSelectionAlert" class="cart-no-selection-alert" style="display: none; margin: 14px 0;">
                        <span>@include('partials.icon', ['name' => 'warning', 'size' => '1em']) {{ __('Vui lòng tick chọn ít nhất 1 sản phẩm để thanh toán.') }}</span>
                    </div>

                    <form method="GET" action="{{ route('payment.index') }}" id="checkoutSelectionForm" class="purchase-checkout-action">
                        <input type="hidden" name="selection" value="1">
                        <button type="submit" class="btn-submit-order" id="btnProceedToCheckout">
                            <span>{{ __('Tiếp tục thanh toán') }}</span>
                            <span class="btn-arrow">→</span>
                        </button>
                    </form>

                    <div class="purchase-continue">
                        <a href="{{ route('home') }}#san-pham">
                            {{ __('← Chọn thêm nước hoa khác') }}
                        </a>
                    </div>

                    <div class="checkout-security-note">
                        <div class="purchase-security-line">
                            <span>@include('partials.icon', ['name' => 'truck', 'size' => '1em'])</span>
                            <span>{{ __('Giao hàng tận nơi toàn quốc qua') }} <strong>{{ __('Giao Hàng Nhanh (GHN)') }}</strong></span>
                        </div>
                        <div class="purchase-security-line">
                            <span>@include('partials.icon', ['name' => 'card', 'size' => '1em'])</span>
                            <span>{{ __('Hỗ trợ thanh toán khi nhận hàng (COD) linh hoạt') }}</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    @endif
</section>

<style>
/* Modern Cart Scoped Styles */
.cart-page-modern {
    padding: 24px 0 60px;
    font-family: inherit;
}
.item-sample-summary { font-size: 12px; line-height: 1.6; color: #6d5b64; margin: 8px 0; }
.custom-cart-checkbox-input:focus-visible + .custom-cart-checkbox-box { outline: 2px solid #753d59; outline-offset: 3px; }
.cart-top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e8f0;
}
.cart-back-btn {
    font-size: 13.5px;
    font-weight: 600;
    color: #145a43;
    text-decoration: none;
    padding: 6px 12px;
    border-radius: 6px;
    background: #f0fdf4;
    transition: all 0.2s;
}
.cart-back-btn:hover {
    background: #dcfce7;
}
.cart-title-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
}
.cart-page-title {
    font-size: 22px;
    font-weight: 700;
    margin: 0;
    color: #0f172a;
}
.cart-item-count-pill {
    background: #e2e8f0;
    color: #334155;
    font-size: 12px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 12px;
}

/* Alert Error */
.cart-alert-error {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13.5px;
}

/* Free Shipping Banner */
.cart-freeship-banner {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13.5px;
    color: #166534;
}
.freeship-content {
    display: flex;
    align-items: center;
    gap: 8px;
}
.freeship-tag {
    background: #16a34a;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
}

/* Select All Card */
.cart-select-all-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.select-all-label-text {
    font-size: 13.5px;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    user-select: none;
}
.cart-selected-status-badge {
    background: #f1f5f9;
    color: #475569;
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 500;
}
.cart-selected-status-badge strong {
    color: #145a43;
}

/* Custom Checkbox Design */
.custom-cart-checkbox-label {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    margin: 0;
    position: relative;
    user-select: none;
}
.custom-cart-checkbox-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    cursor: pointer;
}
.custom-cart-checkbox-box {
    width: 20px;
    height: 20px;
    background-color: #ffffff;
    border: 2px solid #cbd5e1;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
}
.custom-cart-checkbox-label:hover .custom-cart-checkbox-box {
    border-color: #145a43;
}
.custom-cart-checkbox-input:checked + .custom-cart-checkbox-box {
    background-color: #145a43;
    border-color: #145a43;
}
.custom-cart-checkbox-input:checked + .custom-cart-checkbox-box::after {
    content: '';
    width: 5px;
    height: 9px;
    border: solid #ffffff;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    margin-bottom: 2px;
    display: block;
}
.custom-cart-checkbox-input:indeterminate + .custom-cart-checkbox-box {
    background-color: #145a43;
    border-color: #145a43;
}
.custom-cart-checkbox-input:indeterminate + .custom-cart-checkbox-box::after {
    content: '';
    width: 10px;
    height: 2px;
    background-color: #ffffff;
    display: block;
}

/* Cart Grid */
.cart-main-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 24px;
    align-items: flex-start;
}

/* Items List */
.cart-items-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.cart-item-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    display: grid;
    grid-template-columns: auto 80px 1fr auto;
    gap: 16px;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    transition: all 0.25s ease;
}
.cart-item-card.is-unselected {
    opacity: 0.65;
    background: #fafafa;
    border-color: #f1f5f9;
}
.cart-item-card.is-selected {
    border-color: #cbd5e1;
    background: #ffffff;
}

.item-select-checkbox-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    padding-right: 4px;
}

.item-img-link {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    display: block;
    flex-shrink: 0;
}
.item-img-link img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.item-placeholder-img {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #145a43;
    color: #ffffff;
    font-size: 24px;
    font-weight: 700;
}

.item-details {
    display: flex;
    flex-direction: column;
}
.item-brand {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    color: #145a43;
    letter-spacing: 0.5px;
}
.item-title {
    font-size: 14.5px;
    font-weight: 700;
    margin: 2px 0 6px;
}
.item-title a {
    color: #0f172a;
    text-decoration: none;
}
.item-title a:hover {
    color: #145a43;
}
.item-option-badge {
    font-size: 11.5px;
    color: #1e293b;
    margin-bottom: 4px;
}
.item-addons-group {
    display: flex;
    flex-direction: column;
    gap: 3px;
    margin: 3px 0 6px;
}
.addon-badge {
    font-size: 11px;
    padding: 2px 7px;
    border-radius: 4px;
    width: fit-content;
    display: inline-block;
}
.addon-badge.gift-badge {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}
.addon-badge.engrave-badge {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}
.item-unit-price {
    font-size: 12.5px;
    color: #64748b;
}
.item-unit-price strong {
    color: #0f172a;
}

/* Item Action Controls */
.item-actions-wrapper {
    display: flex;
    align-items: center;
    gap: 16px;
}
.item-qty-form {
    display: flex;
    align-items: center;
    gap: 6px;
}
.custom-qty-picker {
    display: flex;
    align-items: center;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    overflow: hidden;
    background: #ffffff;
}
.custom-qty-picker .qty-btn {
    width: 28px;
    height: 30px;
    background: #f8fafc;
    border: none;
    font-size: 14px;
    font-weight: 700;
    color: #334155;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.custom-qty-picker .qty-btn:hover {
    background: #e2e8f0;
}
.custom-qty-picker .qty-input {
    width: 36px;
    height: 30px;
    border: none;
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    color: #0f172a;
    outline: none;
}
.btn-qty-update {
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #475569;
    font-size: 11px;
    font-weight: 600;
    padding: 6px 8px;
    border-radius: 6px;
    cursor: pointer;
}
.btn-qty-update:hover {
    background: #e2e8f0;
    color: #0f172a;
}

.item-line-total {
    text-align: right;
    min-width: 90px;
}
.item-line-total .total-label {
    display: block;
    font-size: 10px;
    color: #94a3b8;
    text-transform: uppercase;
}
.item-line-total .total-value {
    font-size: 15px;
    font-weight: 700;
    color: #145a43;
}
.btn-item-remove {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    color: #e11d48;
    font-size: 11px;
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;
}
.btn-item-remove:hover {
    background: #ffe4e6;
}

/* Guarantees Box */
.cart-guarantee-box {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px;
    margin-top: 4px;
}
.guarantee-item {
    display: flex;
    align-items: center;
    gap: 8px;
}
.guarantee-item span {
    font-size: 20px;
}
.guarantee-item strong {
    display: block;
    font-size: 12px;
    color: #0f172a;
}
.guarantee-item small {
    display: block;
    font-size: 10.5px;
    color: #64748b;
}

/* Checkout Sidebar Card */
.checkout-card-box {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    position: sticky;
    top: 90px;
}
.checkout-box-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 16px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}
.checkout-summary-lines {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 14px;
}
.summary-line {
    display: flex;
    justify-content: space-between;
    font-size: 13.5px;
    color: #475569;
}
.summary-line.total-line {
    border-top: 1px dashed #cbd5e1;
    padding-top: 10px;
    margin-top: 4px;
    font-size: 14.5px;
    color: #0f172a;
    font-weight: 700;
}
.grand-total-amount {
    font-size: 20px;
    color: #145a43;
    font-weight: 800;
}

.cart-no-selection-alert {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    color: #b45309;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 12px;
    margin-bottom: 14px;
    text-align: center;
    font-weight: 500;
}

/* Form Styles */
.checkout-data-form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.form-group-clean label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 4px;
}
.form-group-clean .req-star {
    color: #dc2626;
}
.form-group-clean input,
.form-group-clean select,
.form-group-clean textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 13px;
    color: #0f172a;
    outline: none;
    box-sizing: border-box;
    background: #ffffff;
    transition: border-color 0.2s;
}
.form-group-clean select:disabled {
    background: #f8fafc;
    color: #94a3b8;
    cursor: not-allowed;
}
.form-group-clean input:focus,
.form-group-clean select:focus,
.form-group-clean textarea:focus {
    border-color: #db2777;
    box-shadow: 0 0 0 2px rgba(219, 39, 119, 0.15);
}

.btn-submit-order {
    background: #145a43;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 13px 18px;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.5px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 6px;
    transition: all 0.2s;
}
.btn-submit-order:hover:not(:disabled) {
    background: #0f4433;
}
.btn-submit-order:disabled {
    background: #94a3b8;
    cursor: not-allowed;
    opacity: 0.7;
}

.checkout-security-note {
    margin-top: 14px;
    font-size: 11px;
    color: #64748b;
    text-align: center;
    line-height: 1.4;
}

/* Empty State */
.cart-empty-box {
    text-align: center;
    padding: 60px 20px;
    background: #ffffff;
    border-radius: 12px;
    border: 1px dashed #cbd5e1;
    max-width: 500px;
    margin: 40px auto;
}
.empty-icon {
    font-size: 44px;
    margin-bottom: 12px;
}
.cart-empty-box h2 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px;
    color: #0f172a;
}
.cart-empty-box p {
    font-size: 13.5px;
    color: #64748b;
    margin: 0 0 18px;
}
.btn-empty-shop {
    display: inline-block;
    background: #145a43;
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
}

/* Responsive */
@media (max-width: 860px) {
    .cart-main-grid {
        grid-template-columns: 1fr;
    }
    .cart-guarantee-box {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 600px) {
    .cart-item-card {
        grid-template-columns: auto 65px 1fr;
        gap: 10px;
    }
    .item-actions-wrapper {
        grid-column: 1 / -1;
        justify-content: space-between;
        border-top: 1px solid #f1f5f9;
        padding-top: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAllCheckbox = document.getElementById('selectAllCart');
    const itemCheckboxes = document.querySelectorAll('.cart-item-checkbox');
    const subtotalDisplay = document.getElementById('subtotalDisplay');
    const finalTotalText = document.getElementById('final_total_text');
    const selectedTypesCount = document.getElementById('selectedTypesCount');
    const checkoutSelectedQuantity = document.getElementById('checkoutSelectedQuantity');
    const btnProceedToCheckout = document.getElementById('btnProceedToCheckout');
    const noSelectionAlert = document.getElementById('noSelectionAlert');

    function formatVND(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount) + '₫';
    }

    function recalculateCart() {
        let totalAmount = 0;
        let totalQuantity = 0;
        let selectedCount = 0;
        const totalItems = itemCheckboxes.length;

        itemCheckboxes.forEach((checkbox) => {
            const cardId = checkbox.dataset.cardId;
            const cardEl = document.getElementById(cardId);

            if (checkbox.checked) {
                const lineTotal = parseFloat(checkbox.dataset.lineTotal) || 0;
                const quantity = parseInt(checkbox.dataset.quantity, 10) || 1;
                totalAmount += lineTotal;
                totalQuantity += quantity;
                selectedCount += 1;

                if (cardEl) {
                    cardEl.classList.add('is-selected');
                    cardEl.classList.remove('is-unselected');
                }
            } else {
                if (cardEl) {
                    cardEl.classList.add('is-unselected');
                    cardEl.classList.remove('is-selected');
                }
            }
        });

        // Update displays
        if (subtotalDisplay) subtotalDisplay.textContent = formatVND(totalAmount);
        if (finalTotalText) finalTotalText.textContent = formatVND(totalAmount);
        if (selectedTypesCount) selectedTypesCount.textContent = selectedCount;
        if (checkoutSelectedQuantity) checkoutSelectedQuantity.textContent = totalQuantity + (window.soopiT || (text => text))(" món");

        // Select All checkbox state sync
        if (selectAllCheckbox) {
            if (selectedCount === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            } else if (selectedCount === totalItems) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
        }

        // Checkout Button & Alert
        if (selectedCount === 0) {
            if (btnProceedToCheckout) {
                btnProceedToCheckout.disabled = true;
                btnProceedToCheckout.style.pointerEvents = 'none';
                btnProceedToCheckout.style.opacity = '0.5';
            }
            if (noSelectionAlert) noSelectionAlert.style.display = 'block';
        } else {
            if (btnProceedToCheckout) {
                btnProceedToCheckout.disabled = false;
                btnProceedToCheckout.style.pointerEvents = 'auto';
                btnProceedToCheckout.style.opacity = '1';
            }
            if (noSelectionAlert) noSelectionAlert.style.display = 'none';
        }
    }

    // Select all toggle listener
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            const isChecked = selectAllCheckbox.checked;
            itemCheckboxes.forEach((checkbox) => {
                checkbox.checked = isChecked;
            });
            recalculateCart();
        });
    }

    // Individual item checkbox listeners
    itemCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            recalculateCart();
        });
    });

    // Initial calculation
    recalculateCart();
});
</script>
@endsection
