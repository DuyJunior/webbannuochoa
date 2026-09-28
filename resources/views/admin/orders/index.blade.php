@extends('layouts.admin')

@section('title', 'Quản lý đơn hàng · Lab 8')
@section('page_title', 'Quản lý đơn hàng')

@section('content')
<div class="admin-orders-container">
    {{-- 1. BỘ LỌC TABS THEO QUY CHUẨN LAB 8 (PDF Trang 1, 6) --}}
    <div class="order-tabs-wrapper mb-3">
        <div class="nav-tabs-scroll">
            @foreach($tabs as $tabKey => $tabItem)
                @php
                    $isActive = ($activeTab === $tabKey);
                    $urlParams = request()->except(['tab', 'page']);
                    if ($tabKey !== 'all') {
                        $urlParams['tab'] = $tabKey;
                    }
                    $tabUrl = route('admin.orders.index', $urlParams);
                @endphp
                <a href="{{ $tabUrl }}" class="order-tab-btn {{ $isActive ? 'active' : '' }} tab-{{ $tabItem['color'] }}">
                    <span>{{ $tabItem['label'] }}</span>
                    <span class="tab-badge {{ $isActive ? 'badge-active' : '' }}">{{ $tabItem['count'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- 2. BỘ LỌC TÌM KIẾM & BỘ LỌC NÂNG CAO (PDF Trang 2 - 3) --}}
    <div class="admin-card mb-4 filter-box">
        <form method="GET" action="{{ route('admin.orders.index') }}" id="orderFilterForm">
            @if(request('tab'))
                <input type="hidden" name="tab" value="{{ request('tab') }}">
            @endif

            <div class="row g-2 align-items-center">
                {{-- Ô tìm kiếm --}}
                <div class="col-lg-4 col-md-6 mb-2">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0 text-muted">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                        </div>
                        <input type="text" name="search" class="form-control border-left-0" 
                               placeholder="Mã đơn (#DH...), khách hàng, SĐT, tên nước hoa..." 
                               value="{{ request('search') }}">
                    </div>
                </div>

                {{-- Lọc thanh toán --}}
                <div class="col-lg-2 col-md-3 col-6 mb-2">
                    <select name="payment_status" class="form-control form-select">
                        <option value="">-- Tất cả thanh toán --</option>
                        @foreach($paymentLabels as $pKey => $pLabel)
                            <option value="{{ $pKey }}" {{ request('payment_status') === $pKey ? 'selected' : '' }}>
                                {{ $pLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Lọc cổng thanh toán --}}
                <div class="col-lg-2 col-md-3 col-6 mb-2">
                    <select name="gateway" class="form-control form-select">
                        <option value="">-- Cổng (COD / MoMo) --</option>
                        <option value="cod" {{ request('gateway') === 'cod' ? 'selected' : '' }}>Tiền mặt (COD)</option>
                        <option value="momo" {{ request('gateway') === 'momo' ? 'selected' : '' }}>Ví MoMo</option>
                    </select>
                </div>

                {{-- Ngày bắt đầu --}}
                <div class="col-lg-2 col-md-3 col-6 mb-2">
                    <input type="date" name="date_from" class="form-control" title="Từ ngày" value="{{ request('date_from') }}">
                </div>

                {{-- Ngày kết thúc --}}
                <div class="col-lg-2 col-md-3 col-6 mb-2">
                    <input type="date" name="date_to" class="form-control" title="Đến ngày" value="{{ request('date_to') }}">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2 pt-2 border-top">
                <div class="d-flex align-items-center gap-2">
                    <span class="small text-muted font-weight-bold">Hiển thị:</span>
                    <select name="per_page" class="form-control form-control-sm" style="width: 80px;" onchange="document.getElementById('orderFilterForm').submit()">
                        <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                    <span class="small text-muted ml-2">Sắp xếp:</span>
                    <select name="sort" class="form-control form-control-sm" style="width: 140px;" onchange="document.getElementById('orderFilterForm').submit()">
                        <option value="newest" {{ request('sort', 'newest') === 'newest' ? 'selected' : '' }}>Mới nhất</option>
                        <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Cũ nhất</option>
                        <option value="amount_desc" {{ request('sort') === 'amount_desc' ? 'selected' : '' }}>Giá trị cao nhất</option>
                        <option value="amount_asc" {{ request('sort') === 'amount_asc' ? 'selected' : '' }}>Giá trị thấp nhất</option>
                    </select>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    @if(request()->anyFilled(['search', 'payment_status', 'gateway', 'date_from', 'date_to', 'shipping_status']))
                        <a href="{{ route('admin.orders.index', request('tab') ? ['tab' => request('tab')] : []) }}" class="btn btn-light btn-sm text-danger mr-2">
                            <i class="fa-solid fa-xmark"></i> Xóa lọc
                        </a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm px-3" style="background:#db2777; border-color:#db2777;">
                        <i class="fa-solid fa-filter mr-1"></i> Áp dụng bộ lọc
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="fa-solid fa-file-export mr-1"></i> Xuất trang này
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- 3. THANH THAO TÁC HÀNG LOẠT (BULK ACTION BAR) - THEO YÊU CẦU NGƯỜI DÙNG --}}
    <form method="POST" action="{{ route('admin.orders.bulk_update') }}" id="bulkUpdateForm">
        @csrf
        <div id="bulkActionBar" class="bulk-action-bar alert shadow-sm" style="display: none;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-dark px-3 py-2" style="font-size: 0.9rem; border-radius: 20px;">
                        Đã chọn <strong id="selectedCountNumber">0</strong> đơn hàng
                    </span>
                    <button type="button" class="btn btn-link btn-sm text-secondary" id="btnDeselectAll">Bỏ chọn tất cả</button>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <label class="mb-0 small font-weight-bold text-dark">Chuyển trạng thái:</label>
                    <select name="bulk_shipping_status" id="bulkShippingStatus" class="form-control form-control-sm" style="width: 170px;">
                        <option value="">-- Trạng thái giao --</option>
                        <option value="pending">Chờ tạo vận đơn</option>
                        <option value="ready_to_pick">Chờ lấy hàng</option>
                        <option value="picking">Đang lấy hàng</option>
                        <option value="delivering">Đang giao hàng</option>
                        <option value="delivered">Giao thành công</option>
                        <option value="cancelled">Hủy đơn (Không giao)</option>
                    </select>

                    <select name="bulk_status" id="bulkOrderStatus" class="form-control form-control-sm" style="width: 170px;">
                        <option value="">-- Trạng thái đơn --</option>
                        <option value="pending">Chờ xử lý</option>
                        <option value="confirmed">Đã xác nhận</option>
                        <option value="paid">Đã thanh toán</option>
                        <option value="completed">Đã hoàn thành</option>
                        <option value="cancelled">Đã hủy đơn</option>
                    </select>

                    <button type="submit" class="btn btn-success btn-sm font-weight-bold px-3" onclick="return confirmBulkAction()">
                        <i class="fa-solid fa-bolt mr-1"></i> Cập nhật hàng loạt
                    </button>
                </div>
            </div>
        </div>

        {{-- 4. BẢNG DANH SÁCH ĐƠN HÀNG (PDF Trang 6) --}}
        <div class="admin-card p-0 overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover table-admin align-middle mb-0">
                    <thead class="bg-light text-muted" style="font-size: 0.8rem; letter-spacing: 0.3px; text-transform: uppercase;">
                        <tr>
                            {{-- Checkbox chọn tất cả --}}
                            <th width="40" class="text-center">
                                <input type="checkbox" id="selectAllOrders" title="Tích chọn tất cả đơn hàng trên trang này" style="width: 17px; height: 17px; cursor: pointer;">
                            </th>
                            <th width="120">Mã đơn hàng</th>
                            <th width="120">Ngày tạo</th>
                            <th>Sản phẩm</th>
                            <th width="120" class="text-right">Tổng tiền</th>
                            <th width="110" class="text-right">COD cần thu</th>
                            <th width="170">Khách hàng</th>
                            <th width="120">Mã vận đơn</th>
                            <th width="160">Trạng thái GHN</th>
                            <th width="90">Đơn vị</th>
                            <th width="80" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $isDelivering = in_array($order->shipping_status, ['delivering', 'picked', 'storing', 'transporting', 'sorting']);
                                $paymentBadgeClass = match($order->payment_status) {
                                    'paid' => 'badge-success',
                                    'refunded', 'refund_pending' => 'badge-warning',
                                    'failed', 'cancelled' => 'badge-danger',
                                    default => 'badge-secondary',
                                };
                            @endphp
                            <tr class="order-row-item" data-delivering="{{ $isDelivering ? '1' : '0' }}">
                                {{-- Checkbox chọn dòng này --}}
                                <td class="text-center">
                                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="order-checkbox" style="width: 17px; height: 17px; cursor: pointer;">
                                </td>

                                {{-- Mã đơn hàng & Badge thanh toán --}}
                                <td>
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="font-weight-bold text-dark text-decoration-none">
                                        #DH{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                    </a>
                                    <div class="mt-1">
                                        <span class="badge {{ $paymentBadgeClass }}" style="font-size: 0.68rem; letter-spacing: 0.3px;">
                                            {{ $paymentLabels[$order->payment_status] ?? strtoupper($order->status) }}
                                        </span>
                                    </div>
                                </td>

                                {{-- Ngày tạo đơn --}}
                                <td class="text-muted small">
                                    <div>{{ $order->created_at->format('d/m/Y') }}</div>
                                    <div style="font-size: 0.76rem;">{{ $order->created_at->format('H:i') }}</div>
                                </td>

                                {{-- Sản phẩm trong đơn --}}
                                <td>
                                    <div class="order-items-snippet">
                                        @foreach($order->items as $item)
                                            <div class="item-line small text-truncate" style="max-width: 260px;" title="{{ $item->product_name ?? $item->perfume?->name ?? 'Nước hoa' }}">
                                                <span class="text-dark font-weight-500">{{ $item->product_name ?? $item->perfume?->name ?? 'Sản phẩm' }}</span>
                                                <span class="text-muted">× {{ $item->quantity }}</span>
                                                @if($item->volume_ml)
                                                    <span class="text-primary" style="font-size: 0.72rem;">({{ $item->volume_ml }}ml)</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- Tổng tiền --}}
                                <td class="text-right font-weight-bold text-dark">
                                    {{ number_format($order->total_price, 0, ',', '.') }} đ
                                </td>

                                {{-- COD cần thu --}}
                                <td class="text-right text-muted font-weight-500">
                                    @if(in_array($order->payment_status, ['paid', 'paid_momo']))
                                        <span class="text-success">0 đ</span>
                                    @else
                                        {{ number_format($order->total_price, 0, ',', '.') }} đ
                                    @endif
                                </td>

                                {{-- Tên khách hàng & SĐT --}}
                                <td>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="font-weight-bold text-dark" style="font-size: 0.88rem;">{{ $order->name ?? $order->customer_name }}</span>
                                        @if($order->user_id)
                                            <button type="button" class="btn btn-sm btn-outline-success py-0 px-1 ml-1" style="font-size: 0.72rem; border-radius: 4px;" onclick="openChatWithUser({{ $order->user_id }}, '{{ addslashes($order->name ?? $order->customer_name) }}')" title="Nhắn tin cho khách hàng này">
                                                💬 Chat
                                            </button>
                                        @endif
                                    </div>
                                    <div class="small text-muted"><i class="fa-solid fa-phone mr-1"></i>{{ $order->phone }}</div>
                                </td>

                                {{-- Mã vận đơn GHN --}}
                                <td>
                                    @if($order->ghn_order_code)
                                        <span class="badge badge-light border text-primary font-weight-bold px-2 py-1" style="font-family: monospace;">
                                            {{ $order->ghn_order_code }}
                                        </span>
                                    @else
                                        <span class="text-muted small">Chưa tạo vận đơn</span>
                                    @endif
                                </td>

                                {{-- Trạng thái giao hàng (Badge màu tương ứng) --}}
                                <td>
                                    @php
                                        $shStatus = $order->shipping_status ?? 'pending';
                                        $dotColor = match($shStatus) {
                                            'delivered' => '#16a34a',
                                            'delivering', 'transporting', 'sorting', 'picked' => '#f59e0b',
                                            'ready_to_pick', 'picking' => '#06b6d4',
                                            'return', 'returning', 'returned' => '#ea580c',
                                            'cancelled' => '#dc2626',
                                            default => '#64748b'
                                        };
                                    @endphp
                                    <span class="d-inline-flex align-items-center gap-1 small font-weight-bold" style="color: {{ $dotColor }};">
                                        <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:{{ $dotColor }}; margin-right:4px;"></span>
                                        {{ $shippingLabels[$shStatus] ?? $shStatus }}
                                    </span>
                                </td>

                                {{-- Đơn vị VC --}}
                                <td class="small font-weight-bold text-secondary">
                                    {{ $order->ghn_order_code ? 'GHN Express' : '—' }}
                                </td>

                                {{-- Nút thao tác --}}
                                <td class="text-center">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-outline-primary btn-sm px-2 py-1" title="Xem chi tiết đơn hàng">
                                        <i class="fa-solid fa-eye"></i> Chi tiết
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-box-open mb-2" style="font-size: 2.2rem; opacity: 0.3;"></i>
                                    <div>Không tìm thấy đơn hàng nào phù hợp với bộ lọc hiện tại.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    {{-- PHÂN TRANG --}}
    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="small text-muted">
            Hiển thị {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} trong tổng số <strong>{{ $orders->total() }}</strong> đơn hàng
        </div>
        <div>
            {{ $orders->links() }}
        </div>
    </div>
