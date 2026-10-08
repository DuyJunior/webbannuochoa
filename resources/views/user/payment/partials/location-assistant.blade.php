<section class="delivery-location" data-delivery-location data-phase="idle" data-endpoint="{{ route('locations.current-address') }}" aria-labelledby="delivery-location-title">
    <div class="delivery-location__main">
        <div class="delivery-location__map">
            <div class="delivery-location__map-heading"><span class="delivery-location__map-seal" aria-hidden="true">S</span><div><strong>SOOPI</strong><span>{{ __('GẦN BẠN HƠN') }}</span></div><span class="delivery-location__map-tag">Google Maps</span></div>
            <iframe data-delivery-google-map class="delivery-location__map-canvas" src="https://maps.google.com/maps?q=Hanoi%2CVietnam&amp;z=12&amp;output=embed" title="{{ __('Bản đồ xem trước khu vực Hà Nội, chưa phải vị trí của bạn') }}" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            <div class="delivery-location__map-caption"><span data-delivery-map-caption>{{ __('Xem trước Hà Nội · Chưa định vị') }}</span><a data-delivery-map-link href="https://www.google.com/maps/search/?api=1&amp;query=Hanoi%2CVietnam" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Mở bản đồ lớn trên Google Maps') }}">↗</a></div>
            <p class="delivery-location__map-note">{{ __('Bản đồ chỉ để tham khảo; địa chỉ giao hàng là các ô bạn xác nhận bên dưới.') }}</p>
        </div>
        <div class="delivery-location__intro">
            <span class="delivery-location__eyebrow">{{ __('MỘT CHẠM · GẦN BẠN HƠN') }}</span>
            <h3 id="delivery-location-title">{{ __('Điền địa chỉ bằng vị trí của bạn.') }}</h3>
            <p>{{ __('Gợi ý khu vực và tên đường tìm được. Bạn kiểm tra và bổ sung số nhà, căn hộ.') }}</p>
            <div class="delivery-location__controls">
                <button type="button" class="delivery-location__locate" data-locate>
                    <svg class="delivery-location__target" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2.5"/><path d="M12 2V5M12 19V22M2 12H5M19 12H22"/></svg>
                    <span data-locate-label>{{ __('Dùng vị trí hiện tại') }}</span><span class="delivery-location__arrow" aria-hidden="true">↗</span>
                </button>
                <button type="button" class="delivery-location__manual" data-location-manual>{{ __('Tự nhập địa chỉ') }}</button>
            </div>
            <span class="delivery-location__state" data-location-state>{{ __('Chỉ định vị khi bạn cho phép') }}</span>
            <p class="delivery-location__consent">{{ __('Khi định vị, tọa độ được gửi tới Google để hiển thị bản đồ và Photon để tìm địa chỉ.') }}</p>
        </div>
    </div>
    <ol class="delivery-location__steps" aria-label="{{ __('Các bước xác định địa chỉ') }}">
        <li data-location-step="locating"><span>01</span>{{ __('Lấy vị trí') }}</li>
        <li data-location-step="searching"><span>02</span>{{ __('Tìm địa chỉ') }}</li>
        <li data-location-step="applying"><span>03</span>{{ __('Điền vào biểu mẫu') }}</li>
    </ol>
    <p class="delivery-location__status" role="status" aria-live="polite" data-location-status data-toast-source="info" data-toast-group="delivery-location"></p>
    <div class="delivery-location__suggestion" data-location-result hidden>
        <div class="delivery-location__result-heading"><span class="delivery-location__eyebrow">{{ __('ĐỊA CHỈ GỢI Ý') }}</span><span class="delivery-location__result-badge">{{ __('Chờ bạn xác nhận') }}</span></div>
        <p class="delivery-location__address" data-location-address></p>
        <p data-location-accuracy></p>
        <p data-location-match>{{ __('Kiểm tra số nhà và khu vực GHN bên dưới. Địa chỉ bản đồ có thể khác tên hành chính dùng để giao hàng.') }}</p>
        <div class="delivery-location__actions">
            <button type="button" data-location-apply>{{ __('Xác nhận và điền địa chỉ') }}</button>
            <button type="button" data-location-dismiss>{{ __('Tự nhập địa chỉ') }}</button>
        </div>
    </div>
    <div class="delivery-location__footer">
        <details class="delivery-location__privacy"><summary>{{ __('Quyền riêng tư khi định vị') }}</summary><p>{{ __('Bản đồ Google ban đầu chỉ xem trước Hà Nội. Chỉ lấy vị trí thiết bị một lần khi bạn bấm nút và cho phép; tọa độ được gửi đến Google để hiển thị bản đồ và qua máy chủ Soopi đến Photon để tìm địa chỉ, không theo dõi vị trí liên tục. Địa chỉ gợi ý được lưu tạm 15 phút, không gắn với tài khoản. Nội dung bạn đã nhập chỉ được thay thế sau khi bạn xác nhận.') }}</p></details>
        <small class="delivery-location__credit">© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> · <a href="https://photon.komoot.io" target="_blank" rel="noopener noreferrer">Photon</a></small>
    </div>
    @php($locationMessages = [
        'mapDevice' => __('Vị trí thiết bị · Sai số khoảng :meters m'),
        'mapDeviceTitle' => __('Bản đồ vị trí thiết bị, không phải địa chỉ giao hàng đã xác nhận'),
        'buttonIdle' => __('Dùng vị trí hiện tại'),
        'buttonLocating' => __('Đang lấy vị trí…'),
        'buttonSearching' => __('Đang tìm địa chỉ…'),
        'buttonApplying' => __('Đang điền địa chỉ…'),
        'stateApplying' => __('Đang điền khu vực và cập nhật phí giao hàng…'),
        'statePartial' => __('Đã điền một phần · Kiểm tra các ô còn thiếu'),
        'stateFilled' => __('Đã điền khu vực · Kiểm tra số nhà, tên đường'),
        'filled' => __('Đã tự điền: :fields.'),
        'noneFilled' => __('Chưa khớp được khu vực giao hàng. Vui lòng chọn các ô bên dưới.'),
        'remaining' => __('Bạn kiểm tra và bổ sung: :fields.'),
        'province' => __('tỉnh/thành phố'),
        'district' => __('quận/huyện'),
        'ward' => __('phường/xã'),
        'houseAndStreet' => __('số nhà, tên đường'),
        'checkStreetAndHouse' => __('số nhà; kiểm tra tên đường/ngõ gợi ý gần vị trí'),
        'buttonRetry' => __('Thử định vị lại'),
        'stateIdle' => __('Chỉ định vị khi bạn cho phép'),
        'stateLocating' => __('Cho phép vị trí trong trình duyệt để tiếp tục'),
        'stateSearching' => __('Đang tìm tên địa chỉ từ vị trí của bạn'),
        'stateReady' => __('Đã có gợi ý · Bạn kiểm tra bên dưới'),
        'stateError' => __('Chưa lấy được vị trí · Bạn vẫn có thể tự nhập'),
        'stateLookupError' => __('Đã lấy vị trí · Chưa tìm được địa chỉ'),
        'connection' => __('Đã lấy được vị trí nhưng dịch vụ tìm địa chỉ bị gián đoạn. Hãy thử lại hoặc tự nhập địa chỉ.'),
        'busy' => __('Dịch vụ tìm địa chỉ đang bận. Vui lòng chờ vài giây rồi thử lại hoặc tự nhập địa chỉ.'),
        'notFound' => __('Chưa tìm được địa chỉ đủ chi tiết tại vị trí này. Vui lòng tự nhập địa chỉ giao hàng.'),
        'serviceUnavailable' => __('Dịch vụ tìm địa chỉ tạm thời chưa phản hồi. Bạn vẫn có thể tự nhập địa chỉ để tiếp tục.'),
        'partial' => __('Đã tìm được khu vực gần bạn nhưng chưa đối chiếu đủ với GHN. Sau khi áp dụng, hãy chọn các khu vực còn thiếu và bổ sung số nhà.'),
        'areaOnly' => __('Chỉ tìm được khu vực gần bạn, chưa xác định được tên đường. Hãy bổ sung số nhà, tên đường và chọn đủ khu vực giao hàng sau khi áp dụng.'),
        'check' => __('Kiểm tra số nhà và khu vực GHN bên dưới. Địa chỉ bản đồ có thể khác tên hành chính dùng để giao hàng.'),
        'stateApplied' => __('Đã điền gợi ý · Hãy bổ sung số nhà'),
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
