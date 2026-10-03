<dialog class="product-quick-view" id="product-quick-view" aria-labelledby="quick-view-title" aria-describedby="quick-view-status" data-quick-view-dialog>
    <div class="qv-header">
        <span>SOOPI / VOTRE PARFUM</span>
        <button type="button" class="qv-close" data-qv-close aria-label="{{ __('Đóng xem nhanh') }}">@include('partials.icon', ['name' => 'close', 'size' => 21])</button>
    </div>
    <div class="qv-scroll">
        <p class="qv-status" id="quick-view-status" data-qv-status role="status" aria-live="polite"></p>
        <div class="qv-loading" data-qv-loading aria-hidden="true"><div></div><span></span><span></span></div>
        <div class="qv-error" data-qv-error hidden>
            <p>{{ __('Mùi hương vẫn đang chờ bạn. Hãy thử lại hoặc mở trang chi tiết.') }}</p>
            <button class="qv-primary" type="button" data-qv-retry>{{ __('Thử tải lại') }} <span aria-hidden="true">↻</span></button>
            <a class="qv-detail" data-qv-fallback href="{{ route('home') }}">{{ __('Mở trang sản phẩm') }} <span aria-hidden="true">↗</span></a>
        </div>
        <article class="qv-product" data-qv-product hidden>
            <figure class="qv-picture">
                <img data-qv-image alt="" width="640" height="480" decoding="async" hidden>
                <span class="qv-image-fallback" data-qv-image-fallback>@include('partials.brand-mark', ['size' => 80])</span>
                <figcaption data-qv-image-caption hidden>{{ __('Phối cảnh bộ sưu tập Soopi') }}</figcaption>
            </figure>
            <div class="qv-information">
                <span class="qv-brand" data-qv-brand></span>
                <h2 id="quick-view-title" data-qv-title>{{ __('Xem nhanh mùi hương') }}</h2>
                <p class="qv-concentration" data-qv-concentration></p>
                <p class="qv-description" data-qv-description></p>
                <div class="qv-price" aria-live="polite"><strong data-qv-price></strong><del data-qv-original hidden></del></div>
                <form method="POST" data-qv-form>
                    <input type="hidden" name="_token" data-qv-token>
                    <fieldset class="qv-volumes"><legend>{{ __('Chọn dung tích') }}</legend><div data-qv-variants></div></fieldset>
                    <p class="qv-availability" data-qv-stock role="status" aria-live="polite"></p>
                    <div class="qv-quantity-line">
                        <label for="quick-view-quantity">{{ __('Số lượng') }}</label>
                        <div class="qv-quantity">
                            <button type="button" data-qv-minus aria-label="{{ __('Giảm số lượng') }}">−</button>
                            <input type="number" name="quantity" id="quick-view-quantity" data-qv-quantity min="1" max="1" value="1" step="1" inputmode="numeric" required>
                            <button type="button" data-qv-plus aria-label="{{ __('Tăng số lượng') }}">+</button>
                        </div>
                    </div>
                    <button type="submit" class="qv-primary" data-qv-submit>{{ __('Thêm vào giỏ hàng') }} <span aria-hidden="true">↗</span></button>
                    <a class="qv-primary" data-qv-login href="{{ route('login') }}" hidden>{{ __('Đăng nhập để thêm giỏ') }} <span aria-hidden="true">↗</span></a>
                    <p class="qv-signin-note" data-qv-signin-note hidden>{{ __('Bạn sẽ trở lại sản phẩm sau khi đăng nhập.') }}</p>
                    <p class="qv-sold-out" data-qv-sold-out hidden>{{ __('Dung tích này hiện chưa thể thêm vào giỏ.') }}</p>
                </form>
                <aside class="qv-policies" aria-label="{{ __('Thông tin giao hàng và hỗ trợ') }}">
                    <p>@include('partials.icon', ['name' => 'truck', 'size' => 18])<span>{{ __('Phí giao hàng được xác nhận khi thanh toán.') }}</span></p>
                    <p>@include('partials.icon', ['name' => 'shield', 'size' => 18])<a href="{{ route('store.faq') }}#doi-tra">{{ __('Điều kiện đổi trả trong') }} {{ config('storefront.return_days', 7) }} {{ __('ngày') }} <span aria-hidden="true">↗</span></a></p>
                    @if(config('storefront.zalo_url'))<a class="qv-support" href="{{ config('storefront.zalo_url') }}" target="_blank" rel="noopener noreferrer">{{ __('Cần tư vấn? Trò chuyện cùng Soopi trên Zalo') }} <span aria-hidden="true">↗</span></a>@endif
                </aside>
                <a class="qv-detail" data-qv-detail href="{{ route('home') }}">{{ __('Chi tiết mùi hương & thông tin mua hàng') }} <span aria-hidden="true">↗</span></a>
            </div>
        </article>
    </div>
</dialog>
