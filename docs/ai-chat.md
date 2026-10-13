# Chat tự động bằng AI

Tích hợp vào khung chat khách hàng hiện có, không thay thế phần hỗ trợ của quản trị viên. Gọi một API Chat Completions chuẩn OpenAI bằng Laravel HTTP Client. Mặc định dùng https://aixingialaire.com với model `gpt-5.6-sol`; đổi nhà cung cấp bằng `AI_CHAT_BASE_URL` và `AI_CHAT_MODEL` (bắt buộc HTTPS).

## Bật trên môi trường chạy web

1. Chạy trong thư mục `webbannuochoa-latest`. Cài dependency bằng `composer install`. Bản clone mới cần `npm install` rồi `npm run build` để tạo giao diện chung. Với bản clone mới chưa có `.env`, sao chép `.env.example` thành `.env`, chạy `php artisan key:generate`, rồi cấu hình database của bản web này. Không ghi đè `.env` đang sử dụng và không đổi APP_KEY của một hệ thống đã có dữ liệu.
2. Lấy API key từ nhà cung cấp. Điền trực tiếp trong `.env`, không gửi vào chat và không commit lên Git:

```dotenv
AI_CHAT_ENABLED=true
AI_CHAT_DAILY_LIMIT=50
AI_CHAT_BASE_URL=https://aixingialaire.com/v1
AI_CHAT_API_KEY=your_private_api_key
AI_CHAT_MODEL=gpt-5.6-sol
```

3. Sao lưu database đang dùng, rồi chạy migration thông thường (không dùng `migrate:fresh`):

```sh
php artisan config:clear
php artisan migrate
php artisan queue:work database --queue=ai-chat --sleep=1 --timeout=40 --tries=10
```

Giữ terminal worker hoạt động. Trên server, dùng trình quản lý tiến trình để worker tự khởi động lại. AI cố định dùng kết nối queue `database`, hàng đợi `ai-chat`, kể cả khi queue mặc định của ứng dụng là `sync`. Cần bảng `jobs` và `failed_jobs` từ migration sẵn có, cache hỗ trợ lock (database hoặc Redis khi chạy nhiều server). Giá trị `retry_after` phải lớn hơn timeout 40 giây; mặc định database queue là 90 giây.

4. Web cần ít nhất một tài khoản có `role=admin`. Đăng nhập bằng tài khoản khách hàng, mở Tư vấn và gửi câu hỏi. Chờ tin nhắn có nhãn **Trợ lý AI**. Các trang cũ dùng frontend này không cần build lại npm để nhận file `public/js/customer-chat.js`.
5. Sau khi sửa `.env` hoặc cập nhật code trên server: `php artisan config:clear`, `php artisan queue:restart`; nếu triển khai dùng config cache thì chạy lại `php artisan config:cache`. Worker cần khởi động lại để nhận cấu hình mới.

Nhà cung cấp tính phí theo lượng token sử dụng; theo dõi chi phí ở trang quản lý của nhà cung cấp.

Web giới hạn tối đa 50 lần gọi/ngày UTC cho toàn bộ khách hàng, lưu bền trong `ai_chat_usage`; tính cả lần gọi lỗi. Có thể giảm bằng `AI_CHAT_DAILY_LIMIT`, đặt 0 để chặn toàn bộ. Khi nhận 429, web ngừng gọi tối thiểu 60 giây (theo Retry-After, tối đa 24 giờ), không tự thử lại. Chat người thật vẫn dùng được.

## Luồng xử lý

Khách gửi → lưu `messages` → tạo job `ReplyToCustomerMessage` → đọc danh mục công khai và tối đa 8 tin nhắn gần nhất của đúng khách → kiểm tra quota → gọi nhà cung cấp AI → kiểm tra lại trạng thái cuộc trò chuyện → lưu phản hồi → giao diện tải tin nhắn mới mỗi 3 giây khi đang mở.

