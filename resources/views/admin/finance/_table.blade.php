@php
    $orderStatusLabels = ['pending' => __('Chờ xử lý'), 'confirmed' => __('Đã xác nhận'), 'paid' => __('Đã thanh toán'), 'paid_momo' => __('Đã thanh toán MoMo'), 'cod_ordered' => __('Chờ thu COD'), 'cod_paid' => __('Đã thu COD'), 'completed' => __('Hoàn thành'), 'cancelled' => __('Đã hủy')];
    $shippingStatusLabels = ['pending' => __('Chờ tạo vận đơn'), 'not_shipped' => __('Chưa giao'), 'processing' => __('Đang tạo vận đơn'), 'ready_to_pick' => __('Chờ lấy hàng'), 'picking' => __('Đang lấy hàng'), 'picked' => __('Đã lấy hàng'), 'storing' => __('Đang lưu kho'), 'transporting' => __('Đang trung chuyển'), 'sorting' => __('Đang phân loại'), 'delivering' => __('Đang giao'), 'delivered' => __('Đã giao'), 'return' => __('Chờ hoàn hàng'), 'returning' => __('Đang hoàn hàng'), 'returned' => __('Đã hoàn hàng'), 'return_transporting' => __('Đang chuyển hoàn'), 'return_sorting' => __('Đang phân loại hoàn'), 'cancelled' => __('Đã hủy giao hàng')];
