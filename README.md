# Soopi — Bài tập lớn web bán nước hoa

Laravel 12 · PHP 8.2+ · Blade/Vite · SQLite (demo) · Groq AI chat.

## Chạy hoàn toàn bằng Docker (khuyến nghị)

Không cần cài PHP, Composer, Node hay XAMPP trên máy. Đã bổ sung Compose cho
local và VPS (MySQL, volume giữ dữ liệu/ảnh, Caddy HTTPS), cùng target chạy test
trong Docker. Xem [hướng dẫn Docker local và VPS](docs/docker-vps.md).
CI/CD cho `soopi.site`: [GitHub Actions và VPS hiện tại](docs/github-actions.md).
Các lệnh cài PHP trực tiếp bên dưới là cách chạy cũ, không cần dùng khi chạy Docker.

## Chạy trên máy Windows khác

Cần Git, PHP với pdo_sqlite/sqlite3/mbstring/openssl/curl, Composer, Node.js/npm trên PATH.

```powershell
git clone https://github.com/duyd92689-debug/webbannuochoa.git
cd webbannuochoa
powershell -ExecutionPolicy Bypass -File scripts/setup-local.ps1
powershell -ExecutionPolicy Bypass -File scripts/start-local.ps1
```

Mở http://127.0.0.1:8002. Nếu dùng Laragon chưa có PHP trên PATH, truyền -Php đường dẫn php.exe; script setup cũng nhận -Composer đường dẫn composer.bat.

Setup không ghi đè .env, không xóa database, sao lưu SQLite trước migration. LocalDemoSeeder chỉ tạo catalog khi catalog trống và không thay mật khẩu tài khoản đã tồn tại. Khi cấu hình MySQL hiện có, tự sao lưu rồi chạy migrate; script tự động chỉ dành cho SQLite.

Tài khoản do LocalDemoSeeder tạo (chỉ demo local):

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | admin.demo@example.test | DemoPerfume2026! |
| Khách | customer.demo@example.test | DemoPerfume2026! |

Trang quản trị: /admin/login. Nếu email demo đã tồn tại từ trước thì mật khẩu cũ được giữ nguyên.

## Chế độ demo rõ ràng

.env.example bật DEMO_MODE=true và APP_ENV=local. Không có tiền thật được thu hoặc vận đơn thật được tạo:
- Địa chỉ giao hàng mẫu, phí cố định 30.000đ.
- Thanh toán có bốn kịch bản thử; không yêu cầu số thẻ/OTP.
- Email MAIL_MAILER=log có nút “Mở thư xác thực demo”, chỉ dành cho tài khoản đang đăng nhập.
- Báo cáo mặc định loại đơn demo; chọn “Chỉ đơn demo” để trình bày.
- Đơn demo được gắn nhãn và lịch sử trạng thái. DEMO_MODE bị vô hiệu ngoài local/testing.

Nếu có .env cũ, tự thêm DEMO_MODE=true để bật chế độ này. Không đổi APP_KEY trên database đã có dữ liệu.
Kho mẫu 5ml mới mặc định 0: admin phải nhập kho để đặt Discovery Box. Không tự suy ra tồn kho 10/50ml từ số chai lớn nữa.

## Bật AI Groq (không bắt buộc để chạy cửa hàng)

Điền trực tiếp trong .env, không đưa khóa lên Git:

```dotenv
AI_CHAT_ENABLED=true
GROQ_FREE_PLAN_CONFIRMED=true
GROQ_API_KEY=your_local_key
GROQ_MODEL=openai/gpt-oss-20b
AI_CHAT_DAILY_LIMIT=50
```

Chỉ đặt GROQ_FREE_PLAN_CONFIRMED=true sau khi tự kiểm tra tài khoản đang ở Free plan. Cờ này không thay đổi gói tài khoản tại Groq.
AI cần Internet, khóa hợp lệ và quota còn khả dụng. 50 lần thử/ngày là giới hạn bổ sung của ứng dụng, không phải cam kết quota từ Groq.
Script start-local chạy web, worker ai-chat và lịch dọn đơn hết hạn. Nếu chạy thủ công phải mở ba tiến trình:

```powershell
php artisan serve --host=127.0.0.1 --port=8002
# Terminal khác:
php artisan queue:work database --queue=ai-chat --sleep=1 --timeout=40 --tries=10
# Terminal thứ ba:
php artisan schedule:work
```

Khởi động lại worker sau khi sửa cấu hình/mã AI. Không có khóa hoặc API lỗi vẫn lưu tin nhắn để nhân viên hỗ trợ.
Thẻ sản phẩm trong chat chỉ hiển thị sản phẩm đang bán có tên đầy đủ được AI nhắc đến; ảnh, giá và link lấy từ DB.

## Kiểm thử

```powershell
php vendor/bin/phpunit
npm run build
```

phpunit.xml ép dùng SQLite in-memory, tắt AI mặc định. Các bài test AI dùng khóa giả/HTTP giả, không tiêu quota thật.
Kiểm thử mới gồm phân quyền, tổng tồn kho theo biến thể, Discovery Box, rollback, hủy lặp, demo thanh toán, đánh giá sau mua, thẻ AI và CSV.

## Nâng cấp độ tin cậy 28/09/2026

