{{-- This view is text/plain only; preserve literal text rather than HTML entities. --}}
{!! __('SOOPI · PERFUME STUDIO

Chào') !!} {!! $customerName !!}{!! __(',

Soopi nhận được yêu cầu đặt lại mật khẩu tài khoản của bạn.
Mở liên kết sau để tạo mật khẩu mới:') !!}
{!! $resetUrl !!}

{!! __('Liên kết chỉ dùng một lần, có hiệu lực') !!} {!! $expiresMinutes !!} {!! __('phút kể từ lúc yêu cầu.
Nếu bạn không yêu cầu thay đổi, hãy bỏ qua email này; mật khẩu hiện tại vẫn được giữ nguyên.
Không chia sẻ liên kết hoặc mật khẩu với người khác.

Một chút hương. Một chút thương.') !!}
