@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Vui lòng kiểm tra lại:</strong>
        <ul class="mb-0 mt-2 pl-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
<div class="admin-card">
    <form method="GET" action="{{ route($filterRoute) }}" class="finance-filters">
        <div>
            <label for="finance-search">Mã đơn, khách hàng hoặc điện thoại</label>
            <input class="form-control" id="finance-search" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Ví dụ: #123">
        </div>
        <div>
            <label for="finance-date-from">Từ ngày tạo đơn</label>
            <input class="form-control" id="finance-date-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div>
            <label for="finance-date-to">Đến ngày tạo đơn</label>
            <input class="form-control" id="finance-date-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div>
            <label for="finance-mode">Nguồn dữ liệu</label>
            <select class="form-control" id="finance-mode" name="mode">
                <option value="real" @selected($filters['mode'] === 'real')>Giao dịch thực</option>
                <option value="demo" @selected($filters['mode'] === 'demo')>Dữ liệu mô phỏng (DEMO)</option>
            </select>
        </div>
        <div>
            <label for="finance-min">Giá trị đơn tối thiểu (VND)</label>
            <input class="form-control" id="finance-min" type="number" name="min_amount" min="0" step="0.01" value="{{ $filters['min_amount'] ?? '' }}" placeholder="0">
        </div>
        <div>
            <label for="finance-max">Giá trị đơn tối đa (VND)</label>
            <input class="form-control" id="finance-max" type="number" name="max_amount" min="0" step="0.01" value="{{ $filters['max_amount'] ?? '' }}" placeholder="Không giới hạn">
        </div>
        <div>
            <label for="finance-gateway">Phương thức thanh toán</label>
            <select class="form-control" id="finance-gateway" name="gateway">
                <option value="">Tất cả phương thức</option>
                @foreach($gatewayLabels as $value => $label)
                    @if($value !== 'demo' || $filters['mode'] === 'demo')
                        <option value="{{ $value }}" @selected(($filters['gateway'] ?? '') === $value)>{{ $label }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div>
            <label for="finance-payment-status">Trạng thái thanh toán</label>
            <select class="form-control" id="finance-payment-status" name="payment_status">
                <option value="">Tất cả trạng thái</option>
                @foreach($paymentLabels as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['payment_status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="finance-sort">Sắp xếp</label>
            <select class="form-control" id="finance-sort" name="sort">
                @foreach(['newest' => 'Mới nhất', 'oldest' => 'Cũ nhất', 'amount_desc' => 'Giá trị giảm dần', 'amount_asc' => 'Giá trị tăng dần'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="finance-filter-actions">
            <button type="submit" class="btn btn-pink"><i class="fa-solid fa-filter" aria-hidden="true"></i> Áp dụng bộ lọc</button>
            <a class="btn btn-outline-secondary" href="{{ route($filterRoute, ['mode' => $filters['mode']]) }}">Xóa bộ lọc</a>
            <span class="finance-muted">{{ number_format($totals->order_count) }} đơn khớp bộ lọc · 15 đơn/trang</span>
        </div>
    </form>
</div>
