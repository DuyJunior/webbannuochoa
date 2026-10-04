# Thanh toán SePay / BIDV

Checkout mới nhận `cod` hoặc `sepay`. Không còn endpoint, cấu hình hay SDK MoMo cho thanh toán mới. Các giao dịch MoMo đã lưu được giữ nguyên trong lịch sử và báo cáo. Đơn MoMo cũ chưa trả tiền không được tự đổi tài khoản nhận; khách cần tạo đơn SePay mới, đơn cũ hết hạn theo lịch hiện có.

## Cấu hình

Chỉ cần Docker trên máy chạy. Không cài PHP, Composer hay Laravel trên Windows. Các dependency hiện có được build bên trong image; tích hợp này không thêm dependency.

Thêm vào `.env` của môi trường triển khai (tham khảo `.env.vps.example`):

```dotenv
SEPAY_ENABLED=false
SEPAY_BANK=BIDV
SEPAY_ACCOUNT_NUMBER=<tai-khoan-goc-da-lien-ket-SePay>
SEPAY_SUB_ACCOUNT=<VA-BIDV-da-chon-trong-webhook>
SEPAY_ACCOUNT_NAME="<TEN CHU TAI KHOAN>"
SEPAY_PAYMENT_PREFIX=DH
SEPAY_WEBHOOK_SECRET=<dung-nguyen-secret-HMAC-tren-SePay>
```

Không commit secret, `.env`, file TXT cấu hình cá nhân hay log giao dịch đầy đủ. `SEPAY_SUB_ACCOUNT` là VA dùng trong QR; `SEPAY_ACCOUNT_NUMBER` là tài khoản gốc dùng để kiểm tra webhook. BIDV bắt buộc có VA. Thông tin nhận tiền được chụp lại vào mỗi giao dịch, nên thay đổi cấu hình không làm QR của đơn đang chờ đổi tài khoản. Khi đổi ngân hàng/VA, cần giữ webhook tương ứng cho các đơn cũ đang chờ.

Production cần `APP_URL=https://soopi.site`, `DEMO_MODE=false`, database queue cùng database với đơn hàng (`DB_QUEUE_CONNECTION` để trống hoặc cùng connection), bảng `jobs` và worker `queue:work database --queue=default,ai-chat`. Image hiện có tự chạy worker và scheduler. Không chuyển riêng database queue sang database khác vì biên nhận, thanh toán và job cần commit cùng giao dịch.

## Webhook trên SePay

- URL: `https://soopi.site/payment/sepay/webhook`, phương thức POST, JSON.
- Chỉ tiền vào, đúng tài khoản BIDV và VA đã chọn.
- Xác thực **HMAC-SHA256**, dùng cùng secret như `.env`, không chọn API key.
- Mã thanh toán `DH` + **8 chữ số**; cấu hình nhận diện trên SePay phải chấp nhận 8 chữ số. Mã dùng ID giao dịch thanh toán, không phải số đơn hiển thị; luôn chuyển đúng nội dung trong QR.
- Bật retry nếu phản hồi lỗi. Thành công trả HTTP 200 với `{"success":true}`.

Chữ ký là `sha256=` + HMAC-SHA256 của `timestamp.rawBody`, dùng `X-SePay-Timestamp` và `X-SePay-Signature`; chấp nhận lệch thời gian tối đa 300 giây. Đồng hồ máy chủ phải đồng bộ. Retry phải có chữ ký/timestamp mới do SePay tạo. Không thử bằng cách đăng JSON không ký.

QR dùng `https://vietqr.app/img` với VA, ngân hàng, số tiền và nội dung chính xác. Website không thu số thẻ hoặc OTP. Khi nhận webhook hợp lệ, backend kiểm tra ngân hàng, tài khoản gốc, VA, mã thanh toán, số tiền bằng toàn bộ tổng đơn, hạn thanh toán và trạng thái kho/đơn.

## Chạy và kiểm tra

Trên máy local đang dùng cấu hình Docker demo:

```powershell
docker build --target test -t soopi:test .
docker run --rm soopi:test php vendor/bin/phpunit
docker build --target production -t soopi:local .
docker compose -f compose.yaml -f compose.local.yaml up -d --no-build
docker compose -f compose.yaml -f compose.local.yaml exec -T app php artisan sepay:check
```

Ở local, `sepay:check` báo FAIL cho enabled/demo/HTTPS là đúng: demo không được thu tiền thật. Checkout vẫn có lựa chọn SePay mô phỏng nhưng không tạo QR tài khoản thật và không gọi SePay. Hãy giữ `SEPAY_ENABLED=false` khi chưa triển khai endpoint thật.

