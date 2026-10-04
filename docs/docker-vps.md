# Chạy bằng Docker và chuẩn bị VPS

Máy cá nhân chỉ cần Git và Docker Desktop đang chạy Linux containers. VPS cần
Docker Engine + Compose plugin. PHP, Composer, Node/npm, Nginx và MySQL đều nằm
trong Docker; không chạy các script `setup-local.ps1`/`start-local.ps1` cũ.

## Local trên Windows

Chạy PowerShell tại thư mục repo:

```powershell
docker build --target production -t soopi:local .
docker run --rm --mount "type=bind,source=$($PWD.Path),target=/setup" --entrypoint php soopi:local docker/init-env.php local
docker compose -f compose.yaml -f compose.local.yaml up -d --wait --wait-timeout 180
```

Lệnh init tạo `.env` với APP_KEY và hai mật khẩu MySQL ngẫu nhiên, không in secret
và từ chối ghi đè `.env` đã có. Giữ nguyên APP_KEY khi dữ liệu đã tồn tại.

Website: http://localhost:8002. Admin: http://localhost:8002/admin/login.
Tài khoản local: `admin.demo@example.test` hoặc `customer.demo@example.test`,
mật khẩu `DemoPerfume2026!`. Chế độ local dùng thanh toán/vận chuyển giả lập.
AI, SMTP và LiveKit chưa bật. Đổi APP_PORT trong `.env` nếu cổng 8002 bị dùng.

```powershell
# Trạng thái và log
docker compose -f compose.yaml -f compose.local.yaml ps
docker compose -f compose.yaml -f compose.local.yaml logs --tail=100 app
# Chạy Artisan trong container
docker compose -f compose.yaml -f compose.local.yaml exec --user www-data app php artisan migrate:status
# Build lại sau khi sửa mã (không bind-mount source)
docker compose -f compose.yaml -f compose.local.yaml up -d --build --wait --wait-timeout 180
# Dừng, giữ dữ liệu
docker compose -f compose.yaml -f compose.local.yaml down
```

Không dùng `down -v` nếu cần giữ database/ảnh. Thay mật khẩu trong `.env` không tự
đổi mật khẩu của MySQL volume đã khởi tạo; phải đổi trong MySQL tương ứng.

## Kiểm thử hoàn toàn trong Docker

```powershell
docker build --target test -t soopi:test .
docker run --rm soopi:test
docker run --rm --mount "type=bind,source=$($PWD.Path),target=/app,readonly" -w /app node:22-bookworm-slim node --test tests/Js/livestream-ui.test.cjs
```

PHPUnit dùng SQLite in-memory độc lập; không chạm MySQL đang chạy. Target test có
dependency dev. Image production không có Composer, Node, PHPUnit hay tests.
Frontend được `npm ci` + `npm run build` ngay trong Dockerfile.

## VPS Linux: production với HTTPS

Cấu hình dành cho một VPS, một app container (Nginx + PHP-FPM + worker AI +
scheduler), MySQL 8.4 và Caddy. Không scale app khi chưa tách scheduler/migration.
MySQL không publish cổng 3306; app và DB nối qua mạng Docker `internal` riêng,
không dùng TLS trên kết nối nội bộ cùng máy. Không đổi DB_HOST sang DB bên ngoài:
validator sẽ từ chối. Cấu hình Render/Aiven cũ vẫn yêu cầu TLS xác thực CA.

Trước khi chạy trên VPS, cần domain thật trỏ DNS A/AAAA đúng IP, cổng TCP 80/443
được phép truy cập, và các cổng đó chưa bị reverse proxy khác chiếm. Nếu VPS đã
có Nginx/Traefik/Caddy, điều chỉnh phần proxy theo hạ tầng đó trước khi chạy.

Sau khi đưa **bản mã có thay đổi Docker này** lên VPS (repo GitHub gốc chưa chứa
các thay đổi local), chạy ở root repo bằng shell Linux:

```sh
docker build --target production -t soopi:vps .
docker run --rm --user "$(id -u):$(id -g)" --mount "type=bind,source=$(pwd),target=/setup" --entrypoint php soopi:vps docker/init-env.php vps
chmod 600 .env
# Sửa .env bằng trình soạn thảo của bạn trước bước tiếp theo.
docker compose -f compose.yaml -f compose.vps.yaml config --quiet
docker compose -f compose.yaml -f compose.vps.yaml up -d --wait --wait-timeout 240
```

Trong `.env`, điền APP_DOMAIN, APP_URL=`https://<domain>`, ACME_EMAIL,
SEED_ADMIN_EMAIL và SEED_ADMIN_PASSWORD riêng (ít nhất 12 ký tự), đặt
RUN_SEEDERS=true cho lần khởi tạo đầu. Sau khi tạo được admin, đặt
RUN_SEEDERS=false, xóa SEED_ADMIN_PASSWORD rồi chạy lại `up -d --force-recreate app`
với cùng hai file Compose. Seeder không ghi đè tài khoản/tồn kho đã có.
Không dùng `.env` hoặc database demo từ máy cá nhân cho production.

