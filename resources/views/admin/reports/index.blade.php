@extends('layouts.admin')

@section('title', 'Báo cáo doanh thu · Lab 8')
@section('page_title', 'Báo cáo doanh thu')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a class="btn btn-outline-secondary" href="{{ route('admin.reports.export', request()->query()) }}">Tải CSV theo bộ lọc</a>
        <h2 class="h4 font-weight-bold text-dark mb-0">Báo cáo doanh thu</h2>
    </div>

    {{-- Tabs chuyển đổi: Bảng số liệu / Biểu đồ --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 my-3">
        <nav class="nav nav-pills" aria-label="Báo cáo">
            <a class="nav-link active font-weight-bold" aria-current="page" href="{{ route('admin.reports.index', request()->query()) }}" style="background:#db2777; border-radius: 20px; padding: 8px 20px;">
                <i class="fa-solid fa-table mr-1"></i> Bảng số liệu
            </a>
            <a class="nav-link font-weight-bold ml-2 text-dark" href="{{ route('admin.reports.charts', request()->query()) }}" style="border-radius: 20px; padding: 8px 20px; background:#fff; border:1px solid #e2e8f0;">
                <i class="fa-solid fa-chart-pie mr-1"></i> Biểu đồ trực quan
            </a>
        </nav>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold" onclick="window.print()">
                <i class="fa-solid fa-print mr-1"></i> In / Xuất báo cáo
            </button>
        </div>
    </div>

    {{-- BỘ LỌC BÁO CÁO TOÀN DIỆN (THEO YÊU CẦU NGƯỜI DÙNG) --}}
    <div class="admin-card mb-4 filter-box shadow-sm">
        <form method="GET" action="{{ route('admin.reports.index') }}" id="reportFilterForm">
            <label>Loại dữ liệu
                <select name="mode" class="form-control">
                    <option value="real" @selected(($filters['mode'] ?? 'real') === 'real')>Đơn thực (loại trừ demo)</option>
                    <option value="demo" @selected(($filters['mode'] ?? '') === 'demo')>Chỉ đơn demo</option>
                </select>
            </label>
            <p>Số liệu tổng thu của đơn đã thanh toán, bao gồm phí giao hàng; không phải lợi nhuận. Đơn demo được thống kê riêng.</p>
            {{-- Hàng 1: Các nút mốc thời gian nhanh (Presets) --}}
            <div class="mb-3 d-flex align-items-center flex-wrap gap-2">
                <span class="small font-weight-bold text-muted mr-1"><i class="fa-regular fa-clock mr-1"></i> Mốc nhanh:</span>
                @php
                    $curPreset = $filters['preset'] ?? '';
                @endphp
                <button type="button" class="btn btn-sm {{ empty($curPreset) && empty($filters['date_from']) ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyPreset('')" style="border-radius: 16px;">Tất cả</button>
                <button type="button" class="btn btn-sm {{ $curPreset === 'today' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyPreset('today')" style="border-radius: 16px;">Hôm nay</button>
                <button type="button" class="btn btn-sm {{ $curPreset === 'yesterday' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyPreset('yesterday')" style="border-radius: 16px;">Hôm qua</button>
                <button type="button" class="btn btn-sm {{ $curPreset === '7days' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyPreset('7days')" style="border-radius: 16px;">7 ngày qua</button>
                <button type="button" class="btn btn-sm {{ $curPreset === '30days' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyPreset('30days')" style="border-radius: 16px;">30 ngày qua</button>
                <button type="button" class="btn btn-sm {{ $curPreset === 'this_month' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyPreset('this_month')" style="border-radius: 16px;">Tháng này</button>
                <button type="button" class="btn btn-sm {{ $curPreset === 'last_month' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyPreset('last_month')" style="border-radius: 16px;">Tháng trước</button>
                <button type="button" class="btn btn-sm {{ $curPreset === 'this_year' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyPreset('this_year')" style="border-radius: 16px;">Năm nay</button>
                <input type="hidden" name="preset" id="reportPresetInput" value="{{ $curPreset }}">
            </div>

            {{-- Hàng 2: Bộ lọc chi tiết --}}
            <div class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="small font-weight-bold text-muted mb-1"><i class="fa-regular fa-calendar mr-1"></i> Từ ngày</label>
                    <input type="date" name="date_from" id="dateFromInput" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="small font-weight-bold text-muted mb-1"><i class="fa-regular fa-calendar-check mr-1"></i> Đến ngày</label>
                    <input type="date" name="date_to" id="dateToInput" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="small font-weight-bold text-muted mb-1"><i class="fa-solid fa-layer-group mr-1"></i> Danh mục sản phẩm</label>
                    <select name="category_id" class="form-control form-control-sm">
                        <option value="">-- Tất cả danh mục --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="small font-weight-bold text-muted mb-1"><i class="fa-solid fa-wallet mr-1"></i> Phương thức thanh toán</label>
                    <select name="gateway" class="form-control form-control-sm">
                        <option value="">-- Tất cả phương thức --</option>
                        <option value="cod" {{ ($filters['gateway'] ?? '') === 'cod' ? 'selected' : '' }}>Tiền mặt (COD)</option>
                        <option value="momo" {{ ($filters['gateway'] ?? '') === 'momo' ? 'selected' : '' }}>Ví MoMo</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top flex-wrap gap-2">
                <div class="small text-muted">
                    @if(!empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['category_id']) || !empty($filters['gateway']) || !empty($filters['preset']))
                        <span class="badge badge-warning text-dark px-2 py-1"><i class="fa-solid fa-filter mr-1"></i> Đang áp dụng bộ lọc tùy chỉnh</span>
                    @else
                        <span>Dữ liệu báo cáo toàn thời gian</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @if(!empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['category_id']) || !empty($filters['gateway']) || !empty($filters['preset']))
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-light btn-sm text-danger mr-1">
                            <i class="fa-solid fa-xmark mr-1"></i> Xóa lọc
                        </a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm px-4" style="background:#db2777; border-color:#db2777;">
                        <i class="fa-solid fa-filter mr-1"></i> Áp dụng lọc
                    </button>
                </div>
            </div>
        </form>
    </div>

    <p class="text-muted small">
        <i class="fa-solid fa-circle-info mr-1"></i> Doanh thu tính theo ngày tạo đơn, chỉ gồm đơn đã thanh toán, chưa hoàn tiền và không bị hủy hoặc hoàn hàng.
    </p>

    {{-- Cards thống kê tổng quan --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="admin-card card-body h-100 shadow-sm border-0">
                <span class="text-muted small font-weight-bold text-uppercase">Tổng số đơn hàng</span>
                <h3 class="mb-0 mt-2 font-weight-bold text-dark">{{ number_format($totalOrders) }}</h3>
                <small class="text-muted mt-1">Đơn phát sinh trên hệ thống</small>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="admin-card card-body h-100 shadow-sm border-0">
                <span class="text-muted small font-weight-bold text-uppercase">Tổng số khách hàng</span>
                <h3 class="mb-0 mt-2 font-weight-bold text-dark">{{ number_format($totalCustomers) }}</h3>
                <small class="text-muted mt-1">Tài khoản thành viên đã đăng ký</small>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="admin-card card-body h-100 shadow-sm border-0">
                <span class="text-muted small font-weight-bold text-uppercase">Tổng doanh thu thực thu</span>
                <h3 class="mb-0 mt-2 font-weight-bold text-success">{{ number_format($totalRevenue, 0, ',', '.') }} đ</h3>
                <small class="text-muted mt-1">Bao gồm cước vận chuyển</small>
            </div>
        </div>
    </div>

    {{-- Bảng doanh thu theo danh mục --}}
    <div class="admin-card mb-4 p-0 overflow-hidden shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <strong class="text-dark"><i class="fa-solid fa-folder-open text-primary mr-1"></i> Doanh thu theo danh mục nước hoa</strong>
                <div class="small text-muted">Tính theo giá sản phẩm khi đặt hàng, không gồm phí vận chuyển.</div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="bg-light text-muted" style="font-size: 0.8rem; text-transform: uppercase;">
                    <tr>
                        <th>Danh mục</th>
                        <th class="text-right">Số lượng bán</th>
                        <th class="text-right">Doanh thu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categoryRevenue as $revenue)
                        <tr>
                            <td class="font-weight-bold text-dark">{{ $revenue->category_name ?? ('Danh mục #'.$revenue->category_id) }}</td>
                            <td class="text-right font-weight-500">{{ number_format($revenue->total_qty) }} chai</td>
                            <td class="text-right font-weight-bold text-success">{{ number_format($revenue->total_revenue, 0, ',', '.') }} đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">Chưa có dữ liệu doanh thu.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bảng doanh thu theo ngày, tháng, năm --}}
    @foreach([
        ['Doanh thu theo ngày', 'Ngày', 'date', $revenueByDate, 'd/m/Y'],
        ['Doanh thu theo tháng', 'Tháng', 'month', $revenueByMonth, 'm/Y'],
        ['Doanh thu theo năm', 'Năm', 'year', $revenueByYear, null],
    ] as [$title, $label, $field, $rows, $format])
        <div class="admin-card mb-4 p-0 overflow-hidden shadow-sm">
            <div class="card-header bg-white py-3 border-bottom font-weight-bold text-dark">
                <i class="fa-solid fa-calendar-days text-pink mr-1"></i> {{ $title }}
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="bg-light text-muted" style="font-size: 0.8rem; text-transform: uppercase;">
                        <tr>
                            <th>{{ $label }}</th>
                            <th class="text-right">Số đơn đã thanh toán</th>
                            <th class="text-right">Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $revenue)
                            <tr>
                                <td class="font-weight-500 text-dark">
                                    {{ $format ? \Carbon\Carbon::parse($revenue->{$field}.($field === 'month' ? '-01' : ''))->format($format) : $revenue->{$field} }}
                                </td>
                                <td class="text-right font-weight-500">{{ number_format($revenue->order_count) }} đơn</td>
                                <td class="text-right font-weight-bold text-success">{{ number_format($revenue->total_revenue, 0, ',', '.') }} đ</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">Chưa có doanh thu.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

<script>
function applyPreset(preset) {
    document.getElementById('reportPresetInput').value = preset;
    // Clear custom date inputs so preset takes effect cleanly
    document.getElementById('dateFromInput').value = '';
    document.getElementById('dateToInput').value = '';
    document.getElementById('reportFilterForm').submit();
}
</script>
@endsection
