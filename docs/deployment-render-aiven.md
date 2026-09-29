# Lab 10 — Gói triển khai Render + Aiven MySQL

> Cập nhật 29/09/2026: đã chạy Docker local với MySQL và có hướng triển khai
> [VPS bằng Compose](docker-vps.md). Các ghi chú chưa kiểm chứng bên dưới là trạng
> thái của bài lab Render/Aiven ban đầu; Render/Aiven vẫn chưa được triển khai.

Gói này chuẩn bị mã và cấu hình để bạn triển khai sau. Chưa tạo dịch vụ, chưa đẩy mã lên GitHub, chưa chạy migration trên Aiven và chưa triển khai website online. Máy chuẩn bị mã chưa có Docker nên việc build/chạy image và kết nối Aiven thực tế vẫn cần được kiểm chứng.

## Phạm vi và chi phí

Kiến trúc gồm **một Render Web Service dùng Docker** và **một Aiven for MySQL**. Nginx, PHP-FPM, worker AI và scheduler nằm cùng container. Không cần tạo thêm Background Worker hoặc Cron Job trả phí cho bài lab.

Chỉ chọn gói được giao diện xác nhận là **Free** tại thời điểm bạn tự triển khai. Không chọn gói dùng thử trả phí bằng credit, tự nâng cấp, thêm thẻ hoặc bật tài nguyên trả phí để tiếp tục bài lab. Nếu tài khoản không có lựa chọn Free thì dừng ở bản mã đã chuẩn bị. Theo tài liệu hiện tại, Aiven phân biệt Free tier với free trial; dịch vụ Free có thể bị tắt khi không hoạt động. Chính sách có thể thay đổi. [Aiven service pricing](https://aiven.io/docs/platform/concepts/service-pricing).

Render Free ngủ sau 15 phút không có truy cập vào; lần truy cập tiếp theo cần thời gian khởi động lại. **Trong lúc ngủ, worker AI và scheduler cũng không chạy**, nên phản hồi AI, callback và việc hết hạn đơn không có cam kết xử lý đúng thời gian. Scheduler xử lý các đơn quá hạn khi container hoạt động lại. Free không có shell dashboard/SSH và không có persistent disk. [Render Free](https://render.com/docs/free).

Tên gói Free không có nghĩa mọi cách sử dụng tài khoản đều không phát sinh phí: Render nêu rằng vượt hạn mức bandwidth/build có thể bị tính phí nếu đã thêm phương thức thanh toán; tài khoản không có phương thức thanh toán có thể bị vô hiệu dịch vụ thay vì tính phí. Kiểm tra hạn mức trong dashboard; hướng dẫn này không xác nhận billing của tài khoản. [Render FAQ](https://render.com/docs/faq).

## Các thành phần đã chuẩn bị

| Tệp | Vai trò |
| --- | --- |
| `Dockerfile` | PHP 8.3 FPM; Composer cài từ lock với `--no-dev`; Node 22 chạy `npm ci` và Vite; chép `public/build` vào image cuối. |
| `.dockerignore` | Loại `.env`, credential Composer, CA/key, SQLite, dump SQL, log, vendor/node_modules và cache máy cá nhân khỏi build context. |
| `docker/nginx.conf` | Chỉ phục vụ thư mục `public`, thực thi duy nhất `index.php`, lắng nghe `0.0.0.0:$PORT`. |
| `docker/php.ini`, `docker/php-fpm.conf` | Tắt hiển thị lỗi, log stderr, pool FPM nhỏ, giới hạn tài nguyên. |
| `docker/check-runtime.php`, `check-ca.php`, `check-db.php` | Kiểm tra cấu hình production, CA PEM còn hiệu lực và phiên MySQL dùng TLS đã xác thực. Không in key/password. |
| `docker/entrypoint.sh` | Chuẩn bị quyền, CA riêng, migration tùy chọn, seed tùy chọn, storage link và cache cấu hình/view. |
| `docker/supervise.sh` | Theo dõi cả bốn tiến trình; bất kỳ tiến trình nào thoát ngoài lúc shutdown sẽ làm container thoát lỗi. |
| `docker/render.env.example` | Danh sách biến để điền trong Render; không có credential thật. |

Image có `pdo_mysql`, `mbstring`, `dom`, `bcmath`, `zip`, `opcache` và `pcntl`; các extension nền khác theo image PHP chính thức. `composer check-platform-reqs --no-dev` kiểm tra lại yêu cầu lock ngay trong build. Không dùng `composer update` hoặc `--ignore-platform-reqs` để vượt lỗi. Script shell dùng LF theo `.gitattributes` và được cấp quyền thực thi trong Dockerfile.

## 1. Chuẩn bị Aiven khi muốn triển khai

1. Trong Aiven Console, chọn **Aiven for MySQL**, xác nhận plan **Free** trước khi tạo. Chọn khu vực gần Render nếu Free cho phép. Không đổi sang dịch vụ/plan trả phí khi tùy chọn mong muốn không có.
2. Từ Connection information, lấy riêng **host, port, database, username, password**. Dùng đúng port Aiven cung cấp, không tự thay bằng `3306`. Database có thể là `defaultdb` hoặc database bạn đã tạo cho ứng dụng.
3. Tải **CA certificate** của project thành `ca.pem`. Không lưu CA/credential vào repository. Khi Aiven luân chuyển CA, cập nhật secret file bằng bundle mới được cung cấp. MySQL trên Aiven cần CA project để xác minh chứng chỉ máy chủ. [Aiven TLS/SSL certificates](https://aiven.io/docs/platform/concepts/tls-ssl-certificates).
4. Nếu đã giới hạn IP truy cập, cho phép các địa chỉ outbound của Render service theo cấu hình mạng của tài khoản. Giữ hostname Aiven cung cấp để việc xác thực TLS hoạt động; không thay bằng IP tùy ý.

## 2. Tạo Render Web Service bằng dashboard

Các bước sau dành cho lúc bạn chủ động triển khai; không cần shell Render:

1. Đưa bản mã đã kiểm tra, gồm `composer.lock` và `package-lock.json`, vào repository mà bạn muốn kết nối. Không đưa `.env`, credential hoặc chứng chỉ lên Git.
2. Chọn **New → Web Service**, kết nối đúng repository/branch.
3. Chọn runtime **Docker** và instance **Free**. Repository hiện tại có `Dockerfile`, `artisan`, `composer.json` ngay tại Git root nên để **Root Directory trống**, **Dockerfile Path `./Dockerfile`**, Docker build context tại root. Nếu bạn tự đưa dự án vào thư mục con của repository khác, đặt Root Directory thành thư mục chứa các tệp đó.
4. Để Docker command mặc định của image; không thêm `php artisan serve`, không override entrypoint, không thêm pre-deploy command. Startup script thực hiện migration khi biến tương ứng bật.
5. Đặt **Health Check Path `/up`**. Render cung cấp `PORT`; Nginx lấy giá trị này lúc khởi động. Render kết thúc HTTPS ở reverse proxy và chuyển HTTP vào container. [Render Web Services](https://render.com/docs/web-services).
6. Thêm các biến dưới đây và secret file trước khi mong đợi service khởi động thành công. Docker dùng build nhiều stage, không cần lệnh build/start của runtime PHP native. [Docker on Render](https://render.com/docs/docker).

## 3. Secret file và biến bắt buộc

Trong **Environment → Secret Files**, thêm filename **`ca.pem`**, dán đầy đủ nội dung CA tải từ Aiven. File có sẵn tại **`/etc/secrets/ca.pem`** khi chạy. Entrypoint kiểm tra rồi chép vào **`/var/www/private/ca.pem`**, quyền `root:www-data 0640`, ngoài web root; PHP-FPM và worker đều đọc được. [Render secret files](https://render.com/docs/configure-environment-variables#secret-files).

Điền theo `docker/render.env.example`; giá trị dưới đây là cấu hình, không phải credential mẫu để đăng nhập:

| Biến | Giá trị |
| --- | --- |
| `APP_ENV`, `APP_DEBUG`, `DEMO_MODE` | `production`, `false`, `false` |
| `APP_KEY` | Key riêng tạo một lần, dạng `base64:...` với 32 byte ngẫu nhiên; giữ nguyên qua redeploy. |
| `APP_URL` | URL HTTPS thật của service, ví dụ `https://ten-ban-chon.onrender.com`. |
| `TRUSTED_PROXIES` | `*` cho service chỉ vào qua Render proxy; dùng danh sách địa chỉ tin cậy nếu hạ tầng cung cấp. Không dùng `*` cho máy chủ cho phép truy cập trực tiếp tùy ý. |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Lấy trực tiếp từ dịch vụ Aiven của bạn. |
| `MYSQL_SSL_CA_SOURCE` | `/etc/secrets/ca.pem` |
| `MYSQL_ATTR_SSL_CA` | `/var/www/private/ca.pem` (entrypoint cố định đường dẫn này). |
| `MYSQL_ATTR_SSL_VERIFY_SERVER_CERT` | `true` — không tắt xác minh để xử lý lỗi kết nối. |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `SESSION_SAME_SITE` | `lax` |
| `DB_QUEUE_RETRY_AFTER` | `90`, lớn hơn timeout worker `40` giây. |
| `LOG_CHANNEL`, `LOG_LEVEL` | `stderr`, `info` |
| `RUN_MIGRATIONS`, `RUN_SEEDERS` | `true`, `false` mặc định. |

Để trống/không tạo `DB_URL` và `DB_SOCKET`: chúng có thể đổi đích kết nối so với các trường MySQL đã kiểm tra nên image từ chối chúng. Không đặt `SESSION_DOMAIN=localhost`; bỏ biến này khi dùng domain service mặc định. Key/API token là biến phía server, tuyệt đối không đặt tiền tố `VITE_`.

Bạn có thể tự chạy `php artisan key:generate --show` ở máy phát triển để lấy **key mới riêng cho website này**, rồi lưu trực tiếp vào Render Environment. Lệnh có thể hiển thị secret trên terminal của bạn: không đưa kết quả vào Git, ảnh chụp, báo cáo hoặc chat. Entrypoint không tự sinh hoặc thay APP_KEY.

Không điền secret vào Docker build arguments. Render có thể đưa secret file vào build context; `.dockerignore` đã loại `*.pem` và các loại key nêu trên. Nếu thêm secret file với tên khác, thêm đúng tên đó vào `.dockerignore` trước khi build. [Docker on Render — environment and secrets](https://render.com/docs/docker#environment-variable-translation).

## 4. Migration và tạo admin lần đầu

Startup chạy theo thứ tự: kiểm tra biến → kiểm tra/quyền CA → kiểm tra cấu hình Nginx/FPM → kiểm tra kết nối TLS → `migrate --force` nếu bật → `ProductionSeeder` nếu bật → cache config/view → chạy web/worker/scheduler. Thất bại trước đó sẽ dừng container; không khởi động web với cấu hình DB sai. Không cache route vì ứng dụng hiện có closure routes.

Mặc định **`RUN_SEEDERS=false`**. Với database mới cần khởi tạo admin và catalog, đặt tạm:

```dotenv
RUN_SEEDERS=true
SEED_ADMIN_NAME=Ten quan tri cua ban
SEED_ADMIN_EMAIL=email-quan-tri-cua-ban@example.com
SEED_ADMIN_PASSWORD=MAT_KHAU_RIENG_IT_NHAT_12_KY_TU
```

Chuỗi trên chỉ mô tả cách điền; tự chọn password riêng, mạnh. Sau khi log báo seed thành công và bạn đăng nhập được **`/admin/login`**, đổi lại `RUN_SEEDERS=false`, xóa `SEED_ADMIN_PASSWORD` khỏi Environment và redeploy để loại password seed khỏi cấu hình cache mới.

`ProductionSeeder` tạo admin từ biến deployment và catalog có thể chạy lại. Admin đã tồn tại được giữ password/quyền; email trùng tài khoản không phải admin bị từ chối. Không có tài khoản mẫu mặc định. Không chạy `LocalDemoSeeder`, `migrate:fresh`, `db:wipe` hoặc import database máy cá nhân lên Aiven. Đọc migration mới và có bản backup thích hợp trước mỗi lần cập nhật schema trên database có dữ liệu; rollback image không tự rollback database.

## 5. AI, scheduler và tích hợp ngoài

Worker luôn dùng đúng kết nối và queue mà `config/ai_chat.php` chỉ định:

```text
php artisan queue:work database --queue=ai-chat --sleep=2 --tries=10 --timeout=40 --memory=96 --no-interaction
php artisan schedule:work --no-interaction
```

Lịch trong `routes/console.php` chạy `orders:expire-unpaid` mỗi phút, có khóa chống chồng lặp. Cả worker và scheduler chạy dưới `www-data`. Supervisor dùng Tini và process groups; SIGTERM đóng Nginx/FPM theo cách chờ request, báo dừng worker, dừng scheduler rồi cho tối đa 45 giây trước khi buộc dừng tiến trình còn lại. Nhà cung cấp vẫn có thể cưỡng chế dừng sớm hơn. Job đang bị gián đoạn có thể được thử lại theo cơ chế database queue. Giữ một web instance cho cấu hình lab này.

AI tắt mặc định. Để bật sau khi tự xác minh Groq organization đang dùng Free plan, cấu hình `AI_CHAT_ENABLED=true`, `GROQ_FREE_PLAN_CONFIRMED=true`, `GROQ_API_KEY`, model hỗ trợ và quota ứng dụng `AI_CHAT_DAILY_LIMIT`. Biến xác nhận là cam kết của người vận hành; mã không kiểm tra được billing plan. Không tạo thêm worker trả phí. Khi dịch vụ Render ngủ, các job chờ nằm trong Aiven nhưng không được xử lý cho tới lúc container thức.

`DEMO_MODE` chỉ có hiệu lực ở local/testing; deployment production không có mô phỏng thanh toán/giao hàng. Để kiểm thử checkout online, cần cấu hình sandbox/merchant của nhà cung cấp trước:

- **GHN:** `GHN_BASE_URL`, `GHN_TOKEN`, `GHN_SHOP_ID`, `GHN_FROM_DISTRICT_ID`, `GHN_VERIFY_SSL=true`. Dùng đúng endpoint/tài khoản sandbox được cấp cho bạn khi làm lab. Không gửi đơn vận chuyển thật để thử.
- **MoMo:** `MOMO_ENDPOINT` mặc định là test endpoint, nhưng vẫn cần `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY`, `MOMO_SECRET_KEY` hợp lệ. URL quay lại là `https://<domain>/payment/momo/callback`; IPN là `https://<domain>/payment/momo/ipn`. Đăng ký URL HTTPS đúng với provider; không coi redirect trình duyệt là bằng chứng thanh toán thành công.
- **GHN callback:** endpoint `POST /ghn/webhook` chỉ nhận khi header `X-Webhook-Token` khớp `GHN_WEBHOOK_TOKEN`. Đây là **token riêng của ứng dụng cho relay tin cậy**, không phải cơ chế chữ ký gốc của GHN. Chỉ bật qua relay đã xác minh thông điệp upstream và gắn header này; không đưa token vào URL. Không giả định GHN gửi trực tiếp header tùy chỉnh.
- **Email:** `MAIL_MAILER=log` không gửi mail. Cấu hình SMTP thực tế được phép sử dụng nếu cần OTP/reset password. Luồng xem OTP local không mở ở production.
- **Livestream:** camera/micro cần `LIVEKIT_URL` dạng `wss://` và `LIVEKIT_API_KEY`, `LIVEKIT_API_SECRET` của dịch vụ đã cấu hình. Chưa có LiveKit thì không coi chức năng phát trực tiếp đã kiểm chứng.

Không bật tích hợp khi chưa có sandbox phù hợp hoặc chưa xác nhận chi phí. Các credential để trống không ngăn container web khởi động nhưng chức năng tương ứng chưa sẵn sàng.

Khóa chống chồng lặp của tác vụ hết hạn đơn có TTL **5 phút**, thay vì mặc định 24 giờ: nếu bị ngắt trước khi giải phóng khóa, tác vụ có thể phải chờ tối đa thời gian khóa còn lại sau khi dịch vụ thức. Không xóa mọi mutex khi boot vì bản deploy cũ có thể còn chạy. Khóa từng đơn và hoàn kho idempotent vẫn bảo vệ dữ liệu nếu một lượt xử lý kéo dài quá TTL; cần đo thời gian xử lý và điều chỉnh TTL khi dữ liệu lớn.

## 6. Lưu trữ ảnh và dữ liệu

MySQL lưu dữ liệu ứng dụng bên ngoài container. Session, cache và queue cũng dùng MySQL nên không phụ thuộc SQLite/file cục bộ.

Ảnh catalog đi cùng mã có sẵn sau build. Tuy nhiên upload hiện tại vẫn ghi trực tiếp vào **`public/images/products`** và **`public/images/videos`**; `storage/app/public` cũng là filesystem container. Các file thêm/sửa lúc chạy sẽ mất khi redeploy, restart hoặc service ngủ; bản ghi MySQL có thể còn nhưng trỏ tới file đã mất. `storage:link` chỉ tạo symlink, không cung cấp lưu trữ bền vững. [Render filesystem limitations](https://render.com/docs/free#local-files-lost-on-redeploy).

Bản chuẩn bị này chưa đổi upload sang object storage. Chỉ dùng ảnh có sẵn trong repository cho bài lab và không hứa giữ upload. Muốn upload bền vững cần sửa các controller đang dùng `public_path(...)` sang storage adapter bên ngoài, hoặc thiết kế volume phù hợp trên hạ tầng cho phép; chỉ đặt `FILESYSTEM_DISK=s3` chưa đủ. Không tự thêm disk trả phí vào Render Free.

## 7. Kiểm tra sau khi bạn tự deploy

1. **Startup:** xem log có `Production environment validated`, `Aiven CA certificate validated`, `Verified TLS MySQL connection established`, migration hoàn tất và `Web, AI database queue worker and scheduler started`. Không chia sẻ ảnh log có secret.
2. **Health và asset:** mở HTTPS `/up` thấy HTTP 200; mở trang chủ, trang sản phẩm, các file `/build/assets/...` không lỗi 404. `/up` kiểm tra ứng dụng khởi động, không thay thế kiểm thử toàn bộ DB/queue/API.
3. **HTTPS/session:** đăng nhập `/admin/login` bằng admin vừa tạo, chuyển trang và refresh không bị mất đăng nhập; không có 419/redirect loop/mixed content. Cookie phiên có Secure và URL do ứng dụng sinh dùng HTTPS.
4. **Database:** sửa một dữ liệu thử nghiệm có thể hoàn tác trong admin, redeploy và xác nhận bản ghi còn. Không dùng upload mới làm bài kiểm tra persistence vì đã có giới hạn ở trên.
5. **Phân quyền:** người dùng thường không vào được admin; các trang/thao tác mô phỏng local không mở ở production.
6. **AI:** nếu đã bật bằng tài khoản Free hợp lệ, gửi một câu hỏi từ tài khoản khách và kiểm tra phản hồi tự động trong chat. Nếu không nhận phản hồi, xem log worker, khóa chống lặp, quota và cấu hình API; không đổi queue sang `sync`.
7. **Đơn hàng:** chỉ khi sandbox đã cấu hình, tạo một đơn thử, kiểm tra trạng thái/payment transaction, callback hợp lệ và cập nhật tồn kho. Với một đơn online chưa trả tiền, không có shipment, đợi qua `payment_expires_at` khi service đang thức; xác nhận scheduler hủy đơn và hoàn tồn kho một lần. Kiểm tra đơn đã trả tiền không bị hủy.
8. **Callback:** thử bằng sandbox/relay được phép; callback MoMo sai chữ ký và GHN thiếu/sai header phải bị từ chối. Callback có thể bị chậm vì Free ngủ; không tuyên bố đã nghiệm thu callback chỉ dựa vào `/up`.

## 8. Xử lý lỗi thường gặp và giới hạn kiểm chứng

| Triệu chứng | Kiểm tra |
| --- | --- |
| Build thiếu lock hoặc lỗi package theo OS | Commit lock phù hợp; không đưa dependency trực tiếp chỉ dành cho Windows vào `package.json`. `npm ci` cần lock khớp manifest. |
| Startup báo thiếu biến/APP_KEY sai | Điền giá trị riêng trong Render Environment; không thay key mỗi redeploy. |
| CA không đọc được/không hợp lệ | Filename chính xác `ca.pem`, đầy đủ PEM hiện hành của project Aiven; kiểm tra Secret Files. Không tắt verify. |
| Cả 5 lần TLS MySQL thất bại | Kiểm tra host/port/database/user/password, trạng thái Aiven, CA mới, IP allowlist; không dán credential vào issue/log công khai. |
| Migration/seed dừng deployment | Đọc lỗi schema và quyền DB. Seed lần đầu cần email và password riêng ít nhất 12 ký tự. Không xóa database để vượt lỗi. |
| 502 hoặc restart lặp | Xem tiến trình nào thoát trong log; xem mức RAM, PHP/Nginx và worker. Image thoát nếu worker/scheduler chết dù Nginx vẫn có thể trả HTTP. |
| AI chờ mãi/đơn quá hạn chưa đổi | Service có đang ngủ không, worker đúng `database`/`ai-chat`, scheduler đang chạy, khóa/cache/DB có hoạt động không. |
| 419/URL HTTP | `APP_URL` đúng HTTPS, `TRUSTED_PROXIES` đúng, cookie Secure, session DB đã migrate. |
| Ảnh upload mất sau redeploy | Filesystem tạm thời; xem phần lưu trữ, không phải lỗi migration. |

Đã kiểm tra cú pháp PHP/Bash, định dạng LF và validator cấu hình với một bộ biến giả lập hợp lệ cùng 12 trường hợp sai. Validator CA đã được thử với CA tạm hợp lệ, đường dẫn thiếu, nội dung không phải PEM và chứng chỉ không phải CA; không dùng credential thật. **Chưa kiểm chứng Docker build/runtime Linux, Nginx/FPM trong image, signal handling trong container, kết nối TLS Aiven, migration MySQL hoặc E2E trên Render**, vì chưa có Docker và chưa thực hiện deployment online. Khi có môi trường Docker, có thể chạy `docker build -t perfume-shop:lab10 .` tại root trước; bước chạy container cần bộ cấu hình/CA riêng phù hợp. Không sử dụng kết quả test SQLite local để khẳng định MySQL/Aiven đã chạy thành công.
