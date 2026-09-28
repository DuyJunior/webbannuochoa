@extends('layouts.admin')

@section('title', 'Quản lý livestream')
@section('page_title', 'Livestream nước hoa')
@section('styles')
<link rel="stylesheet" href="{{ asset('css/livestream-admin.css') }}">
@endsection

@section('content')
<div class="live-admin">
    <header class="live-admin-hero">
        <div>
            <p class="live-admin-kicker">Soopi · Studio trực tiếp</p>
            <h2>Quản lý buổi livestream</h2>
            <p>Lên lịch, giới thiệu nước hoa và phát trực tiếp ngay trên website.</p>
        </div>
        <div class="live-admin-actions">
            <a class="live-admin-btn" href="{{ route('livestream.show') }}" target="_blank" rel="noopener noreferrer">Trang khách xem ↗</a>
            <a class="live-admin-btn live-admin-btn--primary" href="{{ route('admin.livestreams.create', ['mode' => 'now']) }}">🔴 Livestream ngay</a>
            <a class="live-admin-btn" href="{{ route('admin.livestreams.create', ['mode' => 'schedule']) }}">◷ Đặt lịch phát</a>
        </div>
    </header>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Chưa thể đổi trạng thái buổi phát:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if(!$livekitConfigured)
        <div class="alert alert-warning">Phát bằng camera trên web cần cấu hình máy chủ LiveKit trước khi lên sóng. Các buổi YouTube vẫn hoạt động bình thường.</div>
    @endif

    <div class="live-admin-metrics" aria-label="Tổng quan livestream">
        <div class="live-admin-metric"><span>Đang phát</span><strong>{{ $stats['live'] }}</strong></div>
        <div class="live-admin-metric"><span>Sắp phát</span><strong>{{ $stats['scheduled'] }}</strong></div>
        <div class="live-admin-metric"><span>Đã kết thúc</span><strong>{{ $stats['ended'] }}</strong></div>
    </div>

    <section class="live-revenue-panel" aria-labelledby="live-revenue-title">
        <div class="live-admin-panel-heading"><div><span class="live-admin-kicker">Kết quả bán hàng</span><h3 id="live-revenue-title">Doanh thu từ livestream</h3></div><small>Chỉ tính sản phẩm khách mở từ live trong đơn đã thu tiền; trừ ưu đãi, không gồm phí giao hàng.</small></div>
        <div class="live-revenue-grid">
            <div><span>Hôm nay</span><strong>{{ number_format($revenuePeriods['today']['revenue'], 0, ',', '.') }}đ</strong><small>{{ $revenuePeriods['today']['orders'] }} đơn đã thu · {{ $revenuePeriods['today']['pending_orders'] }} đơn chờ thu</small></div>
            <div><span>7 ngày qua</span><strong>{{ number_format($revenuePeriods['week']['revenue'], 0, ',', '.') }}đ</strong><small>{{ $revenuePeriods['week']['orders'] }} đơn đã thu · {{ $revenuePeriods['week']['pending_orders'] }} đơn chờ thu</small></div>
            <div><span>30 ngày qua</span><strong>{{ number_format($revenuePeriods['month']['revenue'], 0, ',', '.') }}đ</strong><small>{{ $revenuePeriods['month']['orders'] }} đơn đã thu · {{ $revenuePeriods['month']['pending_orders'] }} đơn chờ thu</small></div>
        </div>
        <p>Giá trị đơn đang chờ thu: {{ number_format($revenuePeriods['month']['pending_value'], 0, ',', '.') }}đ trong 30 ngày qua. Thống kê theo ngày đặt hàng (giờ Việt Nam); đơn bị hủy hoặc hoàn tiền không được cộng.</p>
    </section>

    @if($onAir)
        <div class="live-admin-current" role="status">
            <div><strong>● {{ $onAir->isBrowserOnAir() || $onAir->source === 'youtube' ? 'Đang phát' : 'Mất kết nối' }}: {{ $onAir->title }}</strong><p>{{ $onAir->isBrowserOnAir() || $onAir->source === 'youtube' ? 'Khách có thể xem video trên trang livestream.' : 'Nhân viên cần kết nối lại từ studio.' }}</p></div>
            <a href="{{ $onAir->source === 'browser' ? route('admin.livestreams.studio', $onAir) : route('livestream.show') }}" class="live-admin-btn">{{ $onAir->source === 'browser' ? 'Vào studio' : 'Xem buổi live' }} ↗</a>
        </div>
    @endif

    <section class="live-admin-panel" aria-labelledby="live-list-title">
        <div class="live-admin-panel-heading"><h3 id="live-list-title">Tất cả buổi phát</h3><small>{{ $livestreams->total() }} buổi</small></div>
        @if($livestreams->isEmpty())
            <div class="live-admin-empty">
                <i class="fa-solid fa-video" aria-hidden="true"></i>
                <strong>Chưa có buổi livestream</strong>
                <p>Tạo buổi phát bằng camera/micro ngay trên web hoặc dùng YouTube Live.</p>
                <a href="{{ route('admin.livestreams.create') }}" class="live-admin-btn live-admin-btn--primary">Tạo buổi live →</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="live-admin-table">
                    <thead><tr><th>Buổi phát</th><th>Thời gian</th><th>Trạng thái</th><th>Sản phẩm</th><th>Thao tác</th></tr></thead>
                    <tbody>
                    @foreach($livestreams as $livestream)
                        <tr>
                            <td><strong>{{ $livestream->title }}</strong><small>{{ $livestream->source === 'browser' ? 'Camera trên web' : 'YouTube' }} · {{ $livestream->creator?->name ?? 'Soopi' }}</small></td>
                            <td>{{ $livestream->starts_at?->format('H:i · d/m/Y') ?? 'Chưa đặt lịch' }}</td>
                            <td>
                                @if($livestream->isOverdue())
                                    <span class="live-admin-badge live-admin-badge--overdue">◷ Đã tới giờ · chưa phát</span>
                                @else
                                    <span class="live-admin-badge {{ $livestream->status === 'live' ? 'live-admin-badge--on' : ($livestream->status === 'ended' ? 'live-admin-badge--ended' : '') }}">{{ ['live' => '● Đang phát', 'scheduled' => '◷ Sắp phát', 'ended' => 'Đã kết thúc'][$livestream->status] ?? $livestream->status }}</span>
                                @endif
                            </td>
                            <td><strong>{{ $livestream->products->count() }} sản phẩm</strong><small>Đã thu: {{ number_format($liveRevenue['by_stream'][$livestream->id]['revenue'] ?? 0, 0, ',', '.') }}đ · {{ $liveRevenue['by_stream'][$livestream->id]['orders'] ?? 0 }} đơn</small><small>Chờ thu: {{ number_format($liveRevenue['by_stream'][$livestream->id]['pending_value'] ?? 0, 0, ',', '.') }}đ · {{ $liveRevenue['by_stream'][$livestream->id]['pending_orders'] ?? 0 }} đơn</small></td>
                            <td>
                                <div class="live-admin-table-actions">
                                    @if($livestream->source === 'browser')
                                        @if($livestream->status !== 'ended')
                                            <a class="live-admin-btn live-admin-btn--primary" href="{{ route('admin.livestreams.studio', $livestream) }}">{{ $livestream->status === 'scheduled' ? 'Livestream ngay' : 'Vào studio' }}</a>
                                        @endif
                                        @if($livestream->status === 'live')
                                            <form method="POST" action="{{ route('admin.livestreams.status', $livestream) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="ended"><button class="live-admin-btn" type="submit">Kết thúc</button></form>
                                        @endif
                                    @elseif($livestream->status === 'scheduled')
                                        <form method="POST" action="{{ route('admin.livestreams.status', $livestream) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="live"><button class="live-admin-btn live-admin-btn--primary" type="submit">Phát ngay</button></form>
                                    @elseif($livestream->status === 'live')
                                        <form method="POST" action="{{ route('admin.livestreams.status', $livestream) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="ended"><button class="live-admin-btn" type="submit">Kết thúc</button></form>
                                    @endif
                                    <a class="live-admin-btn" href="{{ route('admin.livestreams.edit', $livestream) }}">Sửa</a>
                                    <a class="live-admin-btn" href="{{ route('admin.livestreams.report', $livestream) }}">Doanh thu</a>
                                    <form method="POST" action="{{ route('admin.livestreams.destroy', $livestream) }}" onsubmit="return confirm('Xóa buổi livestream này?')">@csrf @method('DELETE')<button type="submit" class="live-admin-btn live-admin-btn--danger">Xóa</button></form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $livestreams->links() }}</div>
        @endif
    </section>
</div>
@endsection
