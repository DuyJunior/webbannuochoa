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
Script start-local chạy web, worker email/ai-chat và lịch dọn đơn hết hạn, khôi phục thư chờ. Nếu chạy thủ công phải mở ba tiến trình:

```powershell
php artisan serve --host=127.0.0.1 --port=8002
# Terminal khác:
php artisan queue:work database --queue=default,ai-chat --sleep=1 --timeout=40 --tries=10
# Terminal thứ ba:
php artisan schedule:work
```

Khởi động lại worker sau khi sửa cấu hình/mã AI. Không có khóa hoặc API lỗi vẫn lưu tin nhắn để nhân viên hỗ trợ.
Thẻ sản phẩm trong chat chỉ hiển thị sản phẩm đang bán có tên đầy đủ được AI nhắc đến; ảnh, giá và link lấy từ DB.

## Email trạng thái đơn hàng

Đơn thực có thư xác nhận đã đặt, đã thanh toán, đã bàn giao vận chuyển và đã giao. `ready_to_pick`/`picking` chưa tạo thư đã gửi hàng. Mỗi mốc chỉ có một bản ghi trong `order_emails`; bản ghi được lưu cùng transaction đơn hàng, đưa vào queue database sau commit và tự khôi phục mỗi phút nếu queue/SMTP lỗi. Thư chưa gửi sẽ được bỏ qua nếu đơn đã hủy/hoàn hoặc mốc không còn đúng. Đơn mô phỏng không gửi thư.

Chạy `php artisan migrate --force`, `php artisan config:clear` và khởi động lại worker/lịch sau cập nhật. Dùng `php artisan orders:dispatch-emails --check` để kiểm tra bảng và chế độ gửi mà không gửi email. Container kiểm tra schema trước khi mở web; không bỏ qua migration của bản phát hành này.

Email chỉ gửi đến địa chỉ đã xác thực của tài khoản đặt đơn. Thiết lập `MAIL_MAILER=smtp` và các biến SMTP của nhà cung cấp trong môi trường riêng; không đưa mật khẩu lên Git. Đặt `APP_URL` đúng URL HTTPS của cửa hàng để nút theo dõi mở trang yêu cầu đăng nhập và kiểm tra quyền sở hữu. `ORDER_EMAILS_MAILER` có thể chọn mailer riêng; `ORDER_EMAILS_ENABLED=false` tạm dừng gửi nhưng vẫn giữ thư chờ. Chế độ `log`, `array`, hoặc failover có nhánh `log` chỉ giữ thư chờ, không báo đã gửi. Sửa cấu hình xong cần xóa/tạo lại config cache và khởi động lại worker. Lỗi gửi thử lại với thời gian chờ tăng dần, tối đa 60 phút; cột trạng thái thư trong chi tiết đơn quản trị giúp theo dõi.

Không gửi thư thật trong kiểm thử: các bài `OrderEmailTest` dùng Mail/Queue giả và SQLite bộ nhớ. SMTP không bảo đảm exactly-once khi máy chủ đã nhận thư nhưng kết nối đứt trước xác nhận; hệ thống dùng Message-ID ổn định để hỗ trợ nhận diện lần thử lại.

## Thông tin cửa hàng và ảnh thực tế

Trang `/lien-he` và `/chinh-sach-bao-mat` dùng chung thông tin liên hệ trong `config/storefront.php`. Facebook, Instagram và Zalo là các kênh đã được chủ shop xác nhận. Bổ sung `STORE_ADDRESS`, `STORE_SUPPORT_HOURS`, `STORE_CONTACT_EMAIL` trong môi trường riêng khi có thông tin chính xác; trường trống được ẩn. Sau khi cập nhật cấu hình, xóa/tạo lại config cache.

Trong **Admin → Sản phẩm → Thêm/Sửa → Ảnh thực tế sản phẩm**, tải ảnh cửa hàng chụp (tối đa 6 ảnh/sản phẩm, mỗi ảnh 5 MB, tổng một lần 18 MB). Chạy migration và `php artisan storage:link` trước khi sử dụng. Ảnh nằm trên public disk, cần được lưu bền vững và sao lưu cùng dữ liệu; không thay thế ảnh phối cảnh. Trang sản phẩm chỉ hiện thư viện khi đã có ảnh tải lên. Ảnh cũ không bị gỡ nếu lưu thất bại; ảnh đã chọn xóa chỉ được gỡ sau khi lưu thành công.

## Quên mật khẩu và combo quà tặng

Trang đăng nhập khách hàng và Admin có liên kết **Quên mật khẩu** (`/quen-mat-khau`). Liên kết khôi phục dùng một lần, hết hạn sau 60 phút; mỗi tài khoản được yêu cầu lại sau 60 giây. Thư được mã hóa trong queue `database/default`, vì vậy cần worker đang chạy như hướng dẫn ở trên. Đặt `APP_URL` đúng địa chỉ cửa hàng và cấu hình mailer gửi thật; `log`, `array` hoặc failover có nhánh log không gửi liên kết để tránh lộ token. Không tự đăng nhập sau khi đổi mật khẩu; các phiên database và remember token cũ bị thu hồi. Kiểm thử dùng email giả, không xác nhận khả năng gửi đến hộp thư thật.

Combo trên trang sản phẩm gồm chai dung tích gốc, **hai mẫu 5ml khác mùi, khác chai chính**, hộp quà và thiệp. Giá bằng giá chai hiện tại cộng **90.000đ**, được định nghĩa trong `GiftBundleService::EXTRA_PRICE`; không cộng tiếp phí gói quà 50.000đ của sản phẩm lẻ. Khách chọn rõ hai mẫu còn hàng; giỏ, checkout, đơn hàng, email và Admin hiển thị thành phần đã chọn. Hệ thống giữ/hoàn kho cả ba thành phần qua cơ chế tồn kho hiện có, dùng chung ngăn kho với mẫu lẻ và Discovery Box. Combo cũ trong giỏ dùng lời khắc thay cho mẫu bị yêu cầu xóa/chọn lại; lịch sử đơn cũ được giữ nguyên.

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
Phạm vi còn lại: quy trình khách yêu cầu đổi trả/hoàn tiền thực, sổ nhiều địa chỉ, giỏ đồng bộ nhiều thiết bị, CI và kiểm thử tải. Các mục này chưa được tuyên bố đã hoàn thành.

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
