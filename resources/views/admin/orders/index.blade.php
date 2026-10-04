@extends('layouts.admin')

@section('title', __('Quản lý đơn hàng'))
@section('page_title', __('Quản lý đơn hàng'))

@section('content')
<div class="admin-orders-container">
    @include('admin.partials.print-brand', ['printTitle' => __('Danh sách đơn hàng'), 'printSubtitle' => number_format($orders->total()).__(' đơn phù hợp · Trang ').$orders->currentPage().' / '.$orders->lastPage()])
    {{-- 1. BỘ LỌC TABS THEO QUY CHUẨN LAB 8 (PDF Trang 1, 6) --}}
    <div class="order-tabs-wrapper mb-3">
        <div class="nav-tabs-scroll" aria-label="{{ __('Lọc theo trạng thái đơn hàng') }}">
            @foreach($tabs as $tabKey => $tabItem)
                @php
                    $isActive = ($activeTab === $tabKey);
                    $urlParams = request()->except(['tab', 'page']);
                    if ($tabKey !== 'all') {
                        $urlParams['tab'] = $tabKey;
                    }
                    $tabUrl = route('admin.orders.index', $urlParams);
                @endphp
                <a href="{{ $tabUrl }}" @if($isActive) aria-current="page" @endif class="order-tab-btn {{ $isActive ? 'active' : '' }} tab-{{ $tabItem['color'] }}">
                    <span>{{ __($tabItem['label']) }}</span>
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

            <div class="order-filter-grid">
                <div class="order-filter-field order-filter-search">
                    <label for="order-search">{{ __('Tìm đơn hàng') }}</label>
                    <input id="order-search" type="search" name="search" class="form-control" maxlength="100" placeholder="{{ __('Mã đơn, khách hàng, số điện thoại…') }}" value="{{ request('search') }}">
                </div>
                <div class="order-filter-field">
                    <label for="order-payment">{{ __('Thanh toán') }}</label>
                    <select id="order-payment" name="payment_status" class="form-control">
                        <option value="">{{ __('Tất cả trạng thái') }}</option>
                        @foreach($paymentLabels as $pKey => $pLabel)
                            <option value="{{ $pKey }}" @selected(request('payment_status') === $pKey)>{{ __($pLabel) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="order-filter-field">
                    <label for="order-gateway">{{ __('Phương thức') }}</label>
                    <select id="order-gateway" name="gateway" class="form-control">
                        <option value="">{{ __('Tất cả phương thức') }}</option>
                        <option value="cod" @selected(request('gateway') === 'cod')>{{ __('Tiền mặt (COD)') }}</option>
                        <option value="sepay" @selected(request('gateway') === 'sepay')>SePay</option>
                        <option value="momo" @selected(request('gateway') === 'momo')>{{ __('Ví MoMo') }}</option>
                        <option value="unknown" @selected(request('gateway') === 'unknown')>{{ __('Chưa xác định') }}</option>
                    </select>
                </div>
                <div class="order-filter-field">
                    <label for="order-date-from">{{ __('Từ ngày') }}</label>
                    <input id="order-date-from" type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="order-filter-field">
                    <label for="order-date-to">{{ __('Đến ngày') }}</label>
                    <input id="order-date-to" type="date" name="date_to" class="form-control" min="{{ request('date_from') }}" value="{{ request('date_to') }}">
                </div>
                <div class="order-filter-field order-filter-limit">
                    <label for="order-per-page">{{ __('Số đơn / trang') }}</label>
                    <select id="order-per-page" name="per_page" class="form-control">
                        @foreach([25, 50, 100] as $limit)
                            <option value="{{ $limit }}" @selected(request('per_page', 25) == $limit)>{{ $limit }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="order-filter-field">
                    <label for="order-status">{{ __('Trạng thái đơn') }}</label>
                    <select id="order-status" name="status" class="form-control">
                        <option value="">{{ __('Tất cả trạng thái đơn') }}</option>
                        @foreach(['pending' => __('Chờ xử lý'), 'confirmed' => __('Đã xác nhận'), 'paid' => __('Đã thanh toán'), 'paid_momo' => __('Đã thanh toán MoMo'), 'cod_ordered' => __('Chờ thu COD'), 'cod_paid' => __('Đã thu COD'), 'completed' => __('Đã hoàn thành'), 'cancelled' => __('Đã hủy')] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="order-filter-field">
                    <label for="order-shipping">{{ __('Trạng thái vận chuyển') }}</label>
                    <select id="order-shipping" name="shipping_status" class="form-control">
                        <option value="">{{ __('Tất cả trạng thái giao hàng') }}</option>
                        @foreach($shippingLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('shipping_status') === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="order-filter-field order-filter-sort">
                    <label for="order-sort">{{ __('Sắp xếp') }}</label>
                    <select id="order-sort" name="sort" class="form-control">
                        <option value="newest" @selected(request('sort', 'newest') === 'newest')>{{ __('Mới nhất') }}</option>
                        <option value="oldest" @selected(request('sort') === 'oldest')>{{ __('Cũ nhất') }}</option>
                        <option value="amount_desc" @selected(request('sort') === 'amount_desc')>{{ __('Giá trị cao nhất') }}</option>
                        <option value="amount_asc" @selected(request('sort') === 'amount_asc')>{{ __('Giá trị thấp nhất') }}</option>
                    </select>
                </div>
            </div>
            <div class="order-filter-footer">
                <span class="order-result-count"><strong>{{ number_format($orders->total()) }}</strong> {{ __('đơn hàng phù hợp') }}</span>
                <div class="order-filter-actions">
                    @if(request()->anyFilled(['search', 'status', 'payment_status', 'gateway', 'date_from', 'date_to', 'shipping_status']))
                        <a href="{{ route('admin.orders.index', request('tab') ? ['tab' => request('tab')] : []) }}" class="btn btn-light">{{ __('Xóa bộ lọc') }}</a>
                    @endif
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="fa-solid fa-print" aria-hidden="true"></i> {{ __('In danh sách') }}</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter" aria-hidden="true"></i> {{ __('Áp dụng bộ lọc') }}</button>
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
                        {{ __('Đã chọn') }} <strong id="selectedCountNumber" aria-live="polite">0</strong> {{ __('đơn hàng trên trang') }}
                    </span>
                    <button type="button" class="btn btn-link btn-sm text-secondary" id="btnDeselectAll">{{ __('Bỏ chọn tất cả') }}</button>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <label class="mb-0 small font-weight-bold text-dark" for="bulkShippingStatus">{{ __('Vận chuyển:') }}</label>
                    <select name="bulk_shipping_status" id="bulkShippingStatus" class="form-control form-control-sm" style="width: 170px;">
                        <option value="">{{ __('-- Trạng thái giao --') }}</option>
                        <option value="pending">{{ __('Chờ tạo vận đơn') }}</option>
                        <option value="ready_to_pick">{{ __('Chờ lấy hàng') }}</option>
                        <option value="picking">{{ __('Đang lấy hàng') }}</option>
                        <option value="delivering">{{ __('Đang giao hàng') }}</option>
                        <option value="delivered">{{ __('Giao thành công') }}</option>
                        <option value="cancelled">{{ __('Hủy đơn (Không giao)') }}</option>
                    </select>

                    <label class="sr-only" for="bulkOrderStatus">{{ __('Trạng thái đơn hàng') }}</label>
                    <select name="bulk_status" id="bulkOrderStatus" class="form-control form-control-sm" style="width: 170px;">
                        <option value="">{{ __('-- Trạng thái đơn --') }}</option>
                        <option value="pending">{{ __('Chờ xử lý') }}</option>
                        <option value="confirmed">{{ __('Đã xác nhận') }}</option>
                        <option value="completed">{{ __('Đã hoàn thành') }}</option>
                        <option value="cancelled">{{ __('Đã hủy đơn') }}</option>
                    </select>

                    <button type="submit" class="btn btn-success btn-sm font-weight-bold px-3" onclick="return confirmBulkAction()">
                        <i class="fa-solid fa-bolt mr-1"></i> {{ __('Cập nhật hàng loạt') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- 4. BẢNG DANH SÁCH ĐƠN HÀNG (PDF Trang 6) --}}
        <div class="admin-card p-0 overflow-hidden shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover table-admin align-middle mb-0 {{ $orders->isNotEmpty() ? 'order-table-populated' : 'order-table-empty' }}">
                    @if($orders->isNotEmpty())
                    <thead>
                        <tr>
                            <th class="order-select-cell"><label class="order-select-all"><input type="checkbox" id="selectAllOrders" aria-label="{{ __('Chọn tất cả đơn hàng trên trang') }}"><span>{{ __('Chọn tất cả') }}</span></label></th>
                            <th class="order-meta-cell" scope="col">{{ __('Đơn hàng') }}</th>
                            <th class="order-customer-cell" scope="col">{{ __('Khách hàng') }}</th>
                            <th class="order-products-cell" scope="col">{{ __('Sản phẩm') }}</th>
                            <th class="order-payment-cell" scope="col">{{ __('Thanh toán') }}</th>
                            <th class="order-shipping-cell" scope="col">{{ __('Vận chuyển') }}</th>
                            <th class="order-actions-cell" scope="col"><span class="sr-only">{{ __('Thao tác') }}</span></th>
                        </tr>
                    </thead>
                    @endif
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
                                $shStatus = $order->shipping_status ?? 'pending';
                                $dotColor = match($shStatus) {
                                    'delivered' => '#15803d',
                                    'delivering', 'transporting', 'sorting', 'picked' => '#a16207',
                                    'ready_to_pick', 'picking' => '#0e7490',
                                    'return', 'returning', 'returned', 'return_transporting', 'return_sorting' => '#c2410c',
                                    'cancelled' => '#b91c1c',
                                    default => '#64748b'
                                };
                            @endphp
                            <tr class="order-row-item" data-delivering="{{ $isDelivering ? '1' : '0' }}">
                                <td class="order-select-cell">
                                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="order-checkbox" aria-label="Chọn đơn hàng {{ $order->id }}">
                                </td>
                                <td class="order-meta-cell">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="order-code">#DH{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</a>
                                    @if($order->is_demo)<span class="badge badge-warning mt-1">DEMO</span>@endif
                                    <time class="order-secondary" datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->format('d/m/Y') }}<br> {{ $order->created_at->format('H:i') }}</time>
                                </td>
                                <td class="order-customer-cell" data-label="{{ __('Khách hàng') }}">
                                    <strong class="order-customer-name">{{ $order->name ?? $order->customer_name }}</strong>
                                    <span class="order-secondary">{{ $order->phone }}</span>
                                    @if($order->user_id)
                                        <button type="button" class="order-chat-link" data-user-id="{{ $order->user_id }}" data-customer-name="{{ $order->name ?? $order->customer_name }}" onclick="openChatWithUser(Number(this.dataset.userId), this.dataset.customerName)">@include('partials.icon', ['name' => 'chat', 'size' => '1em']) {{ __('Nhắn tin') }}</button>
                                    @endif
                                </td>
                                <td class="order-products-cell" data-label="{{ __('Sản phẩm') }}">
                                    <div class="order-items-snippet">
                                        @foreach($order->items->take(2) as $item)
                                            <div class="order-product-line">
                                                <span>{{ $item->display_name }}</span>
                                                <span class="order-secondary">{{ __('×') }} {{ $item->quantity }} · {{ $item->volume_label }}</span>
                                            </div>
                                        @endforeach
                                        @if($order->items->count() > 2)
                                            <a class="order-more-items" href="{{ route('admin.orders.show', $order->id) }}">+ {{ $order->items->count() - 2 }} {{ __('sản phẩm khác') }}</a>
                                        @endif
                                    </div>
                                </td>
                                <td class="order-payment-cell" data-label="{{ __('Thanh toán') }}">
                                    <strong class="order-amount">{{ number_format($order->total_price, 0, ',', '.') }} {{ __('đ') }}</strong>
                                    <span class="badge {{ $paymentBadgeClass }} order-payment-badge">{{ __($paymentLabels[$order->payment_status] ?? strtoupper($order->status)) }}</span>
                                    <span class="order-secondary">{{ ['cod' => __('Tiền mặt (COD)'), 'sepay' => 'SePay', 'momo' => __('Ví MoMo'), 'demo' => __('Thanh toán mô phỏng')][$order->gateway] ?? __('Chưa xác định phương thức') }}</span>
                                    @if($order->gateway === 'cod' && in_array($order->payment_status, ['pending', 'failed']) && $order->status !== 'cancelled' && !in_array($order->shipping_status, ['cancelled', 'return', 'returning', 'returned', 'return_transporting', 'return_sorting']))
                                        <span class="order-secondary">{{ __('COD cần thu:') }} <span class="order-cod">{{ number_format($order->total_price, 0, ',', '.') }} {{ __('đ') }}</span></span>
                                    @endif
                                </td>
                                <td class="order-shipping-cell" data-label="{{ __('Vận chuyển') }}">
                                    <span class="order-shipping-status" style="color: {{ $dotColor }}"><span class="order-status-dot" aria-hidden="true"></span>{{ __($shippingLabels[$shStatus] ?? $shStatus) }}</span>
                                    @if($order->ghn_order_code)
                                        <span class="order-secondary">GHN Express</span>
                                        <span class="order-tracking-code">{{ $order->ghn_order_code }}</span>
                                    @else
                                        <span class="order-secondary">{{ __('Chưa tạo vận đơn') }}</span>
                                    @endif
                                </td>
                                <td class="order-actions-cell">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="order-detail-link" aria-label="Chi tiết đơn hàng {{ $order->id }}">{{ __('Chi tiết') }} <span aria-hidden="true">→</span></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted order-empty-state">
                                    <i class="fa-solid fa-box-open mb-2" style="font-size: 2.2rem; opacity: 0.3;"></i>
                                    <div>{{ __('Không tìm thấy đơn hàng nào phù hợp với bộ lọc hiện tại.') }}</div>
                                    @if(request()->anyFilled(['search', 'status', 'payment_status', 'gateway', 'date_from', 'date_to', 'shipping_status', 'tab']))
                                        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm mt-3">{{ __('Xem tất cả đơn hàng') }}</a>
                                    @endif
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
            {{ __('Hiển thị') }} {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} {{ __('trong tổng số') }} <strong>{{ $orders->total() }}</strong> {{ __('đơn hàng') }}
        </div>
        <div>
            {{ $orders->links() }}
        </div>
    </div>
</div>

<style>
.admin-orders-container{min-width:0;max-width:100%;font-size:13px}
.admin-orders-container .order-tabs-wrapper{margin-bottom:18px!important}
.admin-orders-container .nav-tabs-scroll{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px}
.admin-orders-container .order-tab-btn{display:flex;align-items:center;justify-content:space-between;gap:8px;min-height:44px;padding:10px 13px;background:#fff;border:1px solid #efdee5;border-radius:10px;color:#765765;font-size:12px;font-weight:600;text-decoration:none;line-height:1.4}
.admin-orders-container .order-tab-btn:hover{background:#fff3f7;border-color:#dba9bb}
.admin-orders-container .order-tab-btn.active{background:#a83d5d;border-color:#a83d5d;color:#fff}
.admin-orders-container .tab-badge{display:grid;place-items:center;min-width:24px;height:24px;padding:0 6px;border-radius:7px;background:#f9edf2;color:#9c4968;font-size:11px;font-variant-numeric:tabular-nums}
.admin-orders-container .tab-badge.badge-active{background:#ffffff30;color:#fff}
.admin-orders-container .filter-box{padding:20px;border-radius:12px;margin-bottom:20px!important}
.order-filter-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:16px}
.order-filter-field{grid-column:span 3;min-width:0}
.order-filter-search{grid-column:span 6}
.order-filter-limit{grid-column:span 3}
.order-filter-sort{grid-column:span 3}
.order-filter-field label{display:block;margin:0 0 7px;color:#765765;font-size:12px;font-weight:600;line-height:1.4}
.boutique-admin .admin-orders-container .form-control{height:42px;min-height:42px;width:100%;font-size:13px;padding:9px 11px;border-radius:8px;line-height:1.4;color:#49343e;background-color:#fff;min-width:0}
.order-filter-footer{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-top:1px solid #f1e3e9;margin-top:19px;padding-top:16px}
.order-result-count{color:#8d7480;font-size:12px}
.order-result-count strong{color:#a83d5d;font-variant-numeric:tabular-nums}
.order-filter-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.boutique-admin .order-filter-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:40px;min-height:40px;padding:0 14px;border-radius:8px;font-size:12px;font-weight:600;white-space:nowrap}
.admin-orders-container .table-responsive{max-width:100%;overflow:visible}
.admin-orders-container .order-table-populated{width:100%;min-width:0;table-layout:fixed}
.boutique-admin .admin-orders-container .table-admin thead th{padding:14px 10px;white-space:normal;font-size:11px;line-height:1.4;letter-spacing:.03em;border-top:0}
.boutique-admin .admin-orders-container .table-admin td{padding:16px 10px;vertical-align:top;font-size:12px;line-height:1.5;overflow-wrap:anywhere}
.order-table-populated .order-select-cell{width:4%;text-align:center}
.order-table-populated .order-meta-cell{width:13%}
.order-table-populated .order-customer-cell{width:18%}
.order-table-populated .order-products-cell{width:22%}
.order-table-populated .order-payment-cell{width:17%}
.order-table-populated .order-shipping-cell{width:18%}
.order-table-populated .order-actions-cell{width:8%;text-align:right}
.order-select-all{margin:0;display:inline-flex;align-items:center;gap:8px}
.order-select-all span{display:none}
.order-select-all input,.order-checkbox{width:16px;height:16px;accent-color:#a83d5d;cursor:pointer}
.order-code,.order-customer-name,.order-amount{display:block;color:#38282e;font-weight:600}
.order-code{white-space:nowrap}
.order-secondary{display:block;font-size:11px;color:#82717a;margin-top:4px;line-height:1.5}
.order-amount,.order-cod{font-variant-numeric:tabular-nums}
.order-payment-badge{display:inline-block;max-width:100%;white-space:normal;font-size:10px;font-weight:500;line-height:1.4;margin-top:6px;padding:3px 6px}
.order-product-line+.order-product-line{margin-top:8px}
.order-product-line .order-secondary{margin-top:2px}
.order-chat-link,.order-more-items{display:inline-flex;align-items:center;gap:4px;background:none;border:0;padding:0;color:#a83d5d;font-size:11px;margin-top:6px}
.order-shipping-status{display:flex;align-items:baseline;gap:5px;font-size:11px;font-weight:600}
.order-status-dot{flex:0 0 6px;width:6px;height:6px;border-radius:50%;background:currentColor}
.order-tracking-code{display:block;font-family:monospace;font-size:11px;margin-top:2px;color:#735864}
.order-detail-link{display:inline-flex;align-items:center;justify-content:center;gap:4px;white-space:nowrap;color:#a83d5d;font-size:11px;font-weight:600;padding:5px 0}
.order-detail-link:hover,.order-chat-link:hover,.order-more-items:hover{color:#7f2947;text-decoration:underline}
@media(max-width:1100px){
    .admin-orders-container .order-table-populated,.order-table-populated thead,.order-table-populated tbody{display:block;width:100%}
    .order-table-populated thead tr{display:block}
    .order-table-populated thead th:not(.order-select-cell){display:none}
    .boutique-admin .admin-orders-container .order-table-populated thead .order-select-cell{display:block;width:100%;text-align:left;padding:14px 18px}
    .order-select-all span{display:inline}
    .order-table-populated .order-row-item{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 24px;padding:18px;position:relative;border-top:1px solid #efdee5}
    .boutique-admin .admin-orders-container .order-row-item td{display:block;width:auto;padding:0;border:0;min-width:0}
    .order-row-item .order-select-cell{position:absolute;right:18px;top:20px}
    .order-row-item .order-meta-cell{grid-column:1/-1;padding-right:30px!important}
    .order-meta-cell time br{display:none}
    .order-meta-cell time{word-spacing:4px}
    .order-row-item td[data-label]::before{content:attr(data-label);display:block;color:#9b7b8b;font-size:10px;font-weight:600;margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
    .order-row-item .order-actions-cell{grid-column:1/-1;text-align:right;border-top:1px solid #f3e8ed!important;padding-top:10px!important}
    .order-detail-link{padding:7px 12px;border:1px solid #ecd1dc;border-radius:6px}
}
@media(max-width:420px){.order-table-populated .order-row-item{gap:14px}.order-detail-link{width:100%}}
.admin-orders-container .order-row-item:hover{background:#fff8fa}
.admin-orders-container .order-row-item.is-selected{background:#f7f1f6}
.boutique-admin .admin-orders-container .table-admin td.order-empty-state{padding:54px 20px;line-height:1.8;border-top:0;background:#fff}
.admin-orders-container .order-empty-state i{display:block;margin:0 auto 14px!important;color:#c492a5;opacity:1!important}
.admin-orders-container .bulk-action-bar{background:#fff1f5;border:1px solid #edccd8;border-radius:12px;padding:16px;margin-bottom:18px}
.admin-orders-container .bulk-action-bar .d-flex{gap:10px;flex-wrap:wrap}
@media(min-width:1600px){.admin-orders-container .nav-tabs-scroll{grid-template-columns:repeat(8,minmax(0,1fr))}}
@media(max-width:1150px){.order-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.order-filter-field{grid-column:span 1}.order-filter-search{grid-column:1/-1}}
@media(max-width:600px){.admin-orders-container .nav-tabs-scroll{grid-template-columns:repeat(2,minmax(0,1fr))}.admin-orders-container .filter-box{padding:16px}.order-filter-grid{gap:14px 10px}.order-filter-footer{align-items:stretch;flex-direction:column}.order-filter-actions .btn{flex:1}.boutique-admin .admin-orders-container .form-control{font-size:14px}}
@media(max-width:380px){.order-filter-field{grid-column:1/-1}}
@media print{.order-tabs-wrapper,.filter-box,.bulk-action-bar,.order-select-cell,.order-actions-cell,.order-chat-link{display:none!important}.admin-orders-container .table-responsive{overflow:visible}.order-row-item{break-inside:avoid}}
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
            selectAll.indeterminate = count > 0 && count < checkboxes.length;
        }
        checkboxes.forEach(cb => cb.closest('tr').classList.toggle('is-selected', cb.checked));
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

    updateBulkState();
    document.getElementById('order-date-from').addEventListener('change', function () {
        document.getElementById('order-date-to').min = this.value;
    });

    window.confirmBulkAction = function () {
        const checkedBoxes = document.querySelectorAll(".order-checkbox:checked");
        if (checkedBoxes.length === 0) {
            alert((window.soopiT || (text => text))("Vui lòng tích chọn ít nhất 1 đơn hàng để thao tác."));
            return false;
        }

        const shippingStatus = document.getElementById("bulkShippingStatus").value;
        const orderStatus = document.getElementById("bulkOrderStatus").value;

        if (!shippingStatus && !orderStatus) {
            alert((window.soopiT || (text => text))("Vui lòng chọn trạng thái mới cần cập nhật."));
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
                return confirm((window.soopiT || (text => text))("CẢNH BÁO: Trong các đơn đã chọn có đơn hàng đang ở trạng thái 'Đang giao'.\nTheo quy định, đơn hàng đang giao sẽ KHÔNG bị hủy.\nHệ thống sẽ chỉ hủy các đơn chưa giao. Bạn có muốn tiếp tục?"));
            }
        }

        return confirm(`${(window.soopiT || (text => text))("Bạn có chắc chắn muốn cập nhật trạng thái cho")} ${checkedBoxes.length} ${(window.soopiT || (text => text))("đơn hàng đã chọn?")}`);
    };
});
</script>
@endsection
