<div class="finance-metrics">
    <div class="finance-metric">
        <div class="finance-metric-label">{{ __('Số đơn trong bộ lọc') }}</div>
        <div class="finance-metric-value">{{ number_format($totals->order_count) }}</div>
        <div class="finance-metric-note">{{ __('Mỗi đơn được tính một lần') }}</div>
    </div>
    <div class="finance-metric">
        <div class="finance-metric-label">{{ __('Tổng giá trị đơn') }}</div>
        <div class="finance-metric-value">{{ number_format($totals->total_amount, 0, ',', '.') }} ₫</div>
        <div class="finance-metric-note">{{ __('Bao gồm mọi trạng thái đã lọc') }}</div>
    </div>
    <div class="finance-metric">
        <div class="finance-metric-label">{{ __('Giá trị đơn đã thanh toán') }}</div>
        <div class="finance-metric-value">{{ number_format($statusTotals->get('paid')?->total_amount ?? 0, 0, ',', '.') }} ₫</div>
        <div class="finance-metric-note">{{ number_format($statusTotals->get('paid')?->order_count ?? 0) }} {{ __('đơn · trạng thái hiện tại') }}</div>
    </div>
    <div class="finance-metric">
        <div class="finance-metric-label">{{ __('Giá trị đơn chờ hoàn tiền') }}</div>
        <div class="finance-metric-value">{{ number_format($statusTotals->get('refund_pending')?->total_amount ?? 0, 0, ',', '.') }} ₫</div>
        <div class="finance-metric-note">{{ number_format($statusTotals->get('refund_pending')?->order_count ?? 0) }} {{ __('đơn cần đối soát') }}</div>
    </div>
</div>
