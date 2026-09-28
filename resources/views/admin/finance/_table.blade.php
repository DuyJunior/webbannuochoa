<div class="admin-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px">
        <h3 class="finance-chart-title mb-0">{{ $canReconcile ? 'Giao dịch và đối soát COD' : 'Đơn hàng theo bộ lọc' }}</h3>
        <span class="finance-muted">{{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} / {{ number_format($orders->total()) }} đơn</span>
    </div>
    <div class="table-responsive">
        <table class="table table-admin finance-table mb-0">
            <thead><tr><th>Đơn hàng</th><th>Khách hàng</th><th>Giá trị đơn</th><th>Phương thức</th><th>Thanh toán</th><th>Ngày thu tiền</th>@if($canReconcile)<th>Đối soát thủ công</th>@endif</tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->id) }}"><strong>#{{ $order->id }}</strong></a>
                            <div class="finance-muted">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                            <div class="finance-muted">{{ $order->status }} · {{ $order->shipping_status ?? 'Chưa giao' }}</div>
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
                            @endphp
                            <td>
                                @if(count($allowed))
                                    <form method="POST" action="{{ route('admin.finance.update-status', $order->id) }}" class="finance-reconcile">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="mode" value="{{ $filters['mode'] }}">
                                        <input type="hidden" name="current_payment_id" value="{{ $order->payment_id ?? 0 }}">
                                        <input type="hidden" name="current_payment_status" value="{{ $order->payment_status }}">
                                        <input type="hidden" name="current_order_status" value="{{ $order->status }}">
                                        <label for="finance-next-{{ $order->id }}" class="mt-0">Chuyển trạng thái COD</label>
                                        <select id="finance-next-{{ $order->id }}" name="payment_status" class="form-control" required>
                                            <option value="">Chọn trạng thái…</option>
                                            @foreach($allowed as $status)<option value="{{ $status }}">{{ $paymentLabels[$status] }}</option>@endforeach
                                        </select>
                                        @if(in_array('refunded', $allowed, true))
                                            <label for="finance-reference-{{ $order->id }}">Mã chứng từ hoàn tiền{{ $order->is_demo ? ' (tùy chọn)' : ' (bắt buộc)' }}</label>
                                            <input id="finance-reference-{{ $order->id }}" name="manual_refund_reference" class="form-control" minlength="3" maxlength="120" @required(! $order->is_demo) placeholder="Mã biên nhận / chuyển khoản">
                                            <div class="finance-muted mt-1">Ghi nhận đã hoàn bên ngoài; hệ thống không chuyển tiền.</div>
                                        @endif
                                        <button type="submit" class="btn btn-outline-pink">Lưu đối soát</button>
                                    </form>
                                @else
                                    <span class="finance-muted">{{ $order->gateway === 'cod' ? 'Không có chuyển trạng thái hợp lệ.' : 'Đối soát qua cổng thanh toán.' }}</span>
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
                    <tr><td colspan="{{ $canReconcile ? 7 : 6 }}" class="finance-empty"><i class="fa-solid fa-receipt fa-2x mb-3" aria-hidden="true"></i><br>Không có đơn phù hợp với bộ lọc. Hãy thử đổi khoảng ngày hoặc xóa bộ lọc.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="finance-table-footer"><span class="finance-muted">CSV chứa toàn bộ kết quả đã lọc, không chứa thông tin khách hàng.</span>{{ $orders->links('pagination::bootstrap-4') }}</div>
</div>
