# Phát trực tiếp bằng camera trên website

Website đã có studio cho tài khoản `admin` hoặc `livestream_staff`. Người xem mở `/livestream` và chỉ nhận quyền xem. Buổi phát YouTube cũ vẫn dùng được.

## Cấu hình máy chủ video

Để phục vụ hàng chục hoặc hàng trăm người xem đồng thời, dùng một máy chủ LiveKit hoặc LiveKit Cloud. Thêm ba biến vào `.env` của Laravel:

```dotenv
LIVEKIT_URL=wss://video.ten-mien-cua-ban.vn
LIVEKIT_API_KEY=...
LIVEKIT_API_SECRET=...
```

Không đưa API secret vào JavaScript, HTML hay kho mã nguồn. Website chỉ tạo token ngắn hạn trên máy chủ Laravel. Sau khi thay `.env`, chạy `php artisan config:clear` (và khởi động lại PHP nếu đang cache cấu hình).

Website triển khai cho người khác truy cập phải có HTTPS; máy nhân viên dùng `localhost` cũng có thể thử camera. LiveKit cần tên miền, TLS và cổng mạng theo hướng dẫn triển khai chính thức. Hệ thống hiện tại không có máy chủ LiveKit, nên studio sẽ hiện thông báo cấu hình thay vì đưa một buổi phát giả lên trang khách.

## Sử dụng

1. Admin cấp vai trò **Nhân viên livestream** ở phần quản lý tài khoản.
2. Nhân viên tạo buổi phát, chọn **Phát bằng camera/micro ngay trên web**.
3. Vào **Studio**, cấp quyền camera/micro và bấm **Bật camera & bắt đầu phát**.
4. Gửi liên kết `/livestream` cho khách. Khách bấm **Xem livestream** để xem và nghe.
5. Khi xong, bấm **Kết thúc buổi live**. Nếu kết nối của nhân viên bị mất, trang khách ngừng cấp token xem mới sau khoảng 35 giây và hiển thị trạng thái gián đoạn.

Thư viện trình duyệt LiveKit 2.22.3 được lưu ở `public/vendor/livekit-client/` cùng giấy phép để site không phụ thuộc CDN khi tải trình phát.

## Livestream ngay và đặt lịch

Trong quản lý livestream có hai lối tạo: **Livestream ngay** mở studio cho nguồn camera/micro (hoặc hiển thị YouTube Live khi đã có liên kết); **Đặt lịch phát** yêu cầu giờ tương lai theo giờ Việt Nam UTC+7 và quay về danh sách. Đặt lịch chỉ hẹn giờ và thông báo cho khách, không tự bật camera/micro. Khi đến giờ, nhân viên bấm **Livestream ngay** ở dòng buổi phát để vào studio rồi xác nhận dùng thiết bị. Trang khách tự cập nhật khi buổi phát thật sự bắt đầu. Lịch vừa đến giờ sẽ hiển thị trạng thái chờ lên sóng trong tối đa hai giờ; lịch cũ hơn vẫn nằm trong trang quản lý để sửa hoặc kết thúc.

## Sản phẩm và doanh thu trong buổi live

Nhân viên có thể chọn nhiều nước hoa khi tạo buổi live và thêm hoặc gỡ sản phẩm trong Studio mà không phải ngừng phát. Khách mở sản phẩm từ kệ dưới video; khi chuyển sang trang sản phẩm, giỏ hàng hoặc thanh toán trong cùng tab, trình phát thu nhỏ kết nối lại với buổi live. Nếu trình duyệt chặn tự phát tiếng, khách bấm **Bật tiếng**.

Chỉ dòng hàng được mở từ kệ live rồi thêm vào giỏ trong vòng 2 giờ mới được gắn nguồn buổi live. Dòng hàng khác trong cùng đơn không được tính vào doanh thu live. Báo cáo **Doanh thu** của từng buổi và tổng quan **Hôm nay / 7 ngày / 30 ngày** tính giá trị hàng đã thu tiền sau phần ưu đãi phân bổ, không gồm phí giao hàng; đơn chờ thu được hiển thị riêng, đơn hủy và hoàn tiền bị loại. Khoảng thời gian dựa trên ngày đặt hàng theo giờ Việt Nam. Khi đơn được thanh toán sau lúc tắt live, doanh thu của buổi đó vẫn cập nhật.

## Trò chuyện và sản phẩm nổi bật

Khi buổi phát đang trực tuyến, khách (kể cả khách chưa đăng nhập) có thể bình luận ở cạnh video. Tin nhắn tối đa 300 ký tự, được cập nhật mỗi 5 giây và giới hạn tần suất gửi. Nhân viên nhìn thấy cùng cuộc trò chuyện trong Studio và có thể ẩn bình luận không phù hợp. Tin nhắn đã ẩn không hiện trên trang khách.

Trong Studio, bấm **Ghim** ở sản phẩm đang giới thiệu. Sản phẩm đó nổi bật ở đầu kệ của khách sau lần cập nhật danh sách tiếp theo (tối đa khoảng 15 giây). Chỉ có một sản phẩm được ghim mỗi lúc; gỡ sản phẩm cũng bỏ ghim.

Số **đang xem** là số phiên khách gửi tín hiệu trong 45 giây gần nhất. Người xem bằng camera được đếm sau khi kết nối video; người xem YouTube được đếm khi trang live đang mở. Báo cáo từng buổi lưu số phiên đã xem và lượt mở sản phẩm từ kệ live kể từ khi tính năng này được cài đặt; các số này không thể khôi phục cho những buổi phát cũ.

Máy chủ LiveKit trỏ về `127.0.0.1` chỉ phục vụ kiểm thử trên cùng máy. Studio hiển thị cảnh báo khi còn dùng địa chỉ này. Để khách trên Internet xem được cần cấu hình LiveKit công khai với tên miền và `wss://` trên máy chủ có TLS; không thể thay chỉ bằng một liên kết `localhost` trong giao diện.