- `AiChatService`: API key chỉ nằm phía máy chủ; endpoint là `AI_CHAT_BASE_URL` + `/chat/completions`.
- `chat_conversations.human_mode`: khách bấm **Gặp nhân viên** hoặc admin gửi tin thì dừng AI; khách bấm **Bật lại AI** để dùng cho tin nhắn tiếp theo. Tin nhắn cũ không tự chạy lại.
- `messages.is_ai`: phân biệt AI với nhân viên trên cả hai giao diện.
- `messages.reply_to_id`: liên kết với câu hỏi gốc, unique để tránh lưu phản hồi trùng.
- `messages.ai_status`: `pending`, `replied`, `failed`, `skipped`; tin nhắn thường có thể để trống.
- Lock theo khách và kiểm tra lại sau API giúp loại bỏ phản hồi cũ khi khách gửi thêm tin hoặc nhân viên tiếp quản.
- Tin chờ quá 2 phút không tiếp tục được AI trả lời. Giao diện thông báo sự cố, giữ nguyên nội dung khách đã gửi.

## Dữ liệu và giới hạn

- Chỉ gửi tối đa 6 sản phẩm đang hoạt động, ưu tiên tên/thương hiệu khớp từ khóa câu hỏi; mô tả được bỏ HTML và giới hạn 180 ký tự, mỗi tin lịch sử giới hạn 600 ký tự. Danh sách không đại diện toàn bộ kho. Giới hạn phản hồi 1.200 token (bao gồm reasoning nếu có), chỉ hiển thị câu trả lời cuối.
- Giá 10/50/100ml dùng cùng công thức hiện tại của giỏ hàng; tồn kho lấy từ `Perfume::getStockForVolume`. Khi thay đổi công thức giá trong giỏ hàng cần cập nhật phần cung cấp dữ liệu AI tương ứng.
- Không truy vấn và gửi bảng đơn hàng, email tài khoản, địa chỉ hoặc lịch sử của khách khác. Tuy nhiên dữ liệu khách tự gõ trong chat có thể được gửi cho nhà cung cấp AI; giao diện đã thông báo điều này. Không nhập thông tin bí mật.
- AI không có công cụ đặt/hủy đơn, thanh toán, sửa database hoặc tra cứu thông tin riêng tư. Câu hỏi về đơn hàng được hướng dẫn sang trang đơn hàng/nhân viên.
- Có chỉ dẫn chống bịa thông tin và bỏ qua chỉ dẫn trong dữ liệu đầu vào, nhưng AI vẫn có thể trả lời sai; cần kiểm tra chất lượng bằng dữ liệu thật trước khi mở cho khách.
- Chỉ khách đã đăng nhập được dùng chat; gửi tin và đổi chế độ giới hạn 15 lần/phút. Tin khách tối đa 1.000 ký tự, nội dung được render dạng văn bản, không thực thi HTML từ AI.
- Nếu thiếu key, tắt AI, hết hạn mức hoặc lỗi kết nối: không tạo câu trả lời giả. Khách vẫn gửi được tin cho admin.

## Kiểm thử

```sh
php artisan test --filter="AiChatTest|LiveChatTest"
```

Kiểm thử dùng SQLite in-memory và HTTP giả lập, không gọi nhà cung cấp thật hoặc tiêu tốn API quota. Môi trường kiểm thử cần APP_KEY riêng hợp lệ.

Thử thủ công: hỏi giá/tồn kho; gửi liên tiếp hai câu; chọn Gặp nhân viên khi AI đang trả lời; admin phản hồi; bật AI lại; thử giới hạn 429 và worker bị dừng; xác nhận không có tin trùng và nháp không bị xóa khi gửi lỗi.

## Tắt AI

Đặt `AI_CHAT_ENABLED=false`, xóa cache cấu hình và khởi động lại worker. Chat giữa khách và nhân viên vẫn hoạt động.
