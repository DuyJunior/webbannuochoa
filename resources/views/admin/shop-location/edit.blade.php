@extends('layouts.admin')
@section('title', 'Vị trí cửa hàng')
@section('page_title', 'Vị trí cửa hàng')
@section('content')
<div class="studio-page-heading"><div><span class="studio-form-kicker">SOOPI / STORE LOCATION</span><h2>Một địa chỉ. Đồng bộ mọi nơi.</h2><p class="text-muted">Thay đổi chỉ xuất hiện trên website sau khi bạn bấm Lưu vị trí.</p></div><a class="btn btn-outline-secondary" href="{{ route('store.contact') }}#vi-tri-shop" target="_blank" rel="noopener">Xem trên website ↗</a></div>
<form method="POST" action="{{ route('admin.shop-location.update') }}" data-location-editor>
    @csrf @method('PUT')
    @if($errors->any())<div class="alert alert-danger" role="alert"><strong>Vui lòng kiểm tra lại thông tin.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="sl-editor-grid">
        <section class="admin-card studio-form-section">
            <h3>Thông tin điểm hẹn</h3>
            <div class="form-group"><label for="shop-name">Tên hiển thị</label><input class="form-control" id="shop-name" name="name" maxlength="80" value="{{ old('name', $location['name']) }}" required></div>
            <div class="form-group"><label for="shop-address">Địa chỉ</label><textarea class="form-control" id="shop-address" name="address" maxlength="255" rows="2" required>{{ old('address', $location['address']) }}</textarea><small class="studio-field-help">Địa chỉ và tọa độ là hai thông tin riêng. Khi đổi địa chỉ, hãy cập nhật ghim tương ứng.</small></div>
            <div class="form-group"><label for="shop-hours">Giờ mở cửa (không bắt buộc)</label><input class="form-control" id="shop-hours" name="hours" maxlength="160" placeholder="Ví dụ: 09:00 – 21:00, mỗi ngày" value="{{ old('hours', $location['hours']) }}"></div>
            <hr><h3>Đặt lại ghim</h3>
            <p class="studio-field-help">Trên Google Maps, nhấp chuột phải vào điểm muốn đặt shop, bấm dòng tọa độ để sao chép rồi dán bên dưới. Không cần API key.</p>
            <label for="shop-coordinate-pair">Dán tọa độ từ Google Maps</label><div class="sl-coordinate-paste"><input class="form-control" id="shop-coordinate-pair" data-coordinate-pair placeholder="21.0205, 105.76393"><button type="button" class="btn btn-outline-secondary" data-apply-coordinates>Áp dụng</button></div>
            <div class="sl-coordinate-fields">
                <div class="form-group"><label for="shop-lat">Vĩ độ</label><input class="form-control" id="shop-lat" name="latitude" type="number" min="-85" max="85" step="any" value="{{ old('latitude', $location['latitude']) }}" required></div>
                <div class="form-group"><label for="shop-lng">Kinh độ</label><input class="form-control" id="shop-lng" name="longitude" type="number" min="-180" max="180" step="any" value="{{ old('longitude', $location['longitude']) }}" required></div>
            </div>
            <div class="form-group"><input type="hidden" name="is_demo" value="0"><label><input type="checkbox" name="is_demo" value="1" @checked(old('is_demo', $location['is_demo']))> Hiển thị ghi chú địa điểm minh họa cho bài tập</label></div>
        </section>
        <section class="admin-card studio-form-section sl-editor-preview">
            <span class="studio-form-kicker">XEM TRƯỚC / CHƯA LƯU</span><h3 data-preview-name>{{ old('name', $location['name']) }}</h3>
            <iframe data-location-preview title="Xem trước vị trí cửa hàng" src="https://maps.google.com/maps?q={{ $location['latitude'] }},{{ $location['longitude'] }}&amp;z=16&amp;output=embed" referrerpolicy="strict-origin-when-cross-origin"></iframe>
            <div data-pin-map hidden aria-label="Bản đồ kéo thả ghim cửa hàng"></div>
            <p class="sl-editor-status" data-editor-status role="status">Nhập tọa độ hoặc bật bản đồ kéo ghim để chọn vị trí mới.</p>
            <button type="button" class="btn btn-outline-secondary" data-enable-pin>Bật bản đồ kéo ghim</button>
            <p class="studio-field-help mt-3">Khi bản đồ tương tác tải được: nhấp vào một điểm hoặc kéo ghim để thay đổi. Nếu không tải được nền bản đồ, bạn vẫn có thể dán tọa độ và xem trên Google ở trên.</p>
        </section>
    </div>
    <div class="studio-form-actions"><span class="studio-field-help">Cập nhật trang chủ, trang liên hệ và địa chỉ ở chân trang.</span><button class="btn btn-primary" type="submit">Lưu vị trí</button></div>
</form>
@endsection