</div>

<style>
/* ── LAB 8: TABS & ORDER MANAGEMENT LUXURY STYLES ── */
.order-tabs-wrapper {
    overflow-x: auto;
    padding-bottom: 4px;
}
.nav-tabs-scroll {
    display: flex;
    gap: 8px;
    white-space: nowrap;
}
.order-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #475569;
    text-decoration: none !important;
    transition: all 0.2s ease;
}
.order-tab-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
}
.order-tab-btn.active {
    background: #db2777;
    border-color: #db2777;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(219, 39, 119, 0.25);
}
.tab-badge {
    background: #f1f5f9;
    color: #475569;
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 0.74rem;
    font-weight: 700;
}
.tab-badge.badge-active {
    background: rgba(255, 255, 255, 0.28);
    color: #ffffff;
}

/* Bulk Action Bar */
.bulk-action-bar {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    border-radius: 12px;
    padding: 12px 18px;
    margin-bottom: 16px;
    animation: fadeIn 0.2s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}

.table-admin th {
    border-top: none;
    font-weight: 600;
}
.table-admin td {
    vertical-align: middle;
}
.order-row-item:hover {
    background-color: #fdf2f8 !important;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const selectAll = document.getElementById("selectAllOrders");
    const checkboxes = document.querySelectorAll(".order-checkbox");
    const bulkBar = document.getElementById("bulkActionBar");
    const countNumber = document.getElementById("selectedCountNumber");
    const btnDeselectAll = document.getElementById("btnDeselectAll");

    function updateBulkState() {
        const checkedBoxes = document.querySelectorAll(".order-checkbox:checked");
        const count = checkedBoxes.length;

        if (countNumber) countNumber.innerText = count;

        if (count > 0) {
            if (bulkBar) bulkBar.style.display = "block";
        } else {
            if (bulkBar) bulkBar.style.display = "none";
            if (selectAll) selectAll.checked = false;
        }

        if (selectAll && checkboxes.length > 0) {
            selectAll.checked = (count === checkboxes.length);
        }
    }

    if (selectAll) {
        selectAll.addEventListener("change", function () {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateBulkState();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener("change", updateBulkState);
    });

    if (btnDeselectAll) {
        btnDeselectAll.addEventListener("click", function () {
            checkboxes.forEach(cb => cb.checked = false);
            if (selectAll) selectAll.checked = false;
            updateBulkState();
        });
    }

    window.confirmBulkAction = function () {
        const checkedBoxes = document.querySelectorAll(".order-checkbox:checked");
        if (checkedBoxes.length === 0) {
            alert("Vui lòng tích chọn ít nhất 1 đơn hàng để thao tác.");
            return false;
        }

        const shippingStatus = document.getElementById("bulkShippingStatus").value;
        const orderStatus = document.getElementById("bulkOrderStatus").value;

        if (!shippingStatus && !orderStatus) {
            alert("Vui lòng chọn trạng thái mới cần cập nhật.");
            return false;
        }

        // Kiểm tra ràng buộc Lab 8: Không cho hủy nếu đơn đang giao
        if (shippingStatus === 'cancelled' || orderStatus === 'cancelled') {
            let hasDelivering = false;
            checkedBoxes.forEach(cb => {
                const tr = cb.closest("tr");
                if (tr && tr.dataset.delivering === '1') {
                    hasDelivering = true;
                }
            });

            if (hasDelivering) {
                return confirm("CẢNH BÁO: Trong các đơn đã chọn có đơn hàng đang ở trạng thái 'Đang giao'.\nTheo quy định, đơn hàng đang giao sẽ KHÔNG bị hủy.\nHệ thống sẽ chỉ hủy các đơn chưa giao. Bạn có muốn tiếp tục?");
            }
        }

        return confirm(`Bạn có chắc chắn muốn cập nhật trạng thái cho ${checkedBoxes.length} đơn hàng đã chọn?`);
    };
});
</script>
@endsection
