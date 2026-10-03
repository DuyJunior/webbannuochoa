<link rel="stylesheet" href="{{ asset('css/product-photos.css') }}">
<section class="admin-card studio-form-section" aria-labelledby="product-shop-photos">
    <span class="studio-form-kicker">ẢNH CHỤP TẠI CỬA HÀNG</span>
    <h3 id="product-shop-photos">Ảnh thực tế sản phẩm</h3>
    <p class="studio-field-help">Chỉ tải ảnh do cửa hàng chụp đúng sản phẩm: thân chai, hộp, đáy chai hoặc tem nhãn. Không dùng ảnh AI, ảnh minh họa hoặc ảnh lấy từ nơi khác trong mục này.</p>
    <p class="studio-field-help">Ảnh được công khai trên website. Hãy bỏ thông tin cá nhân và dữ liệu vị trí (EXIF) trước khi tải lên.</p>
    @if($editing && $product->shopPhotos->isNotEmpty())
        <div class="admin-shop-photos">
            @foreach($product->shopPhotos as $photo)
                <div class="admin-shop-photo">
                    <a href="{{ $photo->url }}" target="_blank" rel="noopener" aria-label="Mở ảnh thực tế {{ $loop->iteration }} trong tab mới"><img src="{{ $photo->url }}" alt="Ảnh thực tế {{ $loop->iteration }} của {{ $product->name }}" width="180" height="180" loading="lazy"></a>
                    <label for="remove-shop-photo-{{ $photo->id }}"><input type="checkbox" id="remove-shop-photo-{{ $photo->id }}" name="remove_shop_photos[]" value="{{ $photo->id }}" @checked(in_array($photo->id, (array) old('remove_shop_photos', [])))> Xóa ảnh {{ $loop->iteration }}</label>
                </div>
            @endforeach
        </div>
        <p class="studio-field-help">Ảnh được chọn xóa sẽ được gỡ khi bạn lưu thay đổi.</p>
    @else
        <p class="studio-field-help">Chưa có ảnh thực tế. Mục này sẽ xuất hiện trên trang sản phẩm sau khi bạn tải ảnh và lưu.</p>
    @endif
    <div class="form-group mb-0">
        <label for="shop_photos">Thêm ảnh thực tế</label>
        <input type="file" id="shop_photos" name="shop_photos[]" accept="image/jpeg,image/png,image/webp" multiple class="form-control-file @if($errors->has('shop_photos') || $errors->has('shop_photos.*')) is-invalid @endif" aria-describedby="shop-photos-help">
        <small id="shop-photos-help" class="studio-field-help">Tối đa 6 ảnh mỗi sản phẩm · JPG, PNG, WEBP · Mỗi ảnh tối đa 5 MB và 8.000 × 8.000 pixel · Tổng ảnh thực tế tải mỗi lần tối đa 18 MB. Ảnh mới được thêm vào sau ảnh hiện có. Nếu biểu mẫu báo lỗi, hãy chọn lại tệp.</small>
        @foreach(array_merge($errors->get('shop_photos'), $errors->get('shop_photos.*'), $errors->get('remove_shop_photos'), $errors->get('remove_shop_photos.*')) as $messages)
            @foreach((array) $messages as $message)<div class="text-danger small mt-1">{{ $message }}</div>@endforeach
        @endforeach
    </div>
</section>
