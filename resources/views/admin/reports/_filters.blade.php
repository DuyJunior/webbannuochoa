@php
    $chartView = $filterRoute === 'admin.reports.charts';
    $hasFilters = !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['category_id']) || !empty($filters['gateway']) || !empty($filters['preset']);
    $presets = ['' => 'Tất cả', 'today' => 'Hôm nay', 'yesterday' => 'Hôm qua', '7days' => '7 ngày', '30days' => '30 ngày', 'this_month' => 'Tháng này', 'last_month' => 'Tháng trước', 'this_year' => 'Năm nay'];
@endphp
@include('admin.partials.print-brand', ['printTitle' => $chartView ? 'Biểu đồ báo cáo doanh thu' : 'Báo cáo doanh thu', 'printSubtitle' => ($filters['mode'] === 'demo' ? 'DỮ LIỆU MÔ PHỎNG' : 'GIAO DỊCH THỰC').' · '.(!empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : 'Từ đầu').' → '.(!empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : 'Hiện tại')])
<style>
.report-toolbar{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px}.report-view-switch{display:flex;gap:4px;padding:4px;background:#eeeaf0;border-radius:10px}.report-view-switch a{padding:9px 15px;border-radius:7px;color:#645669;font-size:13px;font-weight:600;text-decoration:none}.report-view-switch a[aria-current]{background:#fff;color:#50344d;box-shadow:0 1px 4px #34243312}.report-export-actions{display:flex;gap:8px;flex-wrap:wrap}.report-presets{display:flex;gap:6px;flex-wrap:wrap;padding-bottom:18px;border-bottom:1px solid #ece7ed;margin-bottom:18px}.report-presets .btn{border-radius:7px;font-size:12px;min-height:34px}.report-filter-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px}.report-filter-grid label{display:block;font-size:12px;font-weight:600;margin-bottom:7px}.report-filter-grid .form-control{width:100%;font-size:13px;min-height:42px}.report-filter-footer{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-top:18px;padding-top:16px;border-top:1px solid #ece7ed}.report-filter-summary{font-size:12px;color:#76697c}.report-filter-summary strong{color:#514357}.report-empty{padding:38px 20px;text-align:center;color:#76697c}.report-card-note{display:block;font-size:12px;font-weight:400;color:#76697c;margin-top:5px}.report-definition{font-size:12px;line-height:1.7;color:#76697c;margin-bottom:20px}.report-mode{display:inline-flex;padding:4px 8px;background:#edf5ef;color:#306547;border-radius:5px;font-size:11px;font-weight:700}.report-mode.demo{background:#fff2d8;color:#815a17}@media(max-width:1200px){.report-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:650px){.report-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.report-view-switch{width:100%}.report-view-switch a{flex:1;text-align:center}.report-filter-footer{align-items:stretch;flex-direction:column}}@media(max-width:400px){.report-filter-grid{grid-template-columns:1fr}}@media print{.report-toolbar,.report-filter-card{display:none!important}.chart-wrap{break-inside:avoid}}
</style>
<div class="report-toolbar">
    <nav class="report-view-switch" aria-label="Chế độ xem báo cáo">
        <a href="{{ route('admin.reports.index', $filters) }}" @if(!$chartView) aria-current="page" @endif><i class="fa-solid fa-table mr-1" aria-hidden="true"></i> Bảng số liệu</a>
        <a href="{{ route('admin.reports.charts', $filters) }}" @if($chartView) aria-current="page" @endif><i class="fa-solid fa-chart-simple mr-1" aria-hidden="true"></i> Biểu đồ</a>
    </nav>
    <div class="report-export-actions">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa-solid fa-print mr-1" aria-hidden="true"></i> In báo cáo</button>
        <a class="btn btn-primary btn-sm" href="{{ route('admin.reports.export', $filters) }}"><i class="fa-solid fa-download mr-1" aria-hidden="true"></i> Xuất CSV</a>
    </div>
</div>
<div class="admin-card report-filter-card mb-4">
    <form method="GET" action="{{ route($filterRoute) }}" id="reportFilters">
        <div class="report-presets" role="group" aria-label="Khoảng thời gian nhanh">
            @foreach($presets as $value => $label)
                @php($selected = ($filters['preset'] ?? '') === $value && ($value !== '' || (empty($filters['date_from']) && empty($filters['date_to']))))
                <button type="button" data-report-preset="{{ $value }}" class="btn btn-sm {{ $selected ? 'btn-primary' : 'btn-light border' }}" aria-pressed="{{ $selected ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="report-filter-grid">
            <div><label for="report-mode">Nguồn dữ liệu</label><select id="report-mode" name="mode" class="form-control"><option value="real" @selected(($filters['mode'] ?? 'real') === 'real')>Giao dịch thực</option><option value="demo" @selected(($filters['mode'] ?? '') === 'demo')>Dữ liệu mô phỏng</option></select></div>
            <div><label for="report-date-from">Từ ngày tạo đơn</label><input id="report-date-from" type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div><label for="report-date-to">Đến ngày tạo đơn</label><input id="report-date-to" type="date" name="date_to" min="{{ $filters['date_from'] ?? '' }}" class="form-control" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div><label for="report-category">Danh mục sản phẩm</label><select id="report-category" name="category_id" class="form-control"><option value="">Tất cả danh mục</option>@foreach($filterCategories as $category)<option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
            <div><label for="report-gateway">Phương thức thanh toán</label><select id="report-gateway" name="gateway" class="form-control"><option value="">Tất cả phương thức</option><option value="cod" @selected(($filters['gateway'] ?? '') === 'cod')>Tiền mặt (COD)</option><option value="momo" @selected(($filters['gateway'] ?? '') === 'momo')>Ví MoMo</option><option value="demo" @selected(($filters['gateway'] ?? '') === 'demo')>Mô phỏng</option></select></div>
        </div>
        <div class="report-filter-footer">
            <div class="report-filter-summary"><span class="report-mode {{ $filters['mode'] === 'demo' ? 'demo' : '' }}">{{ $filters['mode'] === 'demo' ? 'DEMO' : 'GIAO DỊCH THỰC' }}</span> <strong>{{ !empty($filters['date_from']) ? \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') : 'Từ đầu' }} → {{ !empty($filters['date_to']) ? \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') : 'Hiện tại' }}</strong></div>
            <div class="d-flex flex-wrap" style="gap:8px">@if($hasFilters)<a href="{{ route($filterRoute, ['mode' => $filters['mode']]) }}" class="btn btn-outline-secondary btn-sm">Xóa bộ lọc</a>@endif<button type="submit" class="btn btn-primary btn-sm">Áp dụng bộ lọc</button></div>
        </div>
    </form>
</div>
<p class="report-definition">Doanh thu theo ngày tạo đơn, gồm giá trị đơn đã thanh toán và phí giao hàng; loại trừ đơn hủy, hoàn hàng và hoàn tiền. Số liệu theo danh mục chỉ tính tiền sản phẩm. Khi lọc danh mục, tổng doanh thu vẫn gồm toàn bộ giá trị của đơn chứa danh mục đó.</p>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reportFilters');
    form.querySelectorAll('[data-report-preset]').forEach(button => button.addEventListener('click', () => {
        form.elements.date_from.value = '';
        form.elements.date_to.value = '';
        const preset = document.createElement('input');
        preset.type = 'hidden';
        preset.name = 'preset';
        preset.value = button.dataset.reportPreset;
        form.append(preset);
        form.requestSubmit();
    }));
    document.getElementById('report-date-from').addEventListener('change', function () {
        document.getElementById('report-date-to').min = this.value;
    });
});
</script>