- Tra cứu đơn yêu cầu đăng nhập, chỉ tìm đơn thuộc tài khoản hiện tại, giới hạn 15 lượt/phút.
- Xóa sản phẩm là lưu trữ mềm; đơn giữ nguyên tên, thương hiệu, dung tích, giá tại lúc mua. Dữ liệu cũ chỉ chụp tên hiện tại khi migrate, không khôi phục được tên đã sửa trước đó.
- Giữ hàng lưu chính xác ngăn kho theo biến thể; hoàn kho không phụ thuộc dung tích sản phẩm bị sửa. Nhật ký trừ/hoàn có ở trang chi tiết đơn quản trị. Không cho sửa dung tích gốc sản phẩm đã có đơn qua CRUD.
- Giỏ hàng hiển thị mục không khả dụng kèm nút xóa, không còn lỗi vòng lặp khi hàng ngừng bán. Phí vận chuyển được báo tại checkout, không quảng cáo miễn phí giả.
- Checkout thông thường có UUID chống gửi lặp, khóa session, tạo giao dịch và giữ kho trong cùng transaction. Đơn online mới giữ hàng 30 phút; lịch chạy mỗi phút chỉ hủy đơn chưa thanh toán, chưa giao, không phải COD. Đơn cũ không bị tự gán hạn.
- Webhook vận chuyển có khóa đơn, bỏ qua trạng thái đến muộn, ghi nhận giao thành công và thanh toán COD. `return` không đồng nghĩa đã nhận lại hàng; không tự hoàn kho hàng trả chưa kiểm tra. Webhook vẫn cần relay xác thực như cấu hình cũ.
- Điểm: 100.000đ chi tiêu hợp lệ = 1 điểm, 1 điểm = 1.000đ, đổi tối đa 20% tiền hàng sau coupon. Chỉ tính đơn hoàn tất có giao dịch đã trả tiền (hoặc đơn legacy hoàn tất không có giao dịch); loại đơn hủy/hoàn và tách demo/thật. Hạng là mốc ghi nhận, chưa có giảm giá tự động theo hạng.
- Menu Tài khoản → Thông tin & đổi mật khẩu. Đổi mật khẩu cần mật khẩu hiện tại; tên cập nhật không thay đổi email/quyền. Với session database, các phiên khác bị xóa.

Trước cập nhật máy khác: chạy `php scripts/backup-sqlite.php`, sau đó `php artisan migrate --force` và khởi động lại worker/lịch chạy. Không chạy `migrate:fresh` trên dữ liệu cần giữ.
Phạm vi còn lại: quy trình khách yêu cầu đổi trả/hoàn tiền thực, email quên mật khẩu, sổ nhiều địa chỉ, giỏ đồng bộ nhiều thiết bị, CI và kiểm thử tải. Các mục này chưa được tuyên bố đã hoàn thành.

## Tài liệu

- [Lab 09 — Finance: chức năng, quy tắc đối soát và kịch bản kiểm thử](docs/finance-lab09.md)
- [Lab 10 — Docker, Render và Aiven: cấu hình chuẩn bị triển khai](docs/deployment-render-aiven.md)
- [5 mục thiết kế thành phần, sơ đồ tổng thể và kịch bản bảo vệ](docs/architecture-and-demo.md)
- [Cấu hình AI](docs/ai-chat-groq.md)
- [Livestream LiveKit](docs/livestream-livekit.md)

Đây là bản bài tập local, chưa phải hệ thống thương mại đã kiểm định. Hoàn tiền thực, kiểm thử tải đồng thời trên MySQL và vận hành cổng thật cần triển khai/kiểm tra riêng.

## Lab 09 / Lab 10 — cập nhật mã 28/09/2026

- Admin có hai mục mới: **Thống kê tài chính** (`/admin/finance`) và **Giao dịch thanh toán** (`/admin/finance/transactions`). Lọc, phân trang, thống kê và CSV dùng cùng dữ liệu; báo cáo mặc định loại đơn demo và đơn có ngày tạo ở tương lai.
- Đối soát COD có kiểm tra chuyển trạng thái, chống cập nhật form cũ và lưu lịch sử người thao tác. Ghi nhận hoàn tiền không chuyển tiền và không tự hoàn kho. Giao dịch MoMo không được sửa thủ công qua Finance.
- Máy đang có dữ liệu: sao lưu bằng `php scripts/backup-sqlite.php`, chạy `php artisan migrate --force`, sau đó `npm ci` và `npm run build`. Dừng Vite của đúng dự án trước `npm ci` nếu Windows báo file native đang bị khóa. Không chạy `migrate:fresh`.
- Thư mục controller đã chuẩn hóa thành `app/Http/Controllers/Admin` để khớp namespace trên Linux. Dependency frontend có lock đa nền tảng, không còn khai báo trực tiếp package chỉ dành cho Windows.
- Đã thêm Docker nhiều stage, Nginx/PHP-FPM, worker AI, scheduler, kiểm tra cấu hình production/TLS và ProductionSeeder chạy lại không ghi đè tài khoản/tồn kho. Cấu hình mẫu ở `docker/render.env.example`, không dùng `.env` local làm cấu hình production.
- **Chưa đưa online.** Đã build/chạy Linux image và MySQL bằng Docker Desktop, bổ sung cấu hình VPS Compose; xem [kết quả kiểm tra Docker](docs/docker-vps.md). Render/Aiven và HTTPS trên VPS thật chưa được nghiệm thu.
