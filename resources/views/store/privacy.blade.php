@extends('layouts.store')
@section('title', 'Chính sách riêng tư · Soopi')
@section('meta_description', 'Cách Soopi sử dụng thông tin tài khoản, đơn hàng và nội dung bạn cung cấp; cookie, dịch vụ liên quan và kênh liên hệ về dữ liệu cá nhân.')
@section('content')
<section class="store-container soopi-information">
    <header class="info-heading info-policy-heading"><div><span class="atelier-kicker">SOOPI / YOUR PRIVACY</span><h1>Thông tin của bạn.<br><em>Được nói rõ.</em></h1></div><div><p>Chính sách riêng tư</p><small>Cập nhật {{ config('storefront.privacy_updated_at') }}</small></div></header>
    <div class="info-policy-layout">
        <nav class="info-policy-nav" aria-label="Nội dung chính sách riêng tư">
            <a href="#du-lieu">01 · Thông tin được xử lý</a><a href="#muc-dich">02 · Mục đích sử dụng</a><a href="#dich-vu">03 · Dịch vụ liên quan</a><a href="#luu-tru">04 · Lưu trữ & bảo vệ</a><a href="#yeu-cau">05 · Yêu cầu của bạn</a>
            <a class="info-policy-contact" href="{{ route('store.contact') }}">Liên hệ Soopi ↗</a>
        </nav>
        <article class="info-policy-copy">
            <p class="info-policy-intro">Trang này giải thích việc xử lý thông tin khi bạn sử dụng website Soopi. Mỗi tính năng chỉ sử dụng thông tin liên quan đến hoạt động của tính năng đó.</p>
            <section id="du-lieu"><span class="info-index">01 / DỮ LIỆU</span><h2>Những thông tin bạn cung cấp</h2>
                <ul><li><strong>Tài khoản:</strong> tên và email để phục vụ tài khoản; mật khẩu được lưu dưới dạng băm để phục vụ đăng nhập.</li><li><strong>Đơn hàng:</strong> tên người nhận, số điện thoại, địa chỉ, sản phẩm, ghi chú, thông tin quà tặng, số tiền và trạng thái thanh toán, giao hàng.</li><li><strong>Tương tác:</strong> tin nhắn tư vấn, đánh giá và ảnh bạn gửi, mùi hương yêu thích, tủ nước hoa và lựa chọn khám phá của bạn.</li><li><strong>Thông tin kỹ thuật:</strong> phiên đăng nhập, địa chỉ IP và thông tin trình duyệt có thể được ghi nhận trong phiên hoặc nhật ký vận hành để bảo vệ và xử lý lỗi hệ thống.</li></ul>
                <p>Soopi không yêu cầu bạn cung cấp mật khẩu ngân hàng, mã PIN thẻ hoặc OTP trong phần tư vấn, đánh giá hay ghi chú đơn hàng.</p>
            </section>
            <section id="muc-dich"><span class="info-index">02 / MỤC ĐÍCH</span><h2>Để phục vụ hành trình mua sắm</h2>
                <p>Thông tin được dùng để duy trì tài khoản, xử lý đơn và thanh toán, giao hàng, hỗ trợ sau mua, tính điểm thành viên, lưu lựa chọn của bạn và ngăn ngừa thao tác gian lận.</p>
                <p>Email tài khoản được dùng cho xác minh, thông báo giao dịch và thông báo hàng về khi bạn đăng ký tính năng này. Email giao dịch giúp bạn theo dõi đơn; không phải đăng ký nhận quảng cáo.</p>
                <p>Website dùng cookie phiên để đăng nhập, bảo vệ biểu mẫu và duy trì giỏ hàng. Một số lựa chọn hiển thị như bật/tắt chuyển động được lưu trên trình duyệt. Xóa hoặc chặn dữ liệu này có thể làm mất phiên đăng nhập hoặc lựa chọn đang lưu.</p>
            </section>
            <section id="dich-vu"><span class="info-index">03 / CHIA SẺ CẦN THIẾT</span><h2>Các dịch vụ tham gia xử lý</h2>
                <ul><li><strong>Vận chuyển:</strong> GHN nhận thông tin cần thiết như người nhận, điện thoại, địa chỉ và thông tin kiện hàng khi đơn được tạo vận đơn.</li><li><strong>Thanh toán:</strong> khi chọn MoMo, thông tin giao dịch được chuyển đến cổng thanh toán để xử lý và đối soát.</li><li><strong>Email và hạ tầng:</strong> dịch vụ gửi thư, máy chủ và lưu trữ xử lý thông tin cần thiết để vận hành website và gửi thông báo.</li><li><strong>Tư vấn AI:</strong> nếu bật tính năng này, nội dung hội thoại có thể được gửi tới dịch vụ Groq để tạo câu trả lời. Bạn có thể chọn nhân viên hỗ trợ trong khung chat; không gửi dữ liệu nhạy cảm không cần thiết.</li><li><strong>Nội dung bên ngoài:</strong> video hoặc livestream, phông chữ và liên kết Zalo, Facebook, Instagram có thể kết nối tới dịch vụ bên thứ ba. Các dịch vụ đó áp dụng chính sách riêng khi bạn sử dụng.</li></ul>
                <p>Nội dung đánh giá, ảnh đánh giá, tin nhắn trong livestream và tủ nước hoa qua trang chia sẻ có thể hiển thị cho người khác. Hãy kiểm tra nội dung trước khi công khai và tránh đưa địa chỉ, số điện thoại hoặc thông tin riêng tư vào đó.</p>
            </section>
            <section id="luu-tru"><span class="info-index">04 / LƯU TRỮ</span><h2>Giữ thông tin cho đúng nhu cầu</h2>
                <p>Dữ liệu tài khoản và giao dịch được lưu để duy trì dịch vụ, đối soát, hỗ trợ khiếu nại và thực hiện các nghĩa vụ lưu giữ áp dụng. Việc xóa dữ liệu không tự động xóa mọi chứng từ giao dịch hoặc bản sao lưu liên quan.</p>
                <p>Hệ thống áp dụng phân quyền cho chức năng quản trị; chi tiết đơn hàng yêu cầu đăng nhập và kiểm tra quyền truy cập. Bạn nên giữ kín mật khẩu và đăng xuất khi dùng thiết bị chung.</p>
            </section>
            <section id="yeu-cau"><span class="info-index">05 / LIÊN HỆ</span><h2>Bạn có thể yêu cầu hỗ trợ</h2>
                <p>Bạn có thể chỉnh sửa thông tin hồ sơ được hỗ trợ trong <a href="{{ route('account.edit') }}">trang tài khoản</a>. Với yêu cầu xem lại, chỉnh sửa, xóa hoặc hạn chế xử lý dữ liệu, vui lòng liên hệ Soopi và nêu rõ nội dung cần hỗ trợ.</p>
                <p>Soopi tiếp nhận qua các kênh liên hệ bên dưới và có thể cần xác minh tài khoản trước khi xử lý, nhằm tránh cung cấp thông tin của bạn cho người khác. Một số dữ liệu giao dịch có thể cần tiếp tục được lưu phục vụ đối soát hoặc nghĩa vụ liên quan.</p>
                <a class="info-policy-button" href="{{ config('storefront.zalo_url') }}" target="_blank" rel="noopener noreferrer">Liên hệ qua Zalo {{ config('storefront.zalo_phone') }} ↗</a>
                <p><a href="{{ route('store.contact') }}">Xem đầy đủ các kênh liên hệ →</a></p>
            </section>
        </article>
    </div>
</section>
@endsection
