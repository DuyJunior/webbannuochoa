@extends('layouts.admin')

@section('title', 'Biểu đồ báo cáo doanh thu')
@section('page_title', 'Biểu đồ báo cáo doanh thu')

@section('content')
<style>
.chart-wrap { min-height: 340px; position: relative; }
.chart-wrap canvas { width: 100% !important; height: 340px !important; }
</style>

<div class="container-fluid p-0">

    {{-- Tabs chuyển đổi: Bảng số liệu / Biểu đồ --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 my-3">
        <nav class="nav nav-pills" aria-label="Báo cáo">
            <a class="nav-link font-weight-bold text-dark" href="{{ route('admin.reports.index', request()->query()) }}" style="border-radius: 20px; padding: 8px 20px; background:#fff; border:1px solid #e2e8f0;">
                <i class="fa-solid fa-table mr-1"></i> Bảng số liệu
            </a>
            <a class="nav-link active font-weight-bold ml-2" aria-current="page" href="{{ route('admin.reports.charts', request()->query()) }}" style="background:#db2777; border-radius: 20px; padding: 8px 20px;">
                <i class="fa-solid fa-chart-pie mr-1"></i> Biểu đồ trực quan
            </a>
        </nav>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm font-weight-bold" onclick="window.print()">
                <i class="fa-solid fa-print mr-1"></i> In / Xuất biểu đồ
            </button>
        </div>
    </div>

    {{-- BỘ LỌC BÁO CÁO BIỂU ĐỒ (THEO YÊU CẦU NGƯỜI DÙNG) --}}
    <div class="admin-card mb-4 filter-box shadow-sm">
        <form method="GET" action="{{ route('admin.reports.charts') }}" id="reportChartFilterForm">
            {{-- Hàng 1: Các nút mốc thời gian nhanh (Presets) --}}
            <div class="mb-3 d-flex align-items-center flex-wrap gap-2">
                <span class="small font-weight-bold text-muted mr-1"><i class="fa-regular fa-clock mr-1"></i> Mốc nhanh:</span>
                @php
                    $curPreset = $filters['preset'] ?? '';
                @endphp
                <button type="button" class="btn btn-sm {{ empty($curPreset) && empty($filters['date_from']) ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyChartPreset('')" style="border-radius: 16px;">Tất cả</button>
                <button type="button" class="btn btn-sm {{ $curPreset === 'today' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyChartPreset('today')" style="border-radius: 16px;">Hôm nay</button>
                <button type="button" class="btn btn-sm {{ $curPreset === 'yesterday' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyChartPreset('yesterday')" style="border-radius: 16px;">Hôm qua</button>
                <button type="button" class="btn btn-sm {{ $curPreset === '7days' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyChartPreset('7days')" style="border-radius: 16px;">7 ngày qua</button>
                <button type="button" class="btn btn-sm {{ $curPreset === '30days' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyChartPreset('30days')" style="border-radius: 16px;">30 ngày qua</button>
                <button type="button" class="btn btn-sm {{ $curPreset === 'this_month' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyChartPreset('this_month')" style="border-radius: 16px;">Tháng này</button>
                <button type="button" class="btn btn-sm {{ $curPreset === 'last_month' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyChartPreset('last_month')" style="border-radius: 16px;">Tháng trước</button>
                <button type="button" class="btn btn-sm {{ $curPreset === 'this_year' ? 'btn-dark font-weight-bold' : 'btn-light border' }}" onclick="applyChartPreset('this_year')" style="border-radius: 16px;">Năm nay</button>
                <input type="hidden" name="preset" id="reportChartPresetInput" value="{{ $curPreset }}">
            </div>

            {{-- Hàng 2: Bộ lọc chi tiết --}}
            <div class="row g-2 align-items-end">
                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="small font-weight-bold text-muted mb-1"><i class="fa-regular fa-calendar mr-1"></i> Từ ngày</label>
                    <input type="date" name="date_from" id="chartDateFromInput" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="small font-weight-bold text-muted mb-1"><i class="fa-regular fa-calendar-check mr-1"></i> Đến ngày</label>
                    <input type="date" name="date_to" id="chartDateToInput" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                </div>

                <div class="col-lg-3 col-md-6 mb-2">
                    <label class="small font-weight-bold text-muted mb-1"><i class="fa-solid fa-layer-group mr-1"></i> Danh mục sản phẩm</label>
                    <select name="category_id" class="form-control form-control-sm">
                        <option value="">-- Tất cả danh mục --</option>
                        @foreach($categoriesList as $cat)
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
                        <span class="badge badge-warning text-dark px-2 py-1"><i class="fa-solid fa-filter mr-1"></i> Biểu đồ đang theo bộ lọc tùy chỉnh</span>
                    @else
                        <span>Biểu đồ số liệu toàn thời gian</span>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    @if(!empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['category_id']) || !empty($filters['gateway']) || !empty($filters['preset']))
                        <a href="{{ route('admin.reports.charts') }}" class="btn btn-light btn-sm text-danger mr-1">
                            <i class="fa-solid fa-xmark mr-1"></i> Xóa lọc
                        </a>
                    @endif
                    <button type="submit" class="btn btn-primary btn-sm px-4" style="background:#db2777; border-color:#db2777;">
                        <i class="fa-solid fa-filter mr-1"></i> Cập nhật biểu đồ
                    </button>
                </div>
            </div>
        </form>
    </div>

    <p class="text-muted small">
        <i class="fa-solid fa-circle-info mr-1"></i> Chỉ gồm đơn đã thanh toán, chưa hoàn tiền và không bị hủy hoặc hoàn hàng. Doanh thu tính theo ngày tạo đơn; số liệu theo danh mục không gồm phí vận chuyển.
    </p>

    <div id="report-chart-error" class="alert alert-warning d-none" role="alert">
        Không tải được thư viện biểu đồ. Bạn có thể xem số liệu tại trang <a href="{{ route('admin.reports.index') }}">Bảng số liệu</a>.
    </div>

    <div class="row">
        {{-- Biểu đồ danh mục --}}
        <div class="col-lg-6 mb-4">
            <div class="admin-card shadow-sm h-100 p-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom font-weight-bold text-dark">
                    <i class="fa-solid fa-chart-simple text-primary mr-1"></i> Doanh thu theo danh mục
                </div>
                <div class="card-body chart-wrap p-3">
                    <canvas id="categoryRevenueChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Biểu đồ 30 ngày --}}
        <div class="col-lg-6 mb-4">
            <div class="admin-card shadow-sm h-100 p-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom font-weight-bold text-dark">
                    <i class="fa-solid fa-chart-line text-success mr-1"></i> Doanh thu theo ngày (30 ngày gần nhất)
                </div>
                <div class="card-body chart-wrap p-3">
                    <canvas id="revenueByDateChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Biểu đồ 12 tháng --}}
        <div class="col-lg-6 mb-4">
            <div class="admin-card shadow-sm h-100 p-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom font-weight-bold text-dark">
                    <i class="fa-solid fa-chart-column text-warning mr-1"></i> Doanh thu theo tháng (12 tháng gần nhất)
                </div>
                <div class="card-body chart-wrap p-3">
                    <canvas id="revenueByMonthChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Biểu đồ theo năm --}}
        <div class="col-lg-6 mb-4">
            <div class="admin-card shadow-sm h-100 p-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom font-weight-bold text-dark">
                    <i class="fa-solid fa-calendar-check text-info mr-1"></i> Doanh thu theo năm
                </div>
                <div class="card-body chart-wrap p-3">
                    <canvas id="revenueByYearChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Biểu đồ phương thức thanh toán --}}
        <div class="col-lg-12 mb-4">
            <div class="admin-card shadow-sm p-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom font-weight-bold text-dark">
                    <i class="fa-solid fa-chart-pie text-pink mr-1"></i> Doanh thu theo phương thức thanh toán (MoMo vs COD)
                </div>
                <div class="card-body chart-wrap p-3 d-flex justify-content-center">
                    <div style="width: 100%; max-width: 480px;">
                        <canvas id="revenueByPaymentMethodChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="report-chart-data" hidden data-chart-data="{{ json_encode([
    'catLabels' => $catLabels ?? [],
    'catRevenue' => $catRevenue ?? [],
    'revDateLabels' => $revDateLabels ?? [],
    'revDateData' => $revDateData ?? [],
    'revMonthLabels' => $revMonthLabels ?? [],
    'revMonthData' => $revMonthData ?? [],
    'revYearLabels' => $revYearLabels ?? [],
    'revYearData' => $revYearData ?? [],
    'paymentMethodLabels' => $paymentMethodLabels ?? [],
    'paymentMethodRevenue' => $paymentMethodRevenue ?? [],
]) }}"></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
    if (typeof Chart === 'undefined') {
        document.getElementById('report-chart-error').classList.remove('d-none');
        return;
    }

    // Dữ liệu Blade nằm trong HTML; phần này chỉ sử dụng JavaScript thuần.
    const reportData = JSON.parse(document.getElementById('report-chart-data').dataset.chartData);

    const catLabels = reportData.catLabels;
    const catRevenue = reportData.catRevenue.map(Number);

    const revDateLabels = reportData.revDateLabels;
    const revDateData = reportData.revDateData.map(Number);

    const revMonthLabels = reportData.revMonthLabels;
    const revMonthData = reportData.revMonthData.map(Number);

    const revYearLabels = reportData.revYearLabels;
    const revYearData = reportData.revYearData.map(Number);

    const payLabels = reportData.paymentMethodLabels;
    const payRevenue = reportData.paymentMethodRevenue.map(Number);

    const chartMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches || document.body.classList.contains('studio-motion-off') ? false : { duration: 350 };
    const mk = (el, type, labels, data, label, bgColor = '#a56385') => new Chart(el, {
        type,
        data: {
            labels,
            datasets: [{
                label,
                data,
                fill: type === 'line',
                tension: 0.3,
                backgroundColor: type === 'line' ? 'rgba(153, 70, 101, 0.08)' : bgColor,
                borderColor: type === 'line' ? '#994665' : bgColor,
                borderWidth: 2,
                borderRadius: type === 'bar' ? 6 : 0,
            }]
        },
        options: {
            animation: chartMotion,
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN').format(value) + ' đ';
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return (context.dataset.label || '') + ': ' + new Intl.NumberFormat('vi-VN').format(context.parsed.y) + ' VNĐ';
                        }
                    }
                }
            }
        }
    });

    if (document.getElementById('categoryRevenueChart')) {
        mk(document.getElementById('categoryRevenueChart'), 'bar', catLabels, catRevenue, 'Doanh thu (VNĐ)', '#bd8da7');
    }
    if (document.getElementById('revenueByDateChart')) {
        mk(document.getElementById('revenueByDateChart'), 'line', revDateLabels, revDateData, 'Doanh thu (VNĐ)');
    }
    if (document.getElementById('revenueByMonthChart')) {
        mk(document.getElementById('revenueByMonthChart'), 'bar', revMonthLabels, revMonthData, 'Doanh thu (VNĐ)', '#89738f');
    }
    if (document.getElementById('revenueByYearChart')) {
        mk(document.getElementById('revenueByYearChart'), 'bar', revYearLabels, revYearData, 'Doanh thu (VNĐ)', '#bc9c79');
    }

    if (document.getElementById('revenueByPaymentMethodChart')) {
        new Chart(document.getElementById('revenueByPaymentMethodChart'), {
            type: 'pie',
            data: {
                labels: payLabels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: payRevenue,
                    backgroundColor: ['#a56385', '#7c9b92', '#8d809f', '#c2a382', '#96a6b8'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                animation: chartMotion,
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + new Intl.NumberFormat('vi-VN').format(context.raw) + ' VNĐ';
                            }
                        }
                    }
                }
            }
        });
    }
});

function applyChartPreset(preset) {
    document.getElementById('reportChartPresetInput').value = preset;
    document.getElementById('chartDateFromInput').value = '';
    document.getElementById('chartDateToInput').value = '';
    document.getElementById('reportChartFilterForm').submit();
}
</script>
@endsection
