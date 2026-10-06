@php
    $location = $shopLocation;
    $coordinates = $location['latitude'].','.$location['longitude'];
    $directions = 'https://www.google.com/maps/dir/?api=1&destination='.rawurlencode($coordinates);
@endphp
<section class="store-container soopi-location" id="vi-tri-shop" aria-labelledby="shop-location-heading"
    data-shop-location data-latitude="{{ $location['latitude'] }}" data-longitude="{{ $location['longitude'] }}">
    <header class="soopi-location-heading"><span>SOOPI / THE SCENT DESTINATION</span><span>{{ __('MỘT ĐIỂM HẸN, VẠN DẤU HƯƠNG') }}</span></header>
    <div class="soopi-location-layout">
        <div class="soopi-location-copy">
            <span class="soopi-location-number" aria-hidden="true">01 /</span>
            <h2 id="shop-location-heading">{{ __('Hẹn bạn tại') }}<br><em>{{ $location['name'] }}.</em></h2>
            <p class="soopi-location-intro">{{ __('Dành một chút thời gian cho chính mình. Để mỗi mùi hương trở thành một cuộc gặp gỡ đáng nhớ.') }}</p>
            <dl class="soopi-location-details">
                <div><dt>{{ __('ĐỊA CHỈ CỬA HÀNG') }}</dt><dd data-shop-address>{{ $location['address'] }}</dd></div>
                @if($location['hours'])<div><dt>{{ __('GIỜ ĐÓN KHÁCH') }}</dt><dd>{{ $location['hours'] }}</dd></div>@endif
            </dl>
            <div class="soopi-location-actions"><a class="soopi-location-directions" href="{{ $directions }}" target="_blank" rel="noopener noreferrer">{{ __('Dẫn lối đến Soopi') }} <span aria-hidden="true">↗</span></a><button type="button" data-copy-shop-address hidden>{{ __('Sao chép địa chỉ') }}</button></div>
            <p class="soopi-location-copy-status" data-copy-status role="status" aria-live="polite"></p>
            <a class="soopi-location-concierge" href="{{ config('storefront.zalo_url') }}" target="_blank" rel="noopener noreferrer"><span>{{ __('TƯ VẤN RIÊNG CHO BẠN') }}<strong>{{ config('storefront.zalo_phone') }}</strong></span><span aria-hidden="true">↗</span></a>
        </div>
        <div class="soopi-location-panel">
            <div class="soopi-location-map-title"><span class="soopi-location-seal" aria-hidden="true">S</span><div><strong>{{ $location['name'] }}</strong><span>PERFUME STUDIO</span></div><span class="soopi-location-map-tag">{{ __('BẢN ĐỒ') }}</span></div>
            <iframe class="soopi-location-map" src="https://maps.google.com/maps?q={{ $coordinates }}&amp;z=16&amp;output=embed" title="{{ __('Bản đồ vị trí') }} {{ $location['name'] }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            <div class="soopi-location-map-footer"><span>{{ __('Điểm đến của những dấu hương riêng.') }}</span><a href="{{ $directions }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Mở vị trí shop trên Google Maps') }}">Google Maps ↗</a></div>
        </div>
    </div>
    @if($location['is_demo'])<p class="soopi-location-note">{{ __('Địa điểm minh họa cho bài tập; không phải cửa hàng thực tế tại địa chỉ này.') }}</p>@endif
</section>
