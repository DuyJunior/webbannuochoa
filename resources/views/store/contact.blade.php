@extends('layouts.store')
@section('title', __('Liên hệ & thông tin cửa hàng · Soopi'))
@section('meta_description', __('Kết nối với Soopi qua Zalo, Facebook và Instagram. Xem thông tin hỗ trợ, theo dõi đơn hàng và chính sách mua sắm.'))
@section('content')
<section class="store-container soopi-information">
    <header class="info-heading">
        <div><span class="atelier-kicker">SOOPI / ALWAYS HERE FOR YOU</span><h1>{{ __('Một lời nhắn.') }}<br><em>{{ __('Gần nhau hơn.') }}</em></h1></div>
        <p>{{ __('Từ mùi hương đầu tiên đến món quà dành cho người thương, Soopi luôn có một nơi để bạn bắt đầu câu chuyện.') }}</p>
    </header>
    <div class="info-contact-layout">
        <div class="info-contact-card">
            <span class="atelier-kicker">{{ __('KẾT NỐI CÙNG SOOPI') }}</span><h2>{{ __('Mình có thể giúp gì cho bạn?') }}</h2>
            <p>{{ __('Hỏi về mùi hương, hình ảnh sản phẩm hoặc đơn hàng của bạn qua các kênh liên hệ dưới đây.') }}</p>
            <a class="info-contact-primary" href="{{ config('storefront.zalo_url') }}" target="_blank" rel="noopener noreferrer"><span><small>{{ __('ZALO TƯ VẤN') }}</small><strong>{{ config('storefront.zalo_phone') }}</strong></span><span aria-hidden="true">↗</span></a>
            <div class="info-social-links">
                <a href="{{ config('storefront.facebook_url') }}" target="_blank" rel="noopener noreferrer">Facebook <span aria-hidden="true">↗</span></a>
                <a href="{{ config('storefront.instagram_url') }}" target="_blank" rel="noopener noreferrer">Instagram <span aria-hidden="true">↗</span></a>
            </div>
            @if(config('storefront.address') || config('storefront.support_hours') || config('storefront.contact_email'))
                <dl class="info-shop-details">
                    @if(config('storefront.address'))<div><dt>{{ __('Địa chỉ / hình thức phục vụ') }}</dt><dd>{{ config('storefront.address') }}</dd></div>@endif
                    @if(config('storefront.support_hours'))<div><dt>{{ __('Giờ hỗ trợ') }}</dt><dd>{{ config('storefront.support_hours') }}</dd></div>@endif
                    @if(config('storefront.contact_email'))<div><dt>{{ __('Email liên hệ') }}</dt><dd><a href="mailto:{{ config('storefront.contact_email') }}">{{ config('storefront.contact_email') }}</a></dd></div>@endif
                </dl>
            @endif
            <p class="info-contact-note">{{ __('Với câu hỏi về đơn hàng, bạn chỉ cần gửi mã đơn. Không gửi mật khẩu hoặc mã OTP qua tin nhắn.') }}</p>
        </div>
        <div class="info-support-links">
            <a href="{{ route('orders.tracking') }}"><span class="info-index">01</span><div><h2>{{ __('Đơn hàng của bạn') }}</h2><p>{{ __('Đăng nhập để xem tiến trình xử lý và thông tin giao hàng.') }}</p></div><span aria-hidden="true">↗</span></a>
            <a href="{{ route('store.faq') }}#doi-tra"><span class="info-index">02</span><div><h2>{{ __('Mua sắm rõ ràng') }}</h2><p>{{ __('Đọc điều kiện hỗ trợ đổi trả trong') }} {{ config('storefront.return_days') }} {{ __('ngày và cách liên hệ khi cần.') }}</p></div><span aria-hidden="true">↗</span></a>
            <a href="{{ route('store.privacy') }}"><span class="info-index">03</span><div><h2>{{ __('Thông tin của bạn') }}</h2><p>{{ __('Tìm hiểu dữ liệu được sử dụng như thế nào và cách gửi yêu cầu hỗ trợ.') }}</p></div><span aria-hidden="true">↗</span></a>
        </div>
    </div>
    <div class="info-closing"><span>{{ __('CHỌN HƯƠNG. CHỌN CHÍNH MÌNH.') }}</span><a href="{{ route('store.finder') }}">{{ __('Khám phá mùi hương phù hợp') }} <span aria-hidden="true">↗</span></a></div>
</section>
@endsection
