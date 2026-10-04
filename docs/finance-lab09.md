# Lab 09 — Thống kê tài chính và quản lý giao dịch

## 1. Truy cập và chuẩn bị

Đăng nhập bằng tài khoản admin tại `/admin/login`. Menu có **Thống kê tài chính** và **Giao dịch thanh toán**. Khách chưa đăng nhập phải đăng nhập; tài khoản thường không được truy cập hoặc cập nhật Finance.

Với bản local đã có dữ liệu, sao lưu SQLite rồi cập nhật schema:

```powershell
php scripts/backup-sqlite.php
php artisan migrate --force
npm ci
npm run build
```

Không chạy `migrate:fresh`; không seed lại dữ liệu giao dịch thật. Nếu có Vite đang chạy, dừng Vite của đúng dự án trước `npm ci`, rồi khởi động lại sau khi build. Máy dùng MySQL cần tự sao lưu bằng công cụ MySQL trước migration, không dùng script backup SQLite.

Migration mới chỉ tạo `payment_status_events` để lưu lịch sử đối soát, không tự thay đổi giao dịch đã có. Bản kiểm thử tự động dùng SQLite in-memory và HTTP giả, không gọi cổng thanh toán/AI thật.

## 2. Hai màn hình

| Màn hình | Đường dẫn | Chức năng |
| --- | --- | --- |
| Tổng quan tài chính | `GET /admin/finance` | Số đơn, tổng giá trị, thống kê trạng thái, thống kê phương thức, giá trị đã thanh toán từng phương thức và danh sách đơn. |
| Quản lý giao dịch | `GET /admin/finance/transactions` | Tra cứu, đối soát COD hợp lệ, xem thời điểm thu tiền và lịch sử người cập nhật. |
| Xuất CSV | `GET /admin/finance/export` | Xuất toàn bộ kết quả bộ lọc, không chỉ trang đang xem. |
| Cập nhật COD | `PATCH /admin/finance/{order}/status` | Kiểm tra quyền, CSRF, giới hạn tần suất, trạng thái và dữ liệu form trước khi lưu. |

Bộ lọc dùng chung gồm từ khóa, ngày bắt đầu/kết thúc, khoảng giá trị đơn, phương thức thanh toán, trạng thái thanh toán, loại dữ liệu thực/demo và sắp xếp mới/cũ/giá tăng/giá giảm. Từ khóa tìm mã đơn (số, `#123`, `DH123`), tên hoặc số điện thoại. Mỗi trang có 15 đơn và giữ bộ lọc khi chuyển trang.

Ngày kết thúc được tính hết ngày đã chọn. Khoảng ngày/giá trị bị đảo, phương thức/trạng thái không hợp lệ hoặc số trang âm bị từ chối. Tất cả truy vấn chỉ lấy đơn có `created_at <= now()`.

## 3. Cách tính, tránh đếm trùng

Một đơn có thể thử thanh toán nhiều lần nhưng Finance chỉ lấy **một giao dịch đại diện**:

1. Ưu tiên nhóm `paid`, `refund_pending`, `refunded`.
2. Trong cùng nhóm ưu tiên, chọn ID giao dịch lớn nhất.
3. Nếu chưa có giao dịch, suy luận trạng thái từ đơn cũ. Không đủ thông tin phương thức thì ghi **Chưa xác định** thay vì tự đoán COD.

Thống kê và biểu đồ tính trên **toàn bộ kết quả sau lọc**, trước phân trang. Tổng giá trị dùng `orders.total_price`, gồm phí giao hàng. `paid_amount` của mỗi phương thức chỉ cộng đơn có trạng thái thanh toán đại diện hiện tại là `paid`; không cộng đơn đang chờ hoàn hoặc đã hoàn.

Đây là báo cáo **giá trị đơn theo trạng thái hiện tại**, không phải lợi nhuận, doanh thu kế toán theo ngày thu tiền hay sao kê dòng tiền. Chọn ngày là chọn **ngày tạo đơn**, không phải `paid_at`. Số liệu hoàn tiền không chứng minh tiền đã được chuyển bởi hệ thống.

Mặc định xem giao dịch thực: loại `is_demo=true` và giao dịch đại diện mang gateway `demo`. Chọn **Chỉ đơn demo** để trình bày dữ liệu mô phỏng, không cộng lẫn vào số liệu thực.

## 4. Các trạng thái và quy tắc COD

Các trạng thái hiển thị: `pending` (chờ thanh toán), `initiated` (đang xử lý), `paid` (đã thanh toán), `failed` (thất bại), `cancelled` (đã hủy), `refund_pending` (chờ hoàn), `refunded` (đã hoàn).

| Trạng thái COD hiện tại | Có thể chuyển đến |
| --- | --- |
| `pending` | `paid`, `failed` |
| `failed` | `pending`, `paid` |
| `paid` | `refund_pending` |
| `refund_pending` | `refunded` |
| `cancelled`, `refunded`, `initiated` | Không có chuyển trạng thái thủ công mới trong Finance. |

Ràng buộc bổ sung:

