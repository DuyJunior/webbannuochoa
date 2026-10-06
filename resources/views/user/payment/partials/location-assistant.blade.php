<section class="delivery-location" data-delivery-location data-endpoint="{{ route('locations.current-address') }}" aria-labelledby="delivery-location-title">
    <div class="delivery-location__intro">
        <span class="delivery-location__icon" aria-hidden="true">◎</span>
        <div>
            <h3 id="delivery-location-title">{{ __('Giao đến nơi bạn đang ở') }}</h3>
            <p>{{ __('Lấy vị trí một lần để gợi ý địa chỉ. Bạn kiểm tra và xác nhận trước khi áp dụng.') }}</p>
        </div>
    </div>
    <button type="button" class="delivery-location__locate" data-locate>{{ __('Dùng vị trí hiện tại') }} <span aria-hidden="true">↗</span></button>
    <p class="delivery-location__privacy">{{ __('Khi bạn cho phép, tọa độ được gửi đến Photon để tìm địa chỉ; kết quả được lưu tạm 15 phút, không gắn với tài khoản.') }}</p>
    <p class="delivery-location__status" role="status" aria-live="polite" data-location-status></p>
    <div class="delivery-location__suggestion" data-location-result hidden>
        <span class="delivery-location__eyebrow">{{ __('ĐỊA CHỈ GỢI Ý') }}</span>
        <p class="delivery-location__address" data-location-address></p>
        <p data-location-accuracy></p>
        <p>{{ __('Kiểm tra số nhà và khu vực GHN bên dưới. Địa chỉ bản đồ có thể khác tên hành chính dùng để giao hàng.') }}</p>
        <div class="delivery-location__actions">
            <button type="button" data-location-apply>{{ __('Dùng địa chỉ này') }}</button>
            <button type="button" data-location-dismiss>{{ __('Tự nhập địa chỉ') }}</button>
        </div>
    </div>
    <small class="delivery-location__credit">© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a> · <a href="https://photon.komoot.io" target="_blank" rel="noopener noreferrer">Photon</a></small>
    @php($locationMessages = [
        'locating' => __('Đang xác định vị trí… Hãy cho phép truy cập vị trí khi trình duyệt hỏi.'),
        'searching' => __('Đã nhận vị trí. Đang tìm địa chỉ phù hợp…'),
        'unsupported' => __('Trình duyệt chưa hỗ trợ vị trí hoặc trang chưa dùng HTTPS. Vui lòng nhập địa chỉ bên dưới.'),
        'denied' => __('Bạn chưa cho phép truy cập vị trí. Hãy bật quyền vị trí trong trình duyệt hoặc nhập địa chỉ bên dưới.'),
        'timeout' => __('Lấy vị trí quá lâu. Hãy thử lại hoặc tự nhập địa chỉ.'),
        'unavailable' => __('Chưa xác định được vị trí. Hãy thử lại hoặc tự nhập địa chỉ.'),
        'failed' => __('Chưa tìm được địa chỉ phù hợp. Bạn có thể thử lại hoặc nhập địa chỉ bên dưới.'),
        'expired' => __('Phiên đăng nhập đã hết hạn. Hãy tải lại trang trước khi dùng vị trí.'),
        'limited' => __('Bạn đã thử nhiều lần. Vui lòng chờ một phút hoặc tự nhập địa chỉ.'),
        'accuracy' => __('Độ chính xác thiết bị ước tính: khoảng :meters m. Đây không phải địa chỉ giao hàng đã xác nhận.'),
        'ready' => __('Kiểm tra địa chỉ gợi ý trước khi dùng. Địa chỉ hiện tại của bạn chưa thay đổi.'),
        'applied' => __('Đã áp dụng phần địa chỉ tìm được. Hãy bổ sung số nhà và chọn những khu vực còn thiếu; phí giao hàng sẽ được tính lại.'),
        'manual' => __('Bạn có thể nhập địa chỉ và chọn khu vực giao hàng bên dưới.'),
        'changed' => __('Bạn vừa sửa địa chỉ. Gợi ý vị trí đã được bỏ để giữ nội dung mới.'),
    ])
    <script type="application/json" data-location-messages>@json($locationMessages)</script>
</section>
