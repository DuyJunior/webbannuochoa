@php
    $orderStatusLabels = ['pending' => 'Chờ xử lý', 'confirmed' => 'Đã xác nhận', 'paid' => 'Đã thanh toán', 'paid_momo' => 'Đã thanh toán MoMo', 'cod_ordered' => 'Chờ thu COD', 'cod_paid' => 'Đã thu COD', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'];
    $shippingStatusLabels = ['pending' => 'Chờ tạo vận đơn', 'not_shipped' => 'Chưa giao', 'processing' => 'Đang tạo vận đơn', 'ready_to_pick' => 'Chờ lấy hàng', 'picking' => 'Đang lấy hàng', 'picked' => 'Đã lấy hàng', 'storing' => 'Đang lưu kho', 'transporting' => 'Đang trung chuyển', 'sorting' => 'Đang phân loại', 'delivering' => 'Đang giao', 'delivered' => 'Đã giao', 'return' => 'Chờ hoàn hàng', 'returning' => 'Đang hoàn hàng', 'returned' => 'Đã hoàn hàng', 'return_transporting' => 'Đang chuyển hoàn', 'return_sorting' => 'Đang phân loại hoàn', 'cancelled' => 'Đã hủy giao hàng'];
@endphp
<div class="admin-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px">
        <h3 class="finance-chart-title mb-0">{{ $canReconcile ? 'Giao dịch và đối soát COD' : 'Đơn hàng theo bộ lọc' }}</h3>
        <span class="finance-muted">{{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} / {{ number_format($orders->total()) }} đơn</span>
    </div>
    <div class="table-responsive">
        <table class="table table-admin finance-table mb-0 {{ $canReconcile ? 'finance-reconcile-table' : '' }}">
            <thead><tr><th scope="col">Đơn hàng</th><th scope="col">Khách hàng</th><th scope="col">Giá trị đơn</th><th scope="col">Phương thức</th><th scope="col">Thanh toán</th><th scope="col">Ngày thu tiền</th>@if($canReconcile)<th scope="col">Đối soát thủ công</th>@endif</tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->id) }}"><strong>#{{ $order->id }}</strong></a>
                            <div class="finance-muted">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                            <div class="finance-muted">{{ $orderStatusLabels[$order->status] ?? $order->status }} · {{ $shippingStatusLabels[$order->shipping_status] ?? $order->shipping_status ?? 'Chưa giao' }}</div>
                            @if($order->is_demo)<span class="finance-mode demo">DEMO</span>@endif
                        </td>
                        <td>{{ $order->name ?: '—' }}<div class="finance-muted">{{ $order->phone ?: '—' }}</div></td>
                        <td class="finance-amount">{{ number_format($order->total_price, 0, ',', '.') }} ₫</td>
                        <td>{{ $gatewayLabels[$order->gateway] ?? 'Chưa xác định' }}<div class="finance-muted">{{ $order->payment_id ? 'GD #'.$order->payment_id : 'Dữ liệu cũ' }}</div></td>
                        <td><span class="finance-status {{ array_key_exists($order->payment_status, $paymentLabels) ? $order->payment_status : '' }}">{{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}</span></td>
                        <td class="finance-muted">{{ $order->payment_paid_at ? \Carbon\Carbon::parse($order->payment_paid_at)->format('d/m/Y H:i') : 'Chưa ghi nhận' }}</td>
                        @if($canReconcile)
                            @php
                                $allowed = array_values(array_filter(\App\Services\FinancePaymentPolicy::allowedTransitions($order, $order->payment_status, $order->gateway), fn ($status) => $status !== $order->payment_status));
                                $events = $auditEvents->get($order->id, collect());
                                $hasOldInput = (string) old('_finance_order') === (string) $order->id;
                            @endphp
                            <td>
                                @if(count($allowed))
                                    <form method="POST" action="{{ route('admin.finance.update-status', $order->id) }}" class="finance-reconcile" data-confirm="Ghi nhận đối soát COD cho đơn #{{ $order->id }} với giá trị {{ number_format($order->total_price, 0, ',', '.') }} ₫? Hãy kiểm tra khoản thu hoặc chứng từ hoàn tiền. Thao tác ghi nhận trạng thái và lịch sử, không chuyển tiền.">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="_finance_order" value="{{ $order->id }}">
                                        <input type="hidden" name="mode" value="{{ $filters['mode'] }}">
                                        <input type="hidden" name="current_payment_id" value="{{ $order->payment_id ?? 0 }}">
                                        <input type="hidden" name="current_payment_status" value="{{ $order->payment_status }}">
                                        <input type="hidden" name="current_order_status" value="{{ $order->status }}">
                                        <label for="finance-next-{{ $order->id }}" class="mt-0">Chuyển trạng thái COD</label>
                                        <select id="finance-next-{{ $order->id }}" name="payment_status" class="form-control" required>
                                            <option value="">Chọn trạng thái…</option>
                                            @foreach($allowed as $status)<option value="{{ $status }}" @selected($hasOldInput && old('payment_status') === $status)>{{ $paymentLabels[$status] }}</option>@endforeach
                                        </select>
                                        @if(in_array('refunded', $allowed, true))
                                            <label for="finance-reference-{{ $order->id }}">Mã chứng từ hoàn tiền{{ $order->is_demo ? ' (tùy chọn)' : ' (bắt buộc)' }}</label>
                                            <input id="finance-reference-{{ $order->id }}" name="manual_refund_reference" class="form-control" value="{{ $hasOldInput ? old('manual_refund_reference') : '' }}" minlength="3" maxlength="120" @required(! $order->is_demo) placeholder="Mã biên nhận / chuyển khoản">
                                            <div class="finance-muted mt-1">Ghi nhận đã hoàn bên ngoài; hệ thống không chuyển tiền.</div>
                                        @endif
                                        <button type="submit" class="btn btn-outline-pink">Lưu đối soát</button>
                                    </form>
                                @else
                                    <span class="finance-muted">{{ match($order->gateway) { 'cod' => 'Không có chuyển trạng thái hợp lệ.', 'momo' => 'Trạng thái cập nhật từ cổng MoMo.', 'demo' => 'Giao dịch mô phỏng, không thu tiền thật.', default => 'Chưa xác định phương thức; cần kiểm tra đơn hàng.' } }}</span>
                                @endif
                                @if($events->isNotEmpty())
                                    <details class="finance-audit">
                                        <summary>Lịch sử đối soát ({{ $events->count() }})</summary>
                                        @foreach($events as $event)
                                            <div class="finance-audit-entry">
                                                <strong>{{ $paymentLabels[$event->from_status] ?? $event->from_status }} → {{ $paymentLabels[$event->to_status] ?? $event->to_status }}</strong><br>
                                                {{ $event->actor_name }} · {{ $event->created_at->format('d/m/Y H:i') }} · GD #{{ $event->payment_id }}
                                                @if($event->manual_refund_reference)<br>Chứng từ: {{ $event->manual_refund_reference }}@endif
                                            </div>
                                        @endforeach
                                    </details>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canReconcile ? 7 : 6 }}" class="finance-empty"><i class="fa-solid fa-receipt fa-2x mb-3" aria-hidden="true"></i><br><strong>Không có đơn phù hợp</strong><p class="mt-2 mb-3">Thử khoảng ngày khác hoặc bỏ điều kiện lọc để xem thêm giao dịch.</p><a class="btn btn-outline-secondary btn-sm" href="{{ route($canReconcile ? 'admin.finance.transactions' : 'admin.finance.index', ['mode' => $filters['mode']]) }}">Xóa bộ lọc</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="finance-table-footer"><span class="finance-muted">CSV chứa toàn bộ kết quả đã lọc, không chứa thông tin khách hàng.</span>{{ $orders->links('pagination::bootstrap-4') }}</div>
</div>
