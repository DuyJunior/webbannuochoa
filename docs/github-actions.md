# GitHub Actions và VPS hiện tại

Workflow `.github/workflows/docker.yml` chạy khi push/PR vào `main`, hoặc bấm
Run workflow. PR chỉ test; push main test rồi publish image
`<DOCKERHUB_USERNAME>/webbannuochoa:<commit SHA>` và `:main` lên Docker Hub.
Deploy dùng digest SHA256 của image vừa publish, không dùng tag trôi nổi.

PHP/Composer/Node chạy trong Docker cả local, CI và VPS. CI chạy PHPUnit, test JS,
ShellCheck, build production, MySQL migration và HTTP/login/assets smoke test.
Các action chính thức được pin theo commit. Deploy được tuần tự hóa, không bị
hủy giữa migration khi có push mới.

## Secrets và variables

Secrets đã được người dùng thêm: DOCKERHUB_USERNAME, DOCKERHUB_TOKEN, VPS_HOST,
VPS_USERNAME, VPS_SSH_KEY. Docker Hub token cần quyền push repository
`webbannuochoa`; VPS key phải đăng nhập được user có quyền Docker.

Variables:

- DEPLOY_ENABLED: `false` lúc chuẩn bị, đổi thành `true` khi production sẵn sàng.
- VPS_SSH_HOST_KEY: public key ED25519 của VPS, lấy qua kết nối SSH đã tin cậy.
- VPS_PORT: tùy chọn, mặc định `22`.

Manual Run workflow với `deploy=true` vẫn yêu cầu DEPLOY_ENABLED=true. Chỉ main
được publish/deploy. Không dùng `pull_request_target` hoặc đưa secrets cho PR.

## Cách bố trí trên VPS

VPS hiện tại: `103.28.32.141`, SSH alias `vps`, user `hieu`. Domain: `soopi.site`.
Deploy chỉ quản lý Compose project `soopi` trong `/home/hieu/soopi`; Nginx host
chuyển tiếp tới `127.0.0.1:18082`. Không dùng `compose.vps.yaml` trên VPS này vì
Caddy sẽ tranh cổng 80/443 với Nginx hiện có. Dùng `compose.host.yaml`.

Thư mục gồm `.deployment-owner` chứa tên repo, `.env` production riêng (quyền
600), `releases/<commit>/` chứa Compose/script, `backups/<UTC timestamp>/` và
`.deployed-image`/`.deployed-release`. Không đưa .env production lên GitHub.

`docker/deploy.sh` kiểm tra ownership/path, khóa bằng flock, yêu cầu ít nhất 4 GiB
trống, dùng Docker login tạm cho riêng đợt deploy, pull image và validate config.
Khi app đã chạy, script bật maintenance, dừng riêng app Soopi để worker không ghi
trong lúc backup SQL/uploads/env, rồi khởi động phiên bản mới, kiểm tra health,
tắt maintenance và kiểm tra trang chủ. Database/ảnh dùng named volume riêng.

Script không prune, không `down`, không `--remove-orphans`, không đổi host Nginx,
không đụng Compose project khác. Nếu backup/migration lỗi, dừng triển khai để
kiểm tra; không tự rollback schema. Backup chứa dữ liệu nhạy cảm và cần được
sao lưu ra ngoài VPS; chưa thiết lập retention tự động.

## Bật HTTPS một lần

Tạo DNS A cho `@` trỏ `103.28.32.141`. Chưa tạo AAAA nếu chưa cấu hình IPv6 cho
website. Chờ DNS trả đúng IP rồi SSH và chạy script đã chuẩn bị:

```sh
ssh vps
sudo bash /home/hieu/soopi/setup-proxy.sh
```

Script chỉ tạo virtual host `soopi.site`, dùng Certbot webroot để cấp chứng chỉ,
kiểm tra cấu hình rồi graceful reload Nginx. Không cài PHP/XAMPP. Cần nhập mật
khẩu sudo trong terminal của bạn; không gửi mật khẩu vào chat. Script không
ghi đè một file soopi.site có sẵn nếu không mang marker của dự án này.

Khi DNS/TLS và .env đã sẵn sàng, bật DEPLOY_ENABLED rồi chạy workflow trên main
với deploy=true. Kiểm tra `https://soopi.site/up`, trang chủ và `/admin/login`.
AI/SMTP/SePay/GHN/LiveKit cần credential riêng, không được tự bật bởi workflow.
