@extends('layouts.admin')

@section('title', 'Biểu đồ báo cáo doanh thu')
@section('page_title', 'Biểu đồ báo cáo doanh thu')

@section('content')
<style>
.chart-wrap { min-height: 340px; position: relative; }
.chart-wrap canvas { width: 100% !important; height: 340px !important; }
</style>

<div class="container-fluid p-0">
    @include('admin.reports._filters', ['filterRoute' => 'admin.reports.charts', 'filterCategories' => $categoriesList])
    <div id="report-chart-error" class="alert alert-warning d-none" role="alert">
        Không tải được thư viện biểu đồ. Bạn có thể xem số liệu tại trang <a href="{{ route('admin.reports.index', $filters) }}">Bảng số liệu</a>.
    </div>

    @if(!$hasRevenue)
        <div class="admin-card report-empty"><i class="fa-solid fa-chart-simple mb-3" aria-hidden="true"></i><h3 class="h6">Chưa có doanh thu phù hợp</h3><p class="mb-3">Không có đơn đã thanh toán đủ điều kiện trong bộ lọc này.</p><a href="{{ route('admin.reports.charts', ['mode' => $filters['mode']]) }}" class="btn btn-outline-secondary btn-sm">Xóa bộ lọc</a></div>
    @endif

    <div class="row" @if(!$hasRevenue) hidden @endif>
        {{-- Biểu đồ danh mục --}}
        <div class="col-lg-6 mb-4">
            <div class="admin-card shadow-sm h-100 p-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom font-weight-bold text-dark">
                    <i class="fa-solid fa-chart-simple text-primary mr-1"></i> Doanh thu theo danh mục
                </div>
                <div class="card-body chart-wrap p-3">
                    <canvas id="categoryRevenueChart" role="img" aria-label="Biểu đồ doanh thu theo danh mục. Số liệu chi tiết có trong chế độ Bảng số liệu."></canvas>
                </div>
            </div>
        </div>

        {{-- Biểu đồ 30 ngày --}}
        <div class="col-lg-6 mb-4">
            <div class="admin-card shadow-sm h-100 p-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom font-weight-bold text-dark">
                    <i class="fa-solid fa-chart-line text-success mr-1"></i> Doanh thu theo ngày
                    <span class="report-card-note">{{ $chartDateRange }} · tối đa 90 ngày cuối kỳ đã chọn</span>
                </div>
                <div class="card-body chart-wrap p-3">
                    <canvas id="revenueByDateChart" role="img" aria-label="Biểu đồ doanh thu theo ngày, {{ $chartDateRange }}."></canvas>
                </div>
            </div>
        </div>

        {{-- Biểu đồ 12 tháng --}}
        <div class="col-lg-6 mb-4">
            <div class="admin-card shadow-sm h-100 p-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom font-weight-bold text-dark">
                    <i class="fa-solid fa-chart-column text-warning mr-1"></i> Doanh thu theo tháng
                    <span class="report-card-note">{{ $chartMonthRange }} · tối đa 12 tháng cuối kỳ đã chọn</span>
                </div>
                <div class="card-body chart-wrap p-3">
                    <canvas id="revenueByMonthChart" role="img" aria-label="Biểu đồ doanh thu theo tháng, {{ $chartMonthRange }}."></canvas>
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
                    <canvas id="revenueByYearChart" role="img" aria-label="Biểu đồ doanh thu theo năm."></canvas>
                </div>
            </div>
        </div>

        {{-- Biểu đồ phương thức thanh toán --}}
        <div class="col-lg-12 mb-4">
            <div class="admin-card shadow-sm p-0 overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom font-weight-bold text-dark">
                    <i class="fa-solid fa-chart-pie text-pink mr-1"></i> Doanh thu theo phương thức thanh toán
                </div>
                <div class="card-body chart-wrap p-3 d-flex justify-content-center">
                    <div style="width: 100%; max-width: 480px;">
                        <canvas id="revenueByPaymentMethodChart" role="img" aria-label="Tỷ trọng doanh thu theo phương thức thanh toán."></canvas>
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


</script>
@endsection
