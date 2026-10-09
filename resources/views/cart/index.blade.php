@extends('layouts.store')

@section('title', __('Giỏ hàng · Soopi'))

@section('content')
<section class="store-container cart-page-modern cart-redesign">
    <header class="cart-top-bar">
        <div class="cart-heading-copy">
            <span class="cart-eyebrow">SOOPI / {{ __('GIỎ HÀNG') }}</span>
            <div class="cart-title-wrap">
                <h1 class="cart-page-title">{{ __('Giỏ hàng của bạn') }}</h1>
                <span class="cart-item-count-pill" id="headerItemCountPill">{{ $items->sum('quantity') }} {{ __('sản phẩm') }}</span>
            </div>
            <p>{{ __('Kiểm tra lựa chọn của bạn trước khi thanh toán.') }}</p>
        </div>
        <a class="cart-back-btn" href="{{ route('home') }}#san-pham">{{ __('Tiếp tục khám phá') }} <span aria-hidden="true">↗</span></a>
    </header>
    <ol class="cart-journey" aria-label="{{ __('Các bước mua hàng') }}">
        <li aria-current="step"><span>01</span>{{ __('Giỏ hàng') }}</li>
        <li><span>02</span>{{ __('Thanh toán') }}</li>
        <li><span>03</span>{{ __('Hoàn tất') }}</li>
    </ol>

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
        <div class="cart-main-grid">
            {{-- Left: Item List --}}
            <div class="cart-items-list">
                {{-- Select All Control Bar --}}
                <div class="cart-select-all-card">
                    <label class="custom-cart-checkbox-label" for="selectAllCart">
                        <input type="checkbox" id="selectAllCart" class="custom-cart-checkbox-input" checked>
                        <span class="custom-cart-checkbox-box"></span>
                        <span class="select-all-label-text">{{ __('Chọn tất cả') }} <span class="cart-line-count">(<span id="totalItemTypesCount">{{ $items->count() }}</span>)</span></span>
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
                                       aria-label="{{ __('Chọn :name để thanh toán', ['name' => ($item['is_discovery_box'] || $item['is_gift_bundle']) ? $item['custom_title'] : $product->localized_name]) }}"
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
                                <img src="{{ $product->image_src }}" alt="{{ $product->localized_name }}">
                            @else
                                <div class="item-placeholder-img">{{ mb_substr($product->brand, 0, 1) }}</div>
                            @endif
                        </a>

                        <div class="item-details">
                            <span class="item-brand">{{ $product->brand }}</span>
                            <h2 class="item-title">
                                <a href="{{ route('perfumes.show', $product) }}">{{ ($item['is_discovery_box'] || $item['is_gift_bundle']) ? $item['custom_title'] : $product->localized_name }}</a>
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
                                    <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="{{ max(1, $item['max_quantity']) }}" class="qty-input" aria-label="{{ __('Số lượng') }} {{ ($item['is_discovery_box'] || $item['is_gift_bundle']) ? $item['custom_title'] : $product->localized_name }}">
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
                                <button class="btn-item-remove" type="submit" title="{{ __('Xóa khỏi giỏ hàng') }}" onclick="return confirm({{ \Illuminate\Support\Js::from(__('Bạn có chắc muốn xóa sản phẩm này?')) }})">
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
                    <span class="cart-eyebrow">{{ __('LỰA CHỌN CỦA BẠN') }}</span>
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
                            <strong class="grand-total-amount" id="final_total_text" aria-live="polite" aria-atomic="true">{{ number_format($subtotal, 0, ',', '.') }}₫</strong>
                        </div>
                    </div>

                    <p class="cart-shipping-hint">@include('partials.icon', ['name' => 'truck', 'size' => 18])<span>{{ __('Phí giao hàng được báo ở bước thanh toán, trước khi bạn đặt hàng.') }}</span></p>

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
