@php
    $emailLabels = ['placed' => 'Xác nhận đặt hàng', 'paid' => 'Xác nhận thanh toán', 'dispatched' => 'Đang vận chuyển', 'delivered' => 'Giao thành công'];
    $emailReasons = [
        'delivery_disabled' => 'Gửi thông báo đang tắt trong cấu hình.',
        'mail_transport_not_live' => 'Chờ cấu hình dịch vụ email thật; mail log không gửi tới khách.',
        'recipient_unverified' => 'Chờ khách xác minh địa chỉ email hợp lệ.',
        'queue_unavailable' => 'Hàng chờ tạm gián đoạn; hệ thống sẽ thử lại.',
        'delivery_failed' => 'Dịch vụ email chưa nhận thư; hệ thống sẽ thử lại.',
        'demo_suppressed' => 'Không gửi email cho đơn mô phỏng.',
        'milestone_no_longer_current' => 'Trạng thái đơn đã thay đổi; bỏ qua thông báo cũ.',
        'recipient_changed' => 'Người nhận đã thay đổi; không gửi thông tin tới địa chỉ cũ.',
        'order_missing' => 'Đơn hàng không còn tồn tại.',
    ];
@endphp
<section class="admin-card mb-4" aria-labelledby="order-emails-heading">
    <div class="d-flex justify-content-between align-items-start flex-wrap mb-3" style="gap:12px"><div><span class="studio-form-kicker">CHĂM SÓC SAU MUA</span><h2 id="order-emails-heading" class="h5 mt-2 mb-1">Email thông báo đơn hàng</h2><p class="small text-muted mb-0">Thông báo được gửi tự động qua hàng chờ. Trạng thái đã gửi nghĩa là dịch vụ email đã nhận thư.</p></div></div>
    @if($order->emails->isEmpty())
        <p class="text-muted mb-0">{{ $order->is_demo ? 'Đơn mô phỏng không gửi email đến khách.' : 'Chưa có thông báo. Email được ghi nhận cho các mốc đặt hàng, thanh toán và giao hàng mới sau khi tính năng được bật.' }}</p>
    @else
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Thông báo</th><th>Người nhận</th><th>Trạng thái</th><th>Cập nhật</th></tr></thead><tbody>
        @foreach($order->emails as $orderEmail)<tr><td>{{ $emailLabels[$orderEmail->type] ?? 'Cập nhật đơn hàng' }}</td><td style="overflow-wrap:anywhere">{{ $orderEmail->recipient }}</td><td><span class="badge badge-{{ $orderEmail->status === 'sent' ? 'success' : ($orderEmail->status === 'skipped' ? 'secondary' : 'warning') }}">{{ $orderEmail->status === 'sent' ? 'Đã gửi' : ($orderEmail->status === 'skipped' ? 'Đã bỏ qua' : 'Đang chờ') }}</span>@if($orderEmail->last_error)<small class="d-block text-muted mt-2">{{ $emailReasons[$orderEmail->last_error] ?? 'Đang chờ xử lý.' }}</small>@endif</td><td class="small text-muted">{{ ($orderEmail->sent_at ?? $orderEmail->skipped_at ?? $orderEmail->updated_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}<small class="d-block">{{ $orderEmail->attempts }} lần thử gửi</small></td></tr>@endforeach
        </tbody></table></div>
    @endif
</section>