Compose production ép APP_ENV=production, APP_DEBUG=false, DEMO_MODE=false và
cookie Secure. Caddy tự cấp/gia hạn TLS, lưu certificate trong volume. App không
publish cổng ra host; chỉ Caddy truy cập được qua mạng frontend, và chỉ tin proxy
trong các dải IP riêng. Không nối container không tin cậy vào mạng này.

Không ghép `compose.local.yaml` khi deploy. File đó mở HTTP localhost và tạo tài
khoản demo. `.env.vps.example` là mẫu, chưa có domain/credential thật. Không commit
`.env`; không dùng `docker compose config` không có `--quiet` khi chia sẻ log vì
lệnh đó có thể in secret.

## Dữ liệu, backup và cập nhật

Named volume lưu MySQL, storage, ảnh sản phẩm, video và ảnh đánh giá. Volume rỗng
nhận file có sẵn từ image ở lần chạy đầu. File catalog mới thêm vào image về sau
không tự ghi đè volume đang tồn tại; cần đồng bộ có chọn lọc khi thay catalog.
Vite build vẫn nằm trong image, không bị volume che mất. Giữ COMPOSE_PROJECT_NAME
ổn định; đổi tên project sẽ dùng bộ volume khác.

Ví dụ backup trên VPS, trước khi đổi schema (thực hiện trong giờ bảo trì để
database và uploads không thay đổi giữa hai lần chụp):

```sh
mkdir -p backups
chmod 700 backups
stamp=$(date +%Y%m%d-%H%M%S)
docker compose -f compose.yaml -f compose.vps.yaml stop proxy app
docker compose exec -T db sh -c 'umask 077; MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot --single-transaction --no-tablespaces --routines --events --triggers --set-gtid-purged=OFF "$MYSQL_DATABASE" > /tmp/soopi-backup.sql'
docker compose cp db:/tmp/soopi-backup.sql "backups/database-$stamp.sql"
docker compose exec -T db rm /tmp/soopi-backup.sql
docker compose run --rm --no-deps --entrypoint tar -v "$(pwd)/backups:/backup" app -czf "/backup/uploads-$stamp.tar.gz" storage public/images/products public/images/videos public/images/reviews
docker compose -f compose.yaml -f compose.vps.yaml up -d --wait --wait-timeout 240
```

Sao lưu `.env` an toàn riêng, chuyển backup ra ngoài VPS, kiểm tra khả năng restore
trước khi cần dùng. Không chạy `migrate:fresh`/`db:wipe`. Backup SQL chứa dữ liệu
người dùng; thư mục backups bị loại khỏi Git và Docker build context.

Sau backup và đọc migration mới:

```sh
docker compose -f compose.yaml -f compose.vps.yaml up -d --build --wait --wait-timeout 240
docker compose -f compose.yaml -f compose.vps.yaml logs --tail=100 app
```

Kiểm tra `/up`, trang chủ, CSS/JS, đăng nhập admin, upload và ảnh còn sau khi recreate.
Migration chạy lúc boot khi RUN_MIGRATIONS=true; có thể tắt để chạy thủ công trong
quy trình triển khai riêng. Rollback image không tự rollback schema. Healthcheck
HTTP kiểm tra app boot; không thay thế monitoring database, disk và backup.

Production không mô phỏng checkout: cần cấu hình GHN/SePay trước khi nghiệm thu
luồng mua hàng, SMTP để gửi mail, Groq/LiveKit nếu dùng AI/livestream. Không coi các
tích hợp ngoài đã hoạt động chỉ vì container healthy.

## Kết quả kiểm tra local ngày 29/09/2026

- Build production Linux thành công, gồm Composer kiểm tra platform và Vite build.
- MySQL 8.4 và app healthy; migration, seed 12 sản phẩm/2 tài khoản local thành công.
- Nginx, PHP-FPM, worker AI và scheduler chạy; tác vụ hết hạn đơn được scheduler gọi.
- 213 test PHP / 1479 assertions và 2 test JavaScript đều qua trong container.
- HTTP trang chủ, health, đăng nhập admin/dashboard và asset build đều thành công.
- `.env` bị chặn 403; PHP ngoài front controller bị chặn 404.
- Recreate cả app và DB vẫn giữ bản ghi kiểm tra MySQL và file trong cả 4 volume
  storage/products/videos/reviews; các file này vẫn truy cập HTTP được.
- Compose VPS và Caddy validate thành công với domain giả; chưa cấp TLS thật,
  chưa kết nối VPS, chưa kiểm thử các dịch vụ AI/thanh toán/vận chuyển/livestream.

Các lỗi hạ tầng phát hiện và sửa khi chạy thực tế: quyền tạo symlink public/storage,
FastCGI Debian làm mất cổng URL khi redirect; bộ test thiếu APP_KEY riêng và browser
mocks cho livestream. Mã nghiệp vụ livestream không thay đổi.

Cấu hình chuyển khoản BIDV, HMAC và kiểm tra `sepay:check`: xem [SePay](sepay.md).
