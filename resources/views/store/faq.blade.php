@extends('layouts.store')

@section('title', 'Câu hỏi thường gặp · Soopi')

@push('styles')
<meta name="description" content="Giải đáp mọi thắc mắc về nước hoa chính hãng, đổi trả, giao hàng và thanh toán tại Soopi.">
<style>
/* ── FAQ PAGE ── */
.ht-faq-page {
    max-width: 820px;
    margin: 0 auto;
    padding: 60px 24px 100px;
}
.ht-faq-page > header {
    text-align: center;
    margin-bottom: 56px;
}
.ht-faq-page > header h1 {
    font: 400 clamp(36px, 5vw, 56px) Georgia, serif;
    color: #3b2c34;
    margin: 14px 0 16px;
}
.ht-faq-page > header p {
    color: #7a6570;
    font-size: 17px;
    line-height: 1.7;
    max-width: 560px;
    margin: 0 auto;
}
.ht-faq-tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 44px;
}
.ht-faq-tab {
    padding: 9px 20px;
    border-radius: 999px;
    border: 1.5px solid #e8cdd8;
    background: #fff;
    color: #8a6677;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all .18s;
    text-decoration: none;
}
.ht-faq-tab:hover,
.ht-faq-tab.active {
    background: #c27090;
    border-color: #c27090;
    color: #fff;
}
.ht-faq-group {
    margin-bottom: 48px;
}
.ht-faq-group-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .13em;
    text-transform: uppercase;
    color: #b07490;
    margin-bottom: 18px;
}
.ht-faq-group-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #efdde4;
}
.ht-faq-item {
    border-bottom: 1px solid #f0e0e7;
}
.ht-faq-question {
    width: 100%;
    background: none;
    border: none;
    text-align: left;
    padding: 20px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    font-size: 16px;
    font-weight: 600;
    color: #3d2f37;
    cursor: pointer;
    line-height: 1.4;
    transition: color .15s;
}
.ht-faq-question:hover { color: #c27090; }
.ht-faq-chevron {
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #f5e8ee;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .25s, background .18s;
}
.ht-faq-item.open .ht-faq-chevron {
    transform: rotate(180deg);
    background: #c27090;
    color: #fff;
}
.ht-faq-chevron svg { display: block; }
.ht-faq-answer {
    overflow: hidden;
    max-height: 0;
    transition: max-height .35s cubic-bezier(0.4,0,0.2,1);
}
.ht-faq-answer-inner {
    padding: 0 0 22px;
    color: #6b5660;
    font-size: 15.5px;
    line-height: 1.8;
}
.ht-faq-answer-inner a {
    color: #c27090;
    text-decoration: underline;
    text-underline-offset: 3px;
}
.ht-faq-answer-inner ul {
    margin: 10px 0 10px 20px;
}
.ht-faq-answer-inner li { margin-bottom: 6px; }
.ht-faq-cta {
    margin-top: 64px;
    background: linear-gradient(130deg, #fff0f5, #fff);
    border: 1px solid #f0dde5;
    border-radius: 20px;
    padding: 44px 40px;
    text-align: center;
}
.ht-faq-cta h2 {
    font: 400 28px Georgia, serif;
    color: #3d2f37;
    margin: 12px 0 10px;
}
.ht-faq-cta p {
    color: #7a6570;
    margin-bottom: 26px;
}
.ht-faq-social-links {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
}
.ht-faq-social-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 14px;
    text-decoration: none;
    transition: transform .18s, box-shadow .18s;
}
.ht-faq-social-link:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.12); }
.ht-faq-social-link.zalo { background: #0068FF; color: #fff; }
.ht-faq-social-link.facebook { background: #1877F2; color: #fff; }
.ht-faq-social-link.instagram { background: linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); color:#fff; }
@media (max-width: 600px) {
    .ht-faq-page { padding: 40px 20px 80px; }
    .ht-faq-cta { padding: 32px 22px; }
}
</style>
@endpush

@section('content')
<div class="ht-faq-page">
    <header>
        <span class="ht-eyebrow">HỖ TRỢ KHÁCH HÀNG</span>
        <h1>Câu hỏi thường gặp</h1>
        <p>Giải đáp mọi thắc mắc để bạn an tâm mua hàng tại Soopi.</p>
    </header>

    <div class="ht-faq-tabs" role="tablist">
        <a class="ht-faq-tab active" href="#chinh-hang">Chính hãng</a>
        <a class="ht-faq-tab" href="#giao-hang">Giao hàng</a>
        <a class="ht-faq-tab" href="#thanh-toan">Thanh toán</a>
        <a class="ht-faq-tab" href="#doi-tra">Đổi & trả</a>
        <a class="ht-faq-tab" href="#bao-quan">Bảo quản</a>
    </div>

    {{-- NHÓM 1: Chính hãng --}}
    <div class="ht-faq-group" id="chinh-hang">
        <div class="ht-faq-group-title">🛡️ Chính hãng &amp; nguồn gốc</div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Soopi có bán nước hoa chính hãng 100% không?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Có. Soopi chỉ kinh doanh nước hoa <strong>chính hãng 100%</strong>, được nhập trực tiếp từ nhà phân phối ủy quyền hoặc nhập khẩu chính ngạch từ Pháp, Ý và các nước sản xuất. Mỗi sản phẩm đều có tem kiểm định và hóa đơn nguồn gốc rõ ràng.
            </div></div>
        </div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Làm sao để phân biệt nước hoa chính hãng với hàng nhái?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Bạn có thể nhận biết qua một số dấu hiệu:
                <ul>
                    <li>Barcode trên vỏ hộp tra cứu được trên website thương hiệu</li>
                    <li>Số batch code (mã lô sản xuất) dập nổi hoặc in đáy chai</li>
                    <li>Mùi hương tồn lưu lâu, nồng độ ổn định qua từng xịt</li>
                    <li>Hộp giấy in sắc nét, nắp chai khớp chặt, không rỉ nước</li>
                </ul>
                Soopi cam kết hoàn tiền 100% nếu sản phẩm được xác nhận không chính hãng.
            </div></div>
        </div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Soopi có cho phép xịt thử trước khi mua không?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Nếu bạn đến trực tiếp studio, nhân viên sẽ hỗ trợ bạn thử mùi trên giấy thử hoặc da tay trước khi quyết định. Ngoài ra, bạn có thể đặt mua <strong>mẫu thử 2ml</strong> (nếu có) với giá rất ưu đãi để trải nghiệm trong 3–7 ngày tại nhà.
            </div></div>
        </div>
    </div>

    {{-- NHÓM 2: Giao hàng --}}
    <div class="ht-faq-group" id="giao-hang">
        <div class="ht-faq-group-title">🚚 Giao hàng &amp; vận chuyển</div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Soopi giao hàng trong bao lâu?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                <ul>
                    <li><strong>Nội thành HCM/HN:</strong> 1–2 ngày làm việc</li>
                    <li><strong>Các tỉnh thành khác:</strong> 2–4 ngày làm việc</li>
                    <li><strong>Vùng sâu, vùng xa:</strong> 4–7 ngày làm việc</li>
                </ul>
                Đơn hàng đặt trước 15:00 sẽ được xử lý và giao cho đơn vị vận chuyển ngay trong ngày.
            </div></div>
        </div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Phí giao hàng được tính như thế nào?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Phí vận chuyển được tính tự động dựa theo khoảng cách và trọng lượng đơn hàng qua hệ thống GHN. Đơn hàng từ <strong>500.000₫ trở lên</strong> được miễn phí giao hàng toàn quốc.
            </div></div>
        </div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Tôi có thể theo dõi đơn hàng ở đâu?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Bạn có thể tra cứu đơn hàng tại trang <a href="{{ route('orders.tracking') }}">Theo dõi đơn hàng</a> hoặc đăng nhập vào tài khoản để xem lịch sử và trạng thái chi tiết. Hệ thống cũng gửi email thông báo mỗi khi trạng thái đơn thay đổi.
            </div></div>
        </div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Sản phẩm có được đóng gói cẩn thận không?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Mỗi đơn hàng được đóng gói <strong>3 lớp chống vỡ</strong>: hộp giấy riêng cho chai, lớp xốp bảo vệ và thùng carton cứng bên ngoài. Đơn mua làm quà tặng có thể yêu cầu thêm gói ruy băng miễn phí trong ghi chú đơn hàng.
            </div></div>
        </div>
    </div>

    {{-- NHÓM 3: Thanh toán --}}
    <div class="ht-faq-group" id="thanh-toan">
        <div class="ht-faq-group-title">💳 Thanh toán</div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Soopi hỗ trợ những phương thức thanh toán nào?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Chúng tôi hỗ trợ đầy đủ các hình thức:
                <ul>
                    <li>💜 <strong>MoMo</strong> – thanh toán QR nhanh chóng</li>
                    <li>🏦 <strong>Chuyển khoản ATM / Internet Banking</strong></li>
                    <li>💵 <strong>COD</strong> – thanh toán khi nhận hàng</li>
                </ul>
            </div></div>
        </div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Mã giảm giá (coupon) dùng như thế nào?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Tại trang thanh toán, bạn sẽ thấy ô nhập mã giảm giá. Nhập mã và nhấn <strong>"Áp dụng"</strong> để hệ thống tự tính lại tổng tiền. Mỗi đơn hàng chỉ dùng được một mã, và mỗi mã có thể có điều kiện áp dụng riêng (giá trị đơn tối thiểu, thời hạn...).
            </div></div>
        </div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Thanh toán MoMo có an toàn không?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Hoàn toàn an toàn. Giao dịch MoMo được xử lý trực tiếp qua cổng thanh toán chính thức của MoMo với mã hóa SSL. Soopi không lưu trữ thông tin thẻ hay tài khoản ví của bạn.
            </div></div>
        </div>
    </div>

    {{-- NHÓM 4: Đổi & Trả --}}
    <div class="ht-faq-group" id="doi-tra">
        <div class="ht-faq-group-title">🔄 Đổi &amp; trả hàng</div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Chính sách đổi trả của Soopi như thế nào?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Soopi chấp nhận đổi/trả trong vòng <strong>7 ngày</strong> kể từ ngày nhận hàng nếu:
                <ul>
                    <li>Sản phẩm bị lỗi do nhà sản xuất (nứt vỡ, rỉ chai, mùi không đúng)</li>
                    <li>Giao nhầm sản phẩm so với đơn đặt hàng</li>
                    <li>Sản phẩm bị hư hỏng trong quá trình vận chuyển</li>
                </ul>
                Sản phẩm cần còn nguyên seal hoặc còn trên 90% lượng nước để được chấp thuận đổi trả.
            </div></div>
        </div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Tôi cần làm gì để yêu cầu đổi/trả hàng?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                <strong>Bước 1:</strong> Chụp ảnh/video sản phẩm lỗi rõ ràng.<br>
                <strong>Bước 2:</strong> Liên hệ Soopi qua Zalo hoặc inbox fanpage Facebook kèm mã đơn hàng và ảnh minh chứng.<br>
                <strong>Bước 3:</strong> Đội ngũ sẽ phản hồi trong vòng 24 giờ và hướng dẫn các bước tiếp theo.<br><br>
                Chi phí giao hàng hoàn trả do Soopi chi trả nếu lỗi từ phía cửa hàng.
            </div></div>
        </div>
    </div>

    {{-- NHÓM 5: Bảo quản --}}
    <div class="ht-faq-group" id="bao-quan">
        <div class="ht-faq-group-title">🌸 Bảo quản &amp; sử dụng</div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Bảo quản nước hoa như thế nào để giữ được lâu?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                <ul>
                    <li>Để nơi <strong>thoáng mát, tránh ánh nắng trực tiếp</strong></li>
                    <li>Không để trong phòng tắm (nhiệt độ và độ ẩm thất thường)</li>
                    <li>Đóng nắp kín sau mỗi lần dùng</li>
                    <li>Tránh lắc chai mạnh – dễ tạo bọt và làm bay hương liệu</li>
                    <li>Nước hoa chính hãng có thể dùng được <strong>3–5 năm</strong> nếu bảo quản đúng cách</li>
                </ul>
            </div></div>
        </div>

        <div class="ht-faq-item">
            <button class="ht-faq-question" aria-expanded="false">
                Xịt nước hoa ở đâu để mùi hương tỏa lâu nhất?
                <span class="ht-faq-chevron"><svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            </button>
            <div class="ht-faq-answer"><div class="ht-faq-answer-inner">
                Xịt vào <strong>điểm mạch</strong> – nơi nhiệt độ da cao giúp khuếch tán hương tốt hơn:
                <ul>
                    <li>Cổ tay, khuỷu tay trong</li>
                    <li>Sau tai và cổ</li>
                    <li>Ngực, hõm cổ</li>
                    <li>Sau đầu gối (nếu muốn mùi bay nhẹ từ dưới lên)</li>
                </ul>
                <em>Mẹo nhỏ:</em> Xịt vào quần áo (vải tự nhiên như cotton) giúp mùi hương lưu lâu hơn trên da.
            </div></div>
        </div>
    </div>

    {{-- CTA liên hệ --}}
    <div class="ht-faq-cta">
        <span style="font-size:32px;">💬</span>
        <h2>Vẫn còn thắc mắc?</h2>
        <p>Đội ngũ Soopi luôn sẵn sàng hỗ trợ bạn — nhanh chóng, tận tâm.</p>
        <div class="ht-faq-social-links">
            <a class="ht-faq-social-link zalo" href="https://zalo.me/0123456789" target="_blank" rel="noopener">
                <svg width="18" height="18" viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="8" fill="white" fill-opacity=".25"/><text x="5" y="28" font-size="22" font-family="Arial" font-weight="bold" fill="white">Z</text></svg>
                Zalo
            </a>
            <a class="ht-faq-social-link facebook" href="https://facebook.com/hathu.perfume" target="_blank" rel="noopener">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                Facebook
            </a>
            <a class="ht-faq-social-link instagram" href="https://instagram.com/hathu.perfume" target="_blank" rel="noopener">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="white"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                Instagram
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Accordion logic
    document.querySelectorAll('.ht-faq-question').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = this.closest('.ht-faq-item');
            var answer = item.querySelector('.ht-faq-answer');
            var inner = item.querySelector('.ht-faq-answer-inner');
            var isOpen = item.classList.contains('open');

            // Close all
            document.querySelectorAll('.ht-faq-item.open').forEach(function (openItem) {
                openItem.classList.remove('open');
                openItem.querySelector('.ht-faq-answer').style.maxHeight = '0';
                openItem.querySelector('.ht-faq-question').setAttribute('aria-expanded', 'false');
            });

            if (!isOpen) {
                item.classList.add('open');
                answer.style.maxHeight = inner.scrollHeight + 'px';
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // Tab active state (scroll-based)
    var tabs = document.querySelectorAll('.ht-faq-tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            this.classList.add('active');
        });
    });
});
</script>
@endpush
@endsection
