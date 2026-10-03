@extends('layouts.admin')
@section('title', __('Tổng quan'))
@section('page_title', __('Tổng quan cửa hàng'))
@section('content')
<div class="studio-dashboard">
    <section class="studio-overview-heading">
        <div>
            <span class="studio-eyebrow">{{ __('TỔNG QUAN VẬN HÀNH ·') }} {{ now()->timezone('Asia/Ho_Chi_Minh')->format('d.m.Y') }}</span>
            <h2>{{ __('Cửa hàng trong tầm tay.') }}</h2>
            <p>{{ __('Chào') }} {{ Auth::user()->name }}{{ __('. Đây là những gì đang diễn ra tại Soopi.') }}</p>
        </div>
        <div class="studio-overview-actions">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-light"><i class="fa-solid fa-chart-line mr-2" aria-hidden="true"></i>{{ __('Xem báo cáo') }}</a>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus mr-2" aria-hidden="true"></i>{{ __('Thêm sản phẩm') }}</a>
        </div>
    </section>
    <section class="studio-metrics" aria-label="{{ __('Số liệu tổng quan toàn thời gian') }}">
        @foreach([
            ['label' => __('Giá trị đơn hàng'), 'value' => number_format($stats['value'], 0, ',', '.'), 'unit' => '₫', 'note' => __('Toàn thời gian · Không gồm đơn hủy'), 'icon' => 'wallet', 'url' => route('admin.finance.index')],
            ['label' => __('Tổng đơn hàng'), 'value' => number_format($stats['orders']), 'unit' => '', 'note' => __('Tất cả đơn hàng của cửa hàng'), 'icon' => 'bag-shopping', 'url' => route('admin.orders.index')],
            ['label' => __('Cần xử lý'), 'value' => number_format($stats['pending']), 'unit' => '', 'note' => __('Chờ xử lý hoặc tạo vận đơn'), 'icon' => 'clock', 'url' => route('admin.orders.index', ['tab' => 'pending'])],
            ['label' => __('Bộ sưu tập'), 'value' => number_format($stats['products']), 'unit' => '', 'note' => $stats['activeProducts'].__(' sản phẩm đang được mở bán'), 'icon' => 'spray-can-sparkles', 'url' => route('admin.products.index')],
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
            <div class="studio-panel-heading"><div><span class="studio-eyebrow">{{ __('NHỊP KINH DOANH') }}</span><h3>{{ __('Giá trị đơn hàng') }}</h3></div>
                <nav class="studio-segment" aria-label="{{ __('Khoảng thời gian thống kê') }}">
                    @foreach([7, 30] as $period)
                        <a href="{{ route('admin.dashboard', ['days' => $period]) }}" @if($days === $period) aria-current="page" @endif>{{ $period }} {{ __('ngày') }}</a>
                    @endforeach
                </nav>
            </div>
            <div class="studio-chart-total">{{ number_format($chart->sum('amount'), 0, ',', '.') }} <span>₫</span></div>
            <p class="studio-muted">{{ $chart->sum('count') }} {{ __('đơn ·') }} {{ $chart->first()['date'] }} – {{ $chart->last()['date'] }} {{ __('· Không gồm đơn hủy') }}</p>
            <div class="studio-bar-chart" role="group" aria-label="Biểu đồ giá trị đơn hàng trong {{ $days }} ngày; chi tiết số liệu ở bảng bên dưới">
                @php($chartMax = max(1, $chart->max('amount')))
                @foreach($chart as $point)
                    <div class="studio-chart-column" tabindex="0" aria-label="{{ $point['date'] }}: {{ number_format($point['amount'], 0, ',', '.') }} đồng, {{ $point['count'] }} đơn">
                        <span class="studio-chart-tooltip" aria-hidden="true">{{ $point['date'] }} · {{ $point['count'] }} {{ __('đơn') }}<strong>{{ number_format($point['amount'], 0, ',', '.') }} ₫</strong></span>
                        <div class="studio-chart-track"><span style="height:{{ $point['amount'] > 0 ? max(2, round($point['amount'] / $chartMax * 100)) : 0 }}%"></span></div>
                        <span class="studio-chart-label">{{ $days === 7 || $loop->first || $loop->last || $loop->iteration % 5 === 0 ? $point['date'] : '' }}</span>
                    </div>
                @endforeach
            </div>
            @if($chart->sum('count') === 0)<p class="studio-chart-empty">{{ __('Chưa có đơn hàng trong khoảng thời gian này.') }}</p>@endif
            <details class="studio-chart-details"><summary>{{ __('Xem số liệu từng ngày') }}</summary><div class="studio-chart-data"><table><thead><tr><th>{{ __('Ngày') }}</th><th>{{ __('Số đơn') }}</th><th>{{ __('Giá trị') }}</th></tr></thead><tbody>@foreach($chart as $point)<tr><td>{{ $point['date'] }}</td><td>{{ $point['count'] }}</td><td>{{ number_format($point['amount'], 0, ',', '.') }} ₫</td></tr>@endforeach</tbody></table></div></details>
        </section>
        <section class="studio-panel studio-tasks">
            <div class="studio-panel-heading"><div><span class="studio-eyebrow">{{ __('TẬP TRUNG HÔM NAY') }}</span><h3>{{ __('Góc vận hành') }}</h3></div><i class="fa-solid fa-sliders" aria-hidden="true"></i></div>
            <a class="studio-task" href="{{ route('admin.orders.index', ['tab' => 'pending']) }}"><span class="studio-task-icon"><i class="fa-solid fa-box" aria-hidden="true"></i></span><span><strong>{{ __('Đơn hàng cần xử lý') }}</strong><small>{{ $stats['pending'] }} {{ __('đơn đang chờ bạn') }}</small></span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            <a class="studio-task" href="{{ route('admin.livestreams.index') }}"><span class="studio-task-icon"><i class="fa-solid fa-video" aria-hidden="true"></i></span><span><strong>{{ __('Không gian livestream') }}</strong><small>{{ __('Lên lịch, phát sóng và tư vấn') }}</small></span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            <a class="studio-task" href="{{ route('admin.coupons.index') }}"><span class="studio-task-icon"><i class="fa-solid fa-ticket" aria-hidden="true"></i></span><span><strong>{{ __('Ưu đãi cho khách hàng') }}</strong><small>{{ __('Quản lý các mã khuyến mãi') }}</small></span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            <p class="studio-ops-note">{{ __('Số liệu tổng quan được tính trên toàn bộ dữ liệu cửa hàng. Mở Báo cáo để lọc thời gian hoặc tách đơn mô phỏng và đơn thực tế.') }}</p>
        </section>
        <section class="studio-panel studio-recent">
            <div class="studio-panel-heading"><div><span class="studio-eyebrow">{{ __('CẬP NHẬT MỚI') }}</span><h3>{{ __('Đơn hàng gần đây') }}</h3></div><a class="studio-text-link" href="{{ route('admin.orders.index') }}">{{ __('Tất cả') }} <span aria-hidden="true">↗</span></a></div>
            <div class="studio-recent-list">
                @forelse($recentOrders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="studio-recent-row">
                        <span class="studio-order-mark"><i class="fa-solid fa-bag-shopping" aria-hidden="true"></i></span>
                        <span class="studio-recent-person"><strong>{{ $order->name ?? __('Khách hàng') }}</strong><small>#DH{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }} · {{ $order->created_at->format('d/m · H:i') }}</small></span>
                        <span class="studio-recent-value"><strong>{{ number_format($order->total_price, 0, ',', '.') }} ₫</strong><small>{{ ['pending' => __('Chờ xử lý'), 'confirmed' => __('Đã xác nhận'), 'paid' => __('Đã thanh toán'), 'paid_momo' => __('Đã thanh toán'), 'cod_ordered' => __('Đơn COD'), 'cod_paid' => __('Đã thu COD'), 'completed' => __('Hoàn thành'), 'cancelled' => __('Đã hủy')][$order->status] ?? $order->status }}</small></span><span aria-hidden="true">↗</span>
                    </a>
                @empty
                    <p class="studio-empty">{{ __('Chưa có đơn hàng. Những đơn hàng đầu tiên sẽ xuất hiện tại đây.') }}</p>
                @endforelse
            </div>
        </section>
        <section class="studio-panel studio-stock">
            <div class="studio-panel-heading"><div><span class="studio-eyebrow">{{ __('CHĂM SÓC BỘ SƯU TẬP') }}</span><h3>{{ __('Lưu ý tồn kho') }}</h3></div><i class="fa-solid fa-spray-can-sparkles" aria-hidden="true"></i></div>
            <p class="studio-muted">{{ __('Sản phẩm đang bán còn tối đa 5 chai nguyên dung tích.') }}</p>
            @forelse($lowStock as $product)
                <a class="studio-stock-row" href="{{ route('admin.products.edit', $product) }}"><span><strong>{{ $product->name }}</strong><small>{{ $product->brand }} · {{ $product->volume_ml }} ml</small></span><b>{{ $product->stock }}</b></a>
            @empty
                <div class="studio-stock-clear"><i class="fa-solid fa-check" aria-hidden="true"></i><strong>{{ __('Tồn kho đang ổn') }}</strong><p>{{ __('Chưa có sản phẩm nào dưới ngưỡng cảnh báo này.') }}</p></div>
            @endforelse
            <a href="{{ route('admin.products.index') }}" class="studio-text-link">{{ __('Quản lý bộ sưu tập') }} <span aria-hidden="true">→</span></a>
        </section>
    </div>
    <footer class="studio-dashboard-footer"><img src="{{ asset('images/brand/soopi-petal-logo.png') }}" width="100" height="37" alt="SOOPI · Perfume Studio" style="display:block;width:100px;height:auto"> <span>{{ __('Chăm chút mỗi ngày. Phát triển dài lâu.') }}</span></footer>
</div>
@endsection
