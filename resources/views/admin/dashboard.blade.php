@extends('layouts.admin')
@section('title', 'Tổng quan')
@section('page_title', 'Tổng quan cửa hàng')
@section('content')
<div class="studio-dashboard">
    <section class="studio-overview-heading">
        <div>
            <span class="studio-eyebrow">TỔNG QUAN VẬN HÀNH · {{ now()->timezone('Asia/Ho_Chi_Minh')->format('d.m.Y') }}</span>
            <h2>Cửa hàng trong tầm tay.</h2>
            <p>Chào {{ Auth::user()->name }}. Đây là những gì đang diễn ra tại Soopi.</p>
        </div>
        <div class="studio-overview-actions">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-light"><i class="fa-solid fa-chart-line mr-2" aria-hidden="true"></i>Xem báo cáo</a>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus mr-2" aria-hidden="true"></i>Thêm sản phẩm</a>
        </div>
    </section>
    <section class="studio-metrics" aria-label="Số liệu tổng quan toàn thời gian">
        @foreach([
            ['label' => 'Giá trị đơn hàng', 'value' => number_format($stats['value'], 0, ',', '.'), 'unit' => '₫', 'note' => 'Toàn thời gian · Không gồm đơn hủy', 'icon' => 'wallet', 'url' => route('admin.finance.index')],
            ['label' => 'Tổng đơn hàng', 'value' => number_format($stats['orders']), 'unit' => '', 'note' => 'Tất cả đơn hàng của cửa hàng', 'icon' => 'bag-shopping', 'url' => route('admin.orders.index')],
            ['label' => 'Cần xử lý', 'value' => number_format($stats['pending']), 'unit' => '', 'note' => 'Chờ xử lý hoặc tạo vận đơn', 'icon' => 'clock', 'url' => route('admin.orders.index', ['tab' => 'pending'])],
            ['label' => 'Bộ sưu tập', 'value' => number_format($stats['products']), 'unit' => '', 'note' => $stats['activeProducts'].' sản phẩm đang được mở bán', 'icon' => 'spray-can-sparkles', 'url' => route('admin.products.index')],
        ] as $metric)
            <a class="studio-metric {{ $metric['icon'] === 'clock' && $stats['pending'] > 0 ? 'studio-metric--attention' : '' }}" href="{{ $metric['url'] }}">
                <div class="studio-metric-top"><span>{{ $metric['label'] }}</span><i class="fa-solid fa-{{ $metric['icon'] }}" aria-hidden="true"></i></div>
                <strong>{{ $metric['value'] }} <small>{{ $metric['unit'] }}</small></strong>
                <span class="studio-metric-note">{{ $metric['note'] }}</span>
            </a>
        @endforeach
    </section>
    <div class="studio-dashboard-grid">
        <section class="studio-panel studio-chart-panel">
            <div class="studio-panel-heading"><div><span class="studio-eyebrow">NHỊP KINH DOANH</span><h3>Giá trị đơn hàng</h3></div>
                <nav class="studio-segment" aria-label="Khoảng thời gian thống kê">
                    @foreach([7, 30] as $period)
                        <a href="{{ route('admin.dashboard', ['days' => $period]) }}" @if($days === $period) aria-current="page" @endif>{{ $period }} ngày</a>
                    @endforeach
                </nav>
            </div>
            <div class="studio-chart-total">{{ number_format($chart->sum('amount'), 0, ',', '.') }} <span>₫</span></div>
            <p class="studio-muted">{{ $chart->sum('count') }} đơn · {{ $chart->first()['date'] }} – {{ $chart->last()['date'] }} · Không gồm đơn hủy</p>
            <div class="studio-bar-chart" role="group" aria-label="Biểu đồ giá trị đơn hàng trong {{ $days }} ngày; chi tiết số liệu ở bảng bên dưới">
                @php($chartMax = max(1, $chart->max('amount')))
                @foreach($chart as $point)
                    <div class="studio-chart-column" tabindex="0" aria-label="{{ $point['date'] }}: {{ number_format($point['amount'], 0, ',', '.') }} đồng, {{ $point['count'] }} đơn">
                        <span class="studio-chart-tooltip" aria-hidden="true">{{ $point['date'] }} · {{ $point['count'] }} đơn<strong>{{ number_format($point['amount'], 0, ',', '.') }} ₫</strong></span>
                        <div class="studio-chart-track"><span style="height:{{ $point['amount'] > 0 ? max(2, round($point['amount'] / $chartMax * 100)) : 0 }}%"></span></div>
                        <span class="studio-chart-label">{{ $days === 7 || $loop->first || $loop->last || $loop->iteration % 5 === 0 ? $point['date'] : '' }}</span>
                    </div>
                @endforeach
            </div>
            @if($chart->sum('count') === 0)<p class="studio-chart-empty">Chưa có đơn hàng trong khoảng thời gian này.</p>@endif
            <details class="studio-chart-details"><summary>Xem số liệu từng ngày</summary><div class="studio-chart-data"><table><thead><tr><th>Ngày</th><th>Số đơn</th><th>Giá trị</th></tr></thead><tbody>@foreach($chart as $point)<tr><td>{{ $point['date'] }}</td><td>{{ $point['count'] }}</td><td>{{ number_format($point['amount'], 0, ',', '.') }} ₫</td></tr>@endforeach</tbody></table></div></details>
        </section>
        <section class="studio-panel studio-tasks">
            <div class="studio-panel-heading"><div><span class="studio-eyebrow">TẬP TRUNG HÔM NAY</span><h3>Góc vận hành</h3></div><i class="fa-solid fa-sliders" aria-hidden="true"></i></div>
            <a class="studio-task" href="{{ route('admin.orders.index', ['tab' => 'pending']) }}"><span class="studio-task-icon"><i class="fa-solid fa-box" aria-hidden="true"></i></span><span><strong>Đơn hàng cần xử lý</strong><small>{{ $stats['pending'] }} đơn đang chờ bạn</small></span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            <a class="studio-task" href="{{ route('admin.livestreams.index') }}"><span class="studio-task-icon"><i class="fa-solid fa-video" aria-hidden="true"></i></span><span><strong>Không gian livestream</strong><small>Lên lịch, phát sóng và tư vấn</small></span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            <a class="studio-task" href="{{ route('admin.coupons.index') }}"><span class="studio-task-icon"><i class="fa-solid fa-ticket" aria-hidden="true"></i></span><span><strong>Ưu đãi cho khách hàng</strong><small>Quản lý các mã khuyến mãi</small></span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            <p class="studio-ops-note">Số liệu tổng quan được tính trên toàn bộ dữ liệu cửa hàng. Mở Báo cáo để lọc thời gian hoặc tách đơn mô phỏng và đơn thực tế.</p>
        </section>
        <section class="studio-panel studio-recent">
            <div class="studio-panel-heading"><div><span class="studio-eyebrow">CẬP NHẬT MỚI</span><h3>Đơn hàng gần đây</h3></div><a class="studio-text-link" href="{{ route('admin.orders.index') }}">Tất cả <span aria-hidden="true">↗</span></a></div>
            <div class="studio-recent-list">
                @forelse($recentOrders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="studio-recent-row">
                        <span class="studio-order-mark"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></span>
                        <span class="studio-recent-person"><strong>{{ $order->name ?? 'Khách hàng' }}</strong><small>#DH{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }} · {{ $order->created_at->format('d/m · H:i') }}</small></span>
                        <span class="studio-recent-value"><strong>{{ number_format($order->total_price, 0, ',', '.') }} ₫</strong><small>{{ ['pending' => 'Chờ xử lý', 'confirmed' => 'Đã xác nhận', 'paid' => 'Đã thanh toán', 'paid_momo' => 'Đã thanh toán', 'cod_ordered' => 'Đơn COD', 'cod_paid' => 'Đã thu COD', 'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy'][$order->status] ?? $order->status }}</small></span><span aria-hidden="true">↗</span>
                    </a>
                @empty
                    <p class="studio-empty">Chưa có đơn hàng. Những đơn hàng đầu tiên sẽ xuất hiện tại đây.</p>
                @endforelse
            </div>
        </section>
        <section class="studio-panel studio-stock">
            <div class="studio-panel-heading"><div><span class="studio-eyebrow">CHĂM SÓC BỘ SƯU TẬP</span><h3>Lưu ý tồn kho</h3></div><i class="fa-solid fa-spray-can-sparkles" aria-hidden="true"></i></div>
            <p class="studio-muted">Sản phẩm đang bán còn tối đa 5 chai nguyên dung tích.</p>
            @forelse($lowStock as $product)
                <a class="studio-stock-row" href="{{ route('admin.products.edit', $product) }}"><span><strong>{{ $product->name }}</strong><small>{{ $product->brand }} · {{ $product->volume_ml }} ml</small></span><b>{{ $product->stock }}</b></a>
            @empty
                <div class="studio-stock-clear"><i class="fa-solid fa-check" aria-hidden="true"></i><strong>Tồn kho đang ổn</strong><p>Chưa có sản phẩm nào dưới ngưỡng cảnh báo này.</p></div>
            @endforelse
            <a href="{{ route('admin.products.index') }}" class="studio-text-link">Quản lý bộ sưu tập <span aria-hidden="true">→</span></a>
        </section>
    </div>
    <footer class="studio-dashboard-footer"><img src="{{ asset('images/brand/soopi-petal-logo.png') }}" width="100" height="37" alt="SOOPI · Perfume Studio" style="display:block;width:100px;height:auto"> <span>Chăm chút mỗi ngày. Phát triển dài lâu.</span></footer>
</div>
@endsection