Khi triển khai production: sao lưu database, build image mới, chạy migration (entrypoint mặc định `RUN_MIGRATIONS=true`), cập nhật `.env`, tạo lại container để áp dụng config cache. Migration chỉ thêm bảng `sepay_webhook_receipts`, không đổi dữ liệu MoMo. Kiểm tra `php artisan sepay:check` sau khi bật cấu hình backend. CLI chỉ kiểm tra cấu hình/schema; không thể xác nhận secret trên dashboard khớp hoặc webhook đã được bật.

Chỉ mở webhook đã tạm ngưng trên SePay sau khi endpoint production, HMAC và worker sẵn sàng. Thực hiện một đơn giá trị nhỏ do chủ shop chủ động chuyển đúng QR; kiểm tra biên nhận `paid`, trạng thái đơn, email và vận đơn. Việc build/chạy test không tự bật webhook, không triển khai lên soopi.site và không tự chuyển tiền.

## Đối soát và xử lý ngoại lệ

Admin → Tài chính → Quản lý giao dịch → **Đối soát giao dịch SePay** (`/admin/finance/sepay`) hiển thị ID SePay, mã ngân hàng, mã thanh toán, số tiền nhận và kết quả. Tìm theo ID SePay hoặc mã DH, lọc theo kết quả:

- `paid`: đủ tiền, đúng đơn, trong hạn. Order thành `paid`; job tạo GHN với COD = 0 và email chỉ ghi nhận một lần.
- `amount_mismatch`: thiếu/thừa tiền hoặc tổng đơn đã khác. Không cộng dồn các lần chuyển; đối soát với khách/ngân hàng trước khi xử lý bên ngoài.
- `unmatched`: không có mã hoặc mã không khớp giao dịch SePay. Kiểm tra cấu hình nhận diện mã DH và lịch sử ngân hàng.
- `duplicate_payment`: có thêm một giao dịch ngân hàng khác cho đơn đã trả tiền. Không ghi đè khoản thu ban đầu, không tạo thêm vận đơn; cần xử lý khoản thừa bên ngoài.
- `late_payment`: tiền chính xác đến sau khi đơn bị hủy/hết hạn. Không khôi phục đơn; giao dịch thành `refund_pending`, tồn kho được trả nếu đơn vừa hết hạn. Admin ghi nhận hoàn tiền sau khi thực sự hoàn bên ngoài, bắt buộc mã chứng từ.
- `order_not_payable` / `demo_order`: không tự ghi nhận thu tiền cho đơn không đủ điều kiện. Cần đối soát thủ công bên ngoài.

Cùng một ID SePay gửi lại chỉ tạo một biên nhận. Sai chữ ký trả 401; payload không hợp lệ trả 422; lỗi database/queue trả 5xx để SePay retry. Sai tài khoản/ngân hàng/VA hoặc tiền ra được bỏ qua và không tác động đơn. Các giao dịch được xác thực nhưng cần đối soát trả 200 để tránh retry vô hạn; theo dõi chúng trên trang admin.

GHN chạy trong queue, không gọi mạng trong webhook. Một lần thử tạo vận đơn được đánh dấu bền vững trước khi gọi GHN. Nếu timeout, worker chết hoặc GHN không xác nhận thành công, khoản thanh toán vẫn giữ nguyên; **kiểm tra GHN bằng mã đơn trước khi tạo lại**, vì phía GHN có thể đã tạo vận đơn. Không tự xóa `shipment_attempted_at` hoặc retry để tạo lần hai. Trang đối soát sẽ hiện đơn đã trả nhưng chưa xác nhận vận đơn.

Đối soát Finance không cho admin đánh dấu SePay `paid`. Admin chỉ được ghi nhận yêu cầu hoàn/hoàn đã thực hiện, có lịch sử người thao tác và mã chứng từ. Khoản sai tiền/chuyển thêm vẫn nằm trong biên nhận để đối soát, không tự biến thành doanh thu.

## Tài liệu chính thức

- [Xác thực webhook](https://developer.sepay.vn/vi/sepay-webhooks/xac-thuc)
- [Payload và phản hồi webhook](https://developer.sepay.vn/vi/sepay-webhooks/tich-hop-webhook)
- [Quy tắc mã thanh toán](https://developer.sepay.vn/vi/sepay-webhooks/cau-hinh-ma-thanh-toan)
- [Tạo VietQR](https://developer.sepay.vn/vi/tien-ich-khac/tao-qr-code)
