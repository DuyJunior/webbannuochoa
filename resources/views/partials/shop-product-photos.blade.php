@if($perfume->shopPhotos->isNotEmpty())
    @push('page-styles')
        <link rel="stylesheet" href="{{ asset('css/product-photos.css') }}">
    @endpush
    @push('scripts')
        <script defer src="{{ asset('js/product-photos.js') }}"></script>
    @endpush
    <section class="shop-photo-gallery" data-shop-photo-gallery aria-labelledby="shop-photo-title">
        <div class="shop-photo-heading"><span>GÓC NHÌN TẠI CỬA HÀNG</span><h2 id="shop-photo-title">Ảnh thực tế sản phẩm</h2></div>
        <p class="shop-photo-intro">Ảnh do cửa hàng cung cấp để bạn xem chi tiết chai và bao bì.</p>
        <figure class="shop-photo-feature">
            <a data-shop-photo-full href="{{ $perfume->shopPhotos->first()->url }}" target="_blank" rel="noopener" aria-label="Mở ảnh thực tế đang chọn ở kích thước đầy đủ trong tab mới">
                <img data-shop-photo-main src="{{ $perfume->shopPhotos->first()->url }}" alt="Ảnh thực tế 1 của {{ $perfume->name }}" width="640" height="640" loading="lazy">
            </a>
            <figcaption><span data-shop-photo-caption aria-live="polite">Ảnh 1 / {{ $perfume->shopPhotos->count() }}</span><span>Chạm ảnh để xem lớn ↗</span></figcaption>
        </figure>
        @if($perfume->shopPhotos->count() > 1)
            <div class="shop-photo-thumbnails" role="group" aria-label="Chọn ảnh thực tế sản phẩm">
                @foreach($perfume->shopPhotos as $photo)
                    <a href="{{ $photo->url }}" data-shop-photo data-photo-position="{{ $loop->iteration }}" data-photo-count="{{ $loop->count }}" data-photo-alt="Ảnh thực tế {{ $loop->iteration }} của {{ $perfume->name }}" aria-label="Xem ảnh thực tế {{ $loop->iteration }} của {{ $perfume->name }}" @if($loop->first) aria-current="true" @endif target="_blank" rel="noopener"><img src="{{ $photo->url }}" alt="" width="80" height="80" loading="lazy"><span>{{ $loop->iteration }}</span></a>
                @endforeach
            </div>
        @endif
    </section>
@endif
