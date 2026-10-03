@php
    $labels = ['pending'=>__('Chờ xử lý'),'confirmed'=>__('Đã xác nhận'),'paid'=>__('Đã thanh toán'),'paid_momo'=>__('Đã thanh toán MoMo'),
        'cod_ordered'=>__('Chờ thu COD'),'cod_paid'=>__('Đã thu COD'),'completed'=>__('Hoàn tất'),'cancelled'=>__('Đã hủy'),
        'not_shipped'=>__('Chưa giao'),'processing'=>__('Đang xử lý'),'ready_to_pick'=>__('Chờ lấy hàng'),'picking'=>__('Đang lấy hàng'),
        'picked'=>__('Đã lấy hàng'),'storing'=>__('Đang lưu kho'),'transporting'=>__('Đang trung chuyển'),'sorting'=>__('Đang phân loại'),
        'delivering'=>__('Đang giao'),'delivered'=>__('Đã giao'),'return'=>__('Chờ hoàn hàng'),'returning'=>__('Đang hoàn hàng'),
        'returned'=>__('Đã hoàn hàng'),'return_transporting'=>__('Đang chuyển hoàn'),'return_sorting'=>__('Đang phân loại hoàn')];
@endphp
<section class="order-history-panel" style="background:#fff;border:1px solid #e7d8c5;border-radius:16px;padding:24px;margin:20px 0">
    <h2 style="font-size:20px;margin-bottom:12px">{{ __('Lịch sử xử lý đơn hàng') }}</h2>
    @if($order->status === 'pending' && $order->payment_expires_at)
        <p>{{ __('Hạn thanh toán:') }} <strong>{{ $order->payment_expires_at->format('d/m/Y H:i') }}</strong>{{ __('. Đơn online chưa thanh toán sẽ tự hủy và hoàn kho khi hết hạn (cần tiến trình lịch chạy).') }}</p>
    @endif
    @if($order->is_demo)<p style="color:#915b12;font-weight:bold">{{ __('DEMO LOCAL — thanh toán và giao hàng mô phỏng, không thu tiền thật.') }}</p>@endif
    @if($order->inventory_status === 'legacy' && auth()->user()?->role === 'admin')
        <p>{{ __('Đơn cũ: chưa có chứng từ trừ kho. Hệ thống không tự cộng trả tồn kho; cần đối soát thủ công.') }}</p>
    @endif
    <ol style="border-left:2px solid #c99c65;padding-left:24px;margin-left:8px">
        @forelse($order->events as $event)
            <li style="padding:8px 0">
                <strong>{{ $labels[$event->to_status] ?? $event->to_status }}</strong>
                @if($event->to_shipping) · {{ $labels[$event->to_shipping] ?? $event->to_shipping }} @endif
                <div style="font-size:13px;color:#666">{{ $event->created_at->format('d/m/Y H:i:s') }} · {{ $event->actor_id ? __('Thao tác người dùng') : __('Hệ thống') }}</div>
            </li>
        @empty
            <li>{{ __('Đơn được tạo trước khi bật lịch sử trạng thái. Không có lịch sử giả được bổ sung.') }}</li>
        @endforelse
    </ol>
</section>