- Chỉ COD được ghi nhận thu tiền thủ công tại Finance. SePay xác nhận thu tiền qua webhook; admin có thể ghi nhận hoàn tiền đã thực hiện bên ngoài, kèm mã chứng từ. MoMo cũ giữ nguyên lịch sử. Xem [SePay và trang đối soát webhook](sepay.md).
- Đơn đã hủy hoặc đang/đã trả hàng không được thu tiền hay mở lại chờ thu. Đơn đã thu vẫn có thể tiếp tục quy trình ghi nhận hoàn tiền hợp lệ.
- `paid_at` được ghi ở lần thu đầu tiên và không bị thay khi gửi lặp. Giao dịch COD legacy chưa có bản ghi sẽ được tạo khi có thay đổi hợp lệ.
- Chỉ khi thu tiền cho đơn `pending`, `confirmed`, `cod_ordered` mới đổi dấu trạng thái đơn thành `cod_paid`. Không kéo lùi đơn đã hoàn thành hoặc thay trạng thái vận chuyển.
- Hoàn tiền thực cần mã chứng từ 3–120 ký tự. Đây là ghi nhận khoản hoàn đã làm bên ngoài: **không gọi API chuyển tiền**, không tạo shipment và không trừ/hoàn kho.

## 5. Chống xung đột và lịch sử

Form gửi kèm ID giao dịch đại diện, trạng thái thanh toán và trạng thái đơn tại lúc mở trang. Server khóa đơn/giao dịch trong transaction rồi so sánh lại. Form cũ bị từ chối để admin tải lại, tránh ghi đè cập nhật khác.

Gửi lặp cùng thao tác ngay sau khi thành công không tạo bản ghi thu tiền/lịch sử thứ hai nếu kết quả đó vẫn còn hiện tại. Không dùng cơ chế này để bỏ qua thay đổi mới phát sinh sau đó.

Mỗi thay đổi hợp lệ ghi `payment_status_events`: mã đơn/giao dịch, admin thực hiện và tên tại thời điểm thao tác, trạng thái trước/sau, trạng thái đơn trước/sau, thời điểm, chứng từ hoàn nếu có và dấu vân tay yêu cầu. Danh sách giao dịch có mục **Lịch sử đối soát** để xem. Đây là nhật ký thao tác Finance, không phải toàn bộ webhook từ các hệ thống ngoài.

## 6. Xuất CSV và bảo vệ dữ liệu

CSV UTF-8 có BOM để mở tiếng Việt trong Excel, cùng bộ lọc và thứ tự với giao diện. Các cột gồm mã đơn, mã thanh toán, ngày tạo, giá trị đơn, phương thức, trạng thái thanh toán/đơn, ngày thu tiền, nhãn REAL/DEMO.

CSV không xuất tên, điện thoại, địa chỉ khách, payload gateway hoặc mã chứng từ hoàn tiền. Giá trị bắt đầu như công thức bảng tính được vô hiệu hóa để tránh thực thi khi mở file.

## 7. Kịch bản trình bày / nghiệm thu local

1. Đăng nhập admin, mở Tổng quan; chọn loại dữ liệu đúng (demo nếu đang dùng đơn mô phỏng). So sánh tổng số đơn với danh sách.
2. Lọc COD, khoảng ngày và khoảng giá trị; thử đổi sắp xếp, chuyển trang. Xác nhận thẻ tổng vẫn là toàn bộ tập đã lọc.
3. Xuất CSV, đối chiếu số dòng với tổng số đơn đã lọc, không chỉ 15 dòng trang đầu.
4. Trên **bản dữ liệu thử riêng**, tạo đơn COD chờ thanh toán. Ở Giao dịch, chuyển sang đã thanh toán và xem `paid_at` cùng lịch sử admin.
5. Mở hai tab cùng giao dịch thử; cập nhật ở tab đầu rồi gửi trạng thái khác từ tab cũ. Hệ thống phải yêu cầu tải lại. Gửi lặp đúng thao tác thành công không tạo lịch sử trùng.
6. Kiểm tra đơn bị hủy/đang trả hàng không được đánh dấu đã thu tiền. Với giao dịch thử đã thu, ghi chờ hoàn rồi đã hoàn; nếu là dữ liệu thực phải có mã chứng từ. Không thực hiện bước này trên giao dịch thật chỉ để demo.
7. Đăng nhập người dùng thường, thử truy cập Finance: phải bị chặn. Xác nhận CSV/bảng không trộn demo và thực.

```powershell
php vendor/bin/phpunit tests/Feature/FinanceTest.php
php vendor/bin/phpunit tests/Feature/DeploymentReadinessTest.php
php vendor/bin/phpunit
```

Các kiểm thử hiện bao phủ phân quyền, bộ lọc, tổng trước phân trang, chọn giao dịch đại diện, ngày tương lai, số đã thanh toán từng gateway, tách demo, chuyển COD, form cũ, gửi lặp, hoàn tiền, nhật ký và CSV. Khóa đồng thời/InnoDB trên MySQL vẫn phải kiểm thử thêm khi có môi trường triển khai; kết quả SQLite không thay thế nghiệm thu MySQL.
