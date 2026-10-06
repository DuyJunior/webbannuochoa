<section class="delivery-location" data-delivery-location data-phase="idle" data-endpoint="{{ route('locations.current-address') }}" aria-labelledby="delivery-location-title">
    <div class="delivery-location__main">
        <div class="delivery-location__art" aria-hidden="true">
            <svg viewBox="0 0 200 210" fill="none" focusable="false">
                <rect x="1" y="1" width="198" height="208" rx="80" fill="#F0E7DF"/>
                <path d="M-8 55L206 152M21 -8L89 220M130 -8L57 217M-8 132L208 49M-8 174L208 185" stroke="#FCF9F4" stroke-width="17"/>
                <path d="M-8 55L206 152M21 -8L89 220M130 -8L57 217M-8 132L208 49M-8 174L208 185" stroke="#D3C1B6" stroke-width=".7"/>
                <path d="M153 3C112 55 204 81 161 127C125 166 171 179 158 217" stroke="#C8D7CF" stroke-width="12"/>
                <ellipse cx="99" cy="142" rx="32" ry="9" fill="#59364A" opacity=".09"/>
                <circle class="delivery-location__ripple" cx="99" cy="126" r="34" stroke="#A9808E" stroke-width="1"/>
                <circle cx="99" cy="126" r="48" stroke="#A9808E" stroke-width=".7" opacity=".35"/>
                <g class="delivery-location__pin">
                    <path d="M99 130C91 121 73 105 73 88A26 26 0 0 1 125 88C125 105 107 121 99 130Z" fill="#533346"/>
                    <path d="M99 67A21 21 0 0 0 78 88" stroke="#C8A8B8" stroke-width="1.5" stroke-linecap="round"/>
                    <circle cx="99" cy="88" r="10" fill="#FBF6EF"/>
                    <circle cx="99" cy="88" r="4" fill="#9D7288"/>
                </g>
                <circle cx="44" cy="56" r="4" fill="#BBA188"/><circle cx="155" cy="172" r="3" fill="#BBA188"/>
            </svg>
            <span>SOOPI / DELIVERY</span>
        </div>
        <div class="delivery-location__intro">
            <span class="delivery-location__eyebrow">{{ __('MỘT CHẠM · GẦN BẠN HƠN') }}</span>
            <h3 id="delivery-location-title">{{ __('Hương thơm, gửi đến bạn.') }}</h3>
            <p>{{ __('Để Soopi tìm địa chỉ gần bạn. Bạn chỉ cần kiểm tra và thêm số nhà trước khi nhận hàng.') }}</p>
            <div class="delivery-location__controls">
                <button type="button" class="delivery-location__locate" data-locate>
                    <svg class="delivery-location__target" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2.5"/><path d="M12 2V5M12 19V22M2 12H5M19 12H22"/></svg>
                    <span data-locate-label>{{ __('Dùng vị trí hiện tại') }}</span><span class="delivery-location__arrow" aria-hidden="true">↗</span>
                </button>
                <button type="button" class="delivery-location__manual" data-location-manual>{{ __('Tự nhập địa chỉ') }}</button>
            </div>
            <span class="delivery-location__state" data-location-state>{{ __('Chỉ định vị khi bạn cho phép') }}</span>
        </div>
    </div>
    <ol class="delivery-location__steps" aria-label="{{ __('Các bước xác định địa chỉ') }}">
        <li data-location-step="locating"><span>01</span>{{ __('Lấy vị trí') }}</li>
        <li data-location-step="searching"><span>02</span>{{ __('Tìm địa chỉ') }}</li>
        <li data-location-step="ready"><span>03</span>{{ __('Bạn xác nhận') }}</li>
    </ol>
    <p class="delivery-location__status" role="status" aria-live="polite" data-location-status data-toast-source="info" data-toast-group="delivery-location"></p>
    <div class="delivery-location__suggestion" data-location-result hidden>
        <div class="delivery-location__result-heading"><span class="delivery-location__eyebrow">{{ __('ĐỊA CHỈ GỢI Ý') }}</span><span class="delivery-location__result-badge">{{ __('Chờ bạn xác nhận') }}</span></div>
        <p class="delivery-location__address" data-location-address></p>
        <p data-location-accuracy></p>
        <p data-location-match>{{ __('Kiểm tra số nhà và khu vực GHN bên dưới. Địa chỉ bản đồ có thể khác tên hành chính dùng để giao hàng.') }}</p>
        <div class="delivery-location__actions">
            <button type="button" data-location-apply>{{ __('Dùng địa chỉ này') }}</button>
            <button type="button" data-location-dismiss>{{ __('Tự nhập địa chỉ') }}</button>
        </div>
    </div>
    <div class="delivery-location__footer">
        <details class="delivery-location__privacy"><summary>{{ __('Quyền riêng tư khi định vị') }}</summary><p>{{ __('Khi bạn cho phép, tọa độ được gửi đến Photon để tìm địa chỉ; kết quả được lưu tạm 15 phút, không gắn với tài khoản.') }}</p></details>
        <small class="delivery-location__credit">© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap</a> · <a href="https://photon.komoot.io" target="_blank" rel="noopener noreferrer">Photon</a></small>
    </div>
    @php($locationMessages = [
        'buttonIdle' => __('Dùng vị trí hiện tại'),
        'buttonLocating' => __('Đang lấy vị trí…'),
        'buttonSearching' => __('Đang tìm địa chỉ…'),
        'buttonRetry' => __('Thử định vị lại'),
        'stateIdle' => __('Chỉ định vị khi bạn cho phép'),
        'stateLocating' => __('Cho phép vị trí trong trình duyệt để tiếp tục'),
        'stateSearching' => __('Đang đối chiếu khu vực giao hàng'),
        'stateReady' => __('Đã có gợi ý · Bạn kiểm tra bên dưới'),
        'stateError' => __('Chưa lấy được vị trí · Bạn vẫn có thể tự nhập'),
        'stateLookupError' => __('Đã lấy vị trí · Chưa tìm được địa chỉ'),
        'connection' => __('Đã lấy được vị trí nhưng kết nối đến bản đồ bị gián đoạn. Hãy thử lại hoặc tự nhập địa chỉ.'),
        'busy' => __('Dịch vụ bản đồ đang bận. Vui lòng chờ vài giây rồi thử lại hoặc tự nhập địa chỉ.'),
        'notFound' => __('Bản đồ chưa có địa chỉ đủ chi tiết tại vị trí này. Vui lòng tự nhập địa chỉ giao hàng.'),
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