@endphp
<div class="admin-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3" style="gap:10px">
        <h3 class="finance-chart-title mb-0">{{ $canReconcile ? __('Giao dịch và đối soát') : __('Đơn hàng theo bộ lọc') }}</h3>
        <span class="finance-muted">{{ $orders->firstItem() ?? 0 }}–{{ $orders->lastItem() ?? 0 }} / {{ number_format($orders->total()) }} {{ __('đơn') }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-admin finance-table mb-0 {{ $canReconcile ? 'finance-reconcile-table' : '' }}">
            <thead><tr><th scope="col">{{ __('Đơn hàng') }}</th><th scope="col">{{ __('Khách hàng') }}</th><th scope="col">{{ __('Giá trị đơn') }}</th><th scope="col">{{ __('Phương thức') }}</th><th scope="col">{{ __('Thanh toán') }}</th><th scope="col">{{ __('Ngày thu tiền') }}</th>@if($canReconcile)<th scope="col">{{ __('Đối soát thủ công') }}</th>@endif</tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $order->id) }}"><strong>#{{ $order->id }}</strong></a>
                            <div class="finance-muted">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                            <div class="finance-muted">{{ __($orderStatusLabels[$order->status] ?? $order->status) }} · {{ __($shippingStatusLabels[$order->shipping_status] ?? $order->shipping_status ?? __('Chưa giao')) }}</div>
                            @if($order->is_demo)<span class="finance-mode demo">DEMO</span>@endif
                        </td>
                        <td>{{ $order->name ?: '—' }}<div class="finance-muted">{{ $order->phone ?: '—' }}</div></td>
                        <td class="finance-amount">{{ number_format($order->total_price, 0, ',', '.') }} ₫</td>
                        <td>{{ __($gatewayLabels[$order->gateway] ?? __('Chưa xác định')) }}<div class="finance-muted">{{ $order->payment_id ? 'GD #'.$order->payment_id : __('Dữ liệu cũ') }}</div></td>
                        <td><span class="finance-status {{ array_key_exists($order->payment_status, $paymentLabels) ? $order->payment_status : '' }}">{{ __($paymentLabels[$order->payment_status] ?? $order->payment_status) }}</span></td>
                        <td class="finance-muted">{{ $order->payment_paid_at ? \Carbon\Carbon::parse($order->payment_paid_at)->format('d/m/Y H:i') : __('Chưa ghi nhận') }}</td>
                        @if($canReconcile)
                            @php
                                $allowed = array_values(array_filter(\App\Services\FinancePaymentPolicy::allowedTransitions($order, $order->payment_status, $order->gateway), fn ($status) => $status !== $order->payment_status));
                                $events = $auditEvents->get($order->id, collect());
                                $hasOldInput = (string) old('_finance_order') === (string) $order->id;
                            @endphp
                            <td>
                                @if(count($allowed))
                                    <form method="POST" action="{{ route('admin.finance.update-status', $order->id) }}" class="finance-reconcile" data-confirm="Ghi nhận đối soát cho đơn #{{ $order->id }} với giá trị {{ number_format($order->total_price, 0, ',', '.') }} ₫? Hãy kiểm tra khoản thu hoặc chứng từ hoàn tiền. Thao tác ghi nhận trạng thái và lịch sử, không chuyển tiền.">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="_finance_order" value="{{ $order->id }}">
                                        <input type="hidden" name="mode" value="{{ $filters['mode'] }}">
                                        <input type="hidden" name="current_payment_id" value="{{ $order->payment_id ?? 0 }}">
                                        <input type="hidden" name="current_payment_status" value="{{ $order->payment_status }}">
                                        <input type="hidden" name="current_order_status" value="{{ $order->status }}">
                                        <label for="finance-next-{{ $order->id }}" class="mt-0">{{ __('Chuyển trạng thái thanh toán') }}</label>
                                        <select id="finance-next-{{ $order->id }}" name="payment_status" class="form-control" required>
                                            <option value="">{{ __('Chọn trạng thái…') }}</option>
                                            @foreach($allowed as $status)<option value="{{ $status }}" @selected($hasOldInput && old('payment_status') === $status)>{{ __($paymentLabels[$status]) }}</option>@endforeach
                                        </select>
                                        @if(in_array('refunded', $allowed, true))
                                            <label for="finance-reference-{{ $order->id }}">{{ __('Mã chứng từ hoàn tiền') }}{{ $order->is_demo ? __(' (tùy chọn)') : __(' (bắt buộc)') }}</label>
                                            <input id="finance-reference-{{ $order->id }}" name="manual_refund_reference" class="form-control" value="{{ $hasOldInput ? old('manual_refund_reference') : '' }}" minlength="3" maxlength="120" @required(! $order->is_demo) placeholder="{{ __('Mã biên nhận / chuyển khoản') }}">
                                            <div class="finance-muted mt-1">{{ __('Ghi nhận đã hoàn bên ngoài; hệ thống không chuyển tiền.') }}</div>
                                        @endif
                                        <button type="submit" class="btn btn-outline-pink">{{ __('Lưu đối soát') }}</button>
                                    </form>
                                @else
                                    <span class="finance-muted">{{ match($order->gateway) { 'cod' => __('Không có chuyển trạng thái hợp lệ.'), 'sepay' => __('Trạng thái cập nhật từ webhook SePay.'), 'momo' => __('Trạng thái cập nhật từ cổng MoMo.'), 'demo' => __('Giao dịch mô phỏng, không thu tiền thật.'), default => __('Chưa xác định phương thức; cần kiểm tra đơn hàng.') } }}</span>
                                @endif
                                @if($events->isNotEmpty())
                                    <details class="finance-audit">
                                        <summary>{{ __('Lịch sử đối soát (') }}{{ $events->count() }})</summary>
                                        @foreach($events as $event)
                                            <div class="finance-audit-entry">
                                                <strong>{{ __($paymentLabels[$event->from_status] ?? $event->from_status) }} → {{ __($paymentLabels[$event->to_status] ?? $event->to_status) }}</strong><br>
                                                {{ $event->actor_name }} · {{ $event->created_at->format('d/m/Y H:i') }} · GD #{{ $event->payment_id }}
                                                @if($event->manual_refund_reference)<br>{{ __('Chứng từ:') }} {{ $event->manual_refund_reference }}@endif
                                            </div>
                                        @endforeach
                                    </details>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canReconcile ? 7 : 6 }}" class="finance-empty"><i class="fa-solid fa-receipt fa-2x mb-3" aria-hidden="true"></i><br><strong>{{ __('Không có đơn phù hợp') }}</strong><p class="mt-2 mb-3">{{ __('Thử khoảng ngày khác hoặc bỏ điều kiện lọc để xem thêm giao dịch.') }}</p><a class="btn btn-outline-secondary btn-sm" href="{{ route($canReconcile ? 'admin.finance.transactions' : 'admin.finance.index', ['mode' => $filters['mode']]) }}">{{ __('Xóa bộ lọc') }}</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="finance-table-footer"><span class="finance-muted">{{ __('CSV chứa toàn bộ kết quả đã lọc, không chứa thông tin khách hàng.') }}</span>{{ $orders->links('pagination::bootstrap-4') }}</div>
</div>
