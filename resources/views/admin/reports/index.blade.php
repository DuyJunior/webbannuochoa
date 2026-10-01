@extends('layouts.admin')

@section('title', 'Báo cáo doanh thu')
@section('page_title', 'Báo cáo doanh thu')

@section('content')
<div class="container-fluid p-0">
    @include('admin.reports._filters', ['filterRoute' => 'admin.reports.index', 'filterCategories' => $categories])
    {{-- Cards thống kê tổng quan --}}
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="admin-card card-body h-100 shadow-sm border-0">
                <span class="text-muted small font-weight-bold text-uppercase">Đơn hàng trong kỳ</span>
                <h3 class="mb-0 mt-2 font-weight-bold text-dark">{{ number_format($totalOrders) }}</h3>
                <small class="text-muted mt-1">Theo ngày và nguồn dữ liệu · mọi trạng thái, danh mục và phương thức</small>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="admin-card card-body h-100 shadow-sm border-0">
                <span class="text-muted small font-weight-bold text-uppercase">Khách hàng toàn cửa hàng</span>
                <h3 class="mb-0 mt-2 font-weight-bold text-dark">{{ number_format($totalCustomers) }}</h3>
                <small class="text-muted mt-1">Tài khoản đã đăng ký · không theo bộ lọc</small>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="admin-card card-body h-100 shadow-sm border-0">
                <span class="text-muted small font-weight-bold text-uppercase">Giá trị đơn đã thanh toán</span>
                <h3 class="mb-0 mt-2 font-weight-bold text-success">{{ number_format($totalRevenue, 0, ',', '.') }} đ</h3>
                <small class="text-muted mt-1">Theo toàn bộ bộ lọc · bao gồm cước vận chuyển</small>
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
                            <td class="font-weight-bold text-dark">{{ $revenue->category_name ?? 'Chưa phân loại' }}</td>
                            <td class="text-right font-weight-500">{{ number_format($revenue->total_qty) }} chai</td>
                            <td class="text-right font-weight-bold text-success">{{ number_format($revenue->total_revenue, 0, ',', '.') }} đ</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="report-empty">Chưa có doanh thu theo danh mục trong khoảng đã chọn. Hãy thử mở rộng khoảng ngày hoặc xóa bộ lọc.</td>
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


@endsection
