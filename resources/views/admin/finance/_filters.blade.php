
<div class="admin-card">
    <form method="GET" action="{{ route($filterRoute) }}" class="finance-filters">
        <div>
            <label for="finance-search">{{ __('Mã đơn, khách hàng hoặc điện thoại') }}</label>
            <input class="form-control" id="finance-search" type="search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="{{ __('Ví dụ: #DH00123') }}">
        </div>
        <div>
            <label for="finance-date-from">{{ __('Từ ngày tạo đơn') }}</label>
            <input class="form-control" id="finance-date-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div>
            <label for="finance-date-to">{{ __('Đến ngày tạo đơn') }}</label>
            <input class="form-control" id="finance-date-to" type="date" name="date_to" min="{{ $filters['date_from'] ?? '' }}" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div>
            <label for="finance-mode">{{ __('Nguồn dữ liệu') }}</label>
            <select class="form-control" id="finance-mode" name="mode">
                <option value="real" @selected($filters['mode'] === 'real')>{{ __('Giao dịch thực') }}</option>
                <option value="demo" @selected($filters['mode'] === 'demo')>{{ __('Dữ liệu mô phỏng (DEMO)') }}</option>
            </select>
        </div>
        <div>
            <label for="finance-min">{{ __('Giá trị đơn tối thiểu (VND)') }}</label>
            <input class="form-control" id="finance-min" type="number" name="min_amount" min="0" step="0.01" value="{{ $filters['min_amount'] ?? '' }}" placeholder="0">
        </div>
        <div>
            <label for="finance-max">{{ __('Giá trị đơn tối đa (VND)') }}</label>
            <input class="form-control" id="finance-max" type="number" name="max_amount" min="0" step="0.01" value="{{ $filters['max_amount'] ?? '' }}" placeholder="{{ __('Không giới hạn') }}">
        </div>
        <div>
            <label for="finance-gateway">{{ __('Phương thức thanh toán') }}</label>
            <select class="form-control" id="finance-gateway" name="gateway">
                <option value="">{{ __('Tất cả phương thức') }}</option>
                @foreach($gatewayLabels as $value => $label)
                    @if($value !== 'demo' || $filters['mode'] === 'demo')
                        <option value="{{ $value }}" @selected(($filters['gateway'] ?? '') === $value)>{{ __($label) }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div>
            <label for="finance-payment-status">{{ __('Trạng thái thanh toán') }}</label>
            <select class="form-control" id="finance-payment-status" name="payment_status">
                <option value="">{{ __('Tất cả trạng thái') }}</option>
                @foreach($paymentLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="finance-sort">{{ __('Sắp xếp') }}</label>
            <select class="form-control" id="finance-sort" name="sort">
                @foreach(['newest' => __('Mới nhất'), 'oldest' => __('Cũ nhất'), 'amount_desc' => __('Giá trị giảm dần'), 'amount_asc' => __('Giá trị tăng dần')] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
        <div class="finance-filter-actions">
            <button type="submit" class="btn btn-pink"><i class="fa-solid fa-filter" aria-hidden="true"></i> {{ __('Áp dụng bộ lọc') }}</button>
            <a class="btn btn-outline-secondary" href="{{ route($filterRoute, ['mode' => $filters['mode']]) }}">{{ __('Xóa bộ lọc') }}</a>
            <span class="finance-muted">{{ number_format($totals->order_count) }} {{ __('đơn khớp bộ lọc · 15 đơn/trang') }}</span>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('finance-date-from').addEventListener('change', function () {
        document.getElementById('finance-date-to').min = this.value;
    });
    document.getElementById('finance-min').addEventListener('input', function () {
        document.getElementById('finance-max').min = this.value || '0';
    });
});
</script>
