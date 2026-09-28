@php
    $labels = ['pending'=>'Chờ xử lý','confirmed'=>'Đã xác nhận','paid'=>'Đã thanh toán','paid_momo'=>'Đã thanh toán MoMo',
        'cod_ordered'=>'Chờ thu COD','cod_paid'=>'Đã thu COD','completed'=>'Hoàn tất','cancelled'=>'Đã hủy',
        'not_shipped'=>'Chưa giao','processing'=>'Đang xử lý','ready_to_pick'=>'Chờ lấy hàng','picking'=>'Đang lấy hàng',
        'picked'=>'Đã lấy hàng','storing'=>'Đang lưu kho','transporting'=>'Đang trung chuyển','sorting'=>'Đang phân loại',
        'delivering'=>'Đang giao','delivered'=>'Đã giao','return'=>'Chờ hoàn hàng','returning'=>'Đang hoàn hàng',
        'returned'=>'Đã hoàn hàng','return_transporting'=>'Đang chuyển hoàn','return_sorting'=>'Đang phân loại hoàn'];
@endphp
<section style="background:#fff;border:1px solid #e7d8c5;border-radius:16px;padding:24px;margin:20px 0">
    <h2 style="font-size:20px;margin-bottom:12px">Lịch sử xử lý đơn hàng</h2>
    @if($order->status === 'pending' && $order->payment_expires_at)
        <p>Hạn thanh toán: <strong>{{ $order->payment_expires_at->format('d/m/Y H:i') }}</strong>. Đơn online chưa thanh toán sẽ tự hủy và hoàn kho khi hết hạn (cần tiến trình lịch chạy).</p>
    @endif
    @if($order->is_demo)<p style="color:#915b12;font-weight:bold">DEMO LOCAL — thanh toán và giao hàng mô phỏng, không thu tiền thật.</p>@endif
    @if($order->inventory_status === 'legacy' && auth()->user()?->role === 'admin')
        <p>Đơn cũ: chưa có chứng từ trừ kho. Hệ thống không tự cộng trả tồn kho; cần đối soát thủ công.</p>
    @endif
    <ol style="border-left:2px solid #c99c65;padding-left:24px;margin-left:8px">
        @forelse($order->events as $event)
            <li style="padding:8px 0">
                <strong>{{ $labels[$event->to_status] ?? $event->to_status }}</strong>
                @if($event->to_shipping) · {{ $labels[$event->to_shipping] ?? $event->to_shipping }} @endif
                <div style="font-size:13px;color:#666">{{ $event->created_at->format('d/m/Y H:i:s') }} · {{ $event->actor_id ? 'Thao tác người dùng' : 'Hệ thống' }}</div>
            </li>
        @empty
            <li>Đơn được tạo trước khi bật lịch sử trạng thái. Không có lịch sử giả được bổ sung.</li>
        @endforelse
    </ol>
</section>
