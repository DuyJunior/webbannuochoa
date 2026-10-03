@extends('layouts.admin')

@section('title', __('Quản lý livestream'))
@section('page_title', __('Livestream nước hoa'))
@section('styles')
<link rel="stylesheet" href="{{ asset('css/livestream-admin.css') }}">
@endsection

@section('content')
<div class="live-admin">
    <header class="live-admin-hero">
        <div>
            <p class="live-admin-kicker">{{ __('Soopi · Studio trực tiếp') }}</p>
            <h2>{{ __('Quản lý buổi livestream') }}</h2>
            <p>{{ __('Lên lịch, giới thiệu nước hoa và phát trực tiếp ngay trên website.') }}</p>
        </div>
        <div class="live-admin-actions">
            <a class="live-admin-btn" href="{{ route('livestream.show') }}" target="_blank" rel="noopener noreferrer">{{ __('Trang khách xem') }} @include('partials.icon', ['name' => 'external', 'size' => '1em'])</a>
            <a class="live-admin-btn live-admin-btn--primary" href="{{ route('admin.livestreams.create', ['mode' => 'now']) }}">@include('partials.icon', ['name' => 'broadcast', 'size' => '1em']) Livestream ngay</a>
            <a class="live-admin-btn" href="{{ route('admin.livestreams.create', ['mode' => 'schedule']) }}">{{ __('◷ Đặt lịch phát') }}</a>
        </div>
    </header>


    @if(!$livekitConfigured)
        <div class="alert alert-warning">{{ __('Phát bằng camera trên web cần cấu hình máy chủ LiveKit trước khi lên sóng. Các buổi YouTube vẫn hoạt động bình thường.') }}</div>
    @endif

    <div class="live-admin-metrics" aria-label="{{ __('Tổng quan livestream') }}">
        <div class="live-admin-metric"><span>{{ __('Đang phát') }}</span><strong>{{ $stats['live'] }}</strong></div>
        <div class="live-admin-metric"><span>{{ __('Sắp phát') }}</span><strong>{{ $stats['scheduled'] }}</strong></div>
        <div class="live-admin-metric"><span>{{ __('Đã kết thúc') }}</span><strong>{{ $stats['ended'] }}</strong></div>
    </div>

    <section class="live-revenue-panel" aria-labelledby="live-revenue-title">
        <div class="live-admin-panel-heading"><div><span class="live-admin-kicker">{{ __('Kết quả bán hàng') }}</span><h3 id="live-revenue-title">{{ __('Doanh thu từ livestream') }}</h3></div><small>{{ __('Chỉ tính sản phẩm khách mở từ live trong đơn đã thu tiền; trừ ưu đãi, không gồm phí giao hàng.') }}</small></div>
        <div class="live-revenue-grid">
            <div><span>{{ __('Hôm nay') }}</span><strong>{{ number_format($revenuePeriods['today']['revenue'], 0, ',', '.') }}{{ __('đ') }}</strong><small>{{ $revenuePeriods['today']['orders'] }} {{ __('đơn đã thu ·') }} {{ $revenuePeriods['today']['pending_orders'] }} {{ __('đơn chờ thu') }}</small></div>
            <div><span>{{ __('7 ngày qua') }}</span><strong>{{ number_format($revenuePeriods['week']['revenue'], 0, ',', '.') }}{{ __('đ') }}</strong><small>{{ $revenuePeriods['week']['orders'] }} {{ __('đơn đã thu ·') }} {{ $revenuePeriods['week']['pending_orders'] }} {{ __('đơn chờ thu') }}</small></div>
            <div><span>{{ __('30 ngày qua') }}</span><strong>{{ number_format($revenuePeriods['month']['revenue'], 0, ',', '.') }}{{ __('đ') }}</strong><small>{{ $revenuePeriods['month']['orders'] }} {{ __('đơn đã thu ·') }} {{ $revenuePeriods['month']['pending_orders'] }} {{ __('đơn chờ thu') }}</small></div>
        </div>
        <p>{{ __('Giá trị đơn đang chờ thu:') }} {{ number_format($revenuePeriods['month']['pending_value'], 0, ',', '.') }}{{ __('đ trong 30 ngày qua. Thống kê theo ngày đặt hàng (giờ Việt Nam); đơn bị hủy hoặc hoàn tiền không được cộng.') }}</p>
    </section>

    @if($onAir)
        <div class="live-admin-current" role="status">
            <div><strong>● {{ $onAir->isBrowserOnAir() || $onAir->source === 'youtube' ? __('Đang phát') : __('Mất kết nối') }}: {{ $onAir->title }}</strong><p>{{ $onAir->isBrowserOnAir() || $onAir->source === 'youtube' ? __('Khách có thể xem video trên trang livestream.') : __('Nhân viên cần kết nối lại từ studio.') }}</p></div>
            <a href="{{ $onAir->source === 'browser' ? route('admin.livestreams.studio', $onAir) : route('livestream.show') }}" class="live-admin-btn">{{ $onAir->source === 'browser' ? __('Vào studio') : __('Xem buổi live') }} @include('partials.icon', ['name' => 'external', 'size' => '1em'])</a>
        </div>
    @endif

    <section class="live-admin-panel" aria-labelledby="live-list-title">
        <div class="live-admin-panel-heading"><h3 id="live-list-title">{{ __('Danh sách buổi phát') }}</h3><small>{{ $livestreams->total() }} {{ __('buổi') }}</small></div>
        <form method="GET" action="{{ route('admin.livestreams.index') }}" class="studio-filter-bar mb-4">
            <div class="form-row align-items-end">
                <div class="col-md-6 mb-2"><label for="livestream-search" class="small font-weight-bold">{{ __('Tìm buổi phát') }}</label><input id="livestream-search" class="form-control" type="search" name="search" maxlength="255" value="{{ request('search') }}" placeholder="{{ __('Tên buổi livestream…') }}"></div>
                <div class="col-md-3 mb-2"><label for="livestream-status" class="small font-weight-bold">{{ __('Trạng thái') }}</label><select id="livestream-status" class="form-control" name="status"><option value="">{{ __('Tất cả trạng thái') }}</option>@foreach(['live' => __('Đang phát'), 'scheduled' => __('Sắp phát'), 'ended' => __('Đã kết thúc')] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ __($label) }}</option>@endforeach</select></div>
                <div class="col-md-3 mb-2 d-flex gap-2"><button type="submit" class="btn btn-primary">{{ __('Lọc buổi phát') }}</button>@if(request()->filled('search') || request()->filled('status'))<a href="{{ route('admin.livestreams.index') }}" class="btn btn-outline-secondary">{{ __('Xóa lọc') }}</a>@endif</div>
            </div>
        </form>
        @if($livestreams->isEmpty())
            <div class="live-admin-empty">
                <i class="fa-solid fa-video" aria-hidden="true"></i>
                <strong>{{ request()->filled('search') || request()->filled('status') ? __('Không tìm thấy buổi phát phù hợp') : __('Chưa có buổi livestream') }}</strong>
                <p>{{ request()->filled('search') || request()->filled('status') ? __('Thử từ khóa khác hoặc xóa bộ lọc để xem tất cả buổi phát.') : __('Tạo buổi phát bằng camera/micro ngay trên web hoặc dùng YouTube Live.') }}</p>
                @if(request()->filled('search') || request()->filled('status'))<a href="{{ route('admin.livestreams.index') }}" class="live-admin-btn">{{ __('Xóa bộ lọc') }}</a>@else<a href="{{ route('admin.livestreams.create') }}" class="live-admin-btn live-admin-btn--primary">{{ __('Tạo buổi live →') }}</a>@endif
            </div>
        @else
            <div class="table-responsive">
                <table class="live-admin-table">
                    <thead><tr><th>{{ __('Buổi phát') }}</th><th>{{ __('Thời gian') }}</th><th>{{ __('Trạng thái') }}</th><th>{{ __('Sản phẩm') }}</th><th>{{ __('Thao tác') }}</th></tr></thead>
                    <tbody>
                    @foreach($livestreams as $livestream)
                        <tr>
                            <td><strong>{{ $livestream->title }}</strong><small>{{ $livestream->source === 'browser' ? __('Camera trên web') : 'YouTube' }} · {{ $livestream->creator?->name ?? 'Soopi' }}</small></td>
                            <td>{{ $livestream->starts_at?->format('H:i · d/m/Y') ?? __('Chưa đặt lịch') }}</td>
                            <td>
                                @if($livestream->isOverdue())
                                    <span class="live-admin-badge live-admin-badge--overdue">{{ __('◷ Đã tới giờ · chưa phát') }}</span>
                                @elseif($livestream->source === 'browser' && $livestream->status === 'live' && !$livestream->isBrowserOnAir())
                                    <span class="live-admin-badge live-admin-badge--overdue">{{ __('Mất kết nối') }}</span>
                                @else
                                    <span class="live-admin-badge {{ $livestream->status === 'live' ? 'live-admin-badge--on' : ($livestream->status === 'ended' ? 'live-admin-badge--ended' : '') }}">{{ ['live' => __('● Đang phát'), 'scheduled' => __('◷ Sắp phát'), 'ended' => __('Đã kết thúc')][$livestream->status] ?? $livestream->status }}</span>
                                @endif
                            </td>
                            <td><strong>{{ $livestream->products->count() }} {{ __('sản phẩm') }}</strong><small>{{ __('Đã thu:') }} {{ number_format($liveRevenue['by_stream'][$livestream->id]['revenue'] ?? 0, 0, ',', '.') }}{{ __('đ ·') }} {{ $liveRevenue['by_stream'][$livestream->id]['orders'] ?? 0 }} {{ __('đơn') }}</small><small>{{ __('Chờ thu:') }} {{ number_format($liveRevenue['by_stream'][$livestream->id]['pending_value'] ?? 0, 0, ',', '.') }}{{ __('đ ·') }} {{ $liveRevenue['by_stream'][$livestream->id]['pending_orders'] ?? 0 }} {{ __('đơn') }}</small></td>
                            <td>
                                <div class="live-admin-table-actions">
                                    @if($livestream->source === 'browser')
                                        @if($livestream->status !== 'ended')
                                            <a class="live-admin-btn live-admin-btn--primary" href="{{ route('admin.livestreams.studio', $livestream) }}">{{ $livestream->status === 'scheduled' ? 'Livestream ngay' : __('Vào studio') }}</a>
                                        @endif
                                        @if($livestream->status === 'live')
                                            <form method="POST" action="{{ route('admin.livestreams.status', $livestream) }}" data-confirm="Kết thúc buổi phát {{ $livestream->title }}? Khách hàng sẽ không còn xem được phiên trực tiếp này.">@csrf @method('PATCH')<input type="hidden" name="status" value="ended"><button class="live-admin-btn" type="submit">{{ __('Kết thúc') }}</button></form>
                                        @endif
                                    @elseif($livestream->status === 'scheduled')
                                        <form method="POST" action="{{ route('admin.livestreams.status', $livestream) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="live"><button class="live-admin-btn live-admin-btn--primary" type="submit">{{ __('Phát ngay') }}</button></form>
                                    @elseif($livestream->status === 'live')
                                        <form method="POST" action="{{ route('admin.livestreams.status', $livestream) }}" data-confirm="Kết thúc buổi phát {{ $livestream->title }}? Khách hàng sẽ không còn xem được phiên trực tiếp này.">@csrf @method('PATCH')<input type="hidden" name="status" value="ended"><button class="live-admin-btn" type="submit">{{ __('Kết thúc') }}</button></form>
                                    @endif
                                    <a class="live-admin-btn" href="{{ route('admin.livestreams.edit', $livestream) }}">{{ __('Sửa') }}</a>
                                    <a class="live-admin-btn" href="{{ route('admin.livestreams.report', $livestream) }}">Doanh thu</a>
                                    <form method="POST" action="{{ route('admin.livestreams.destroy', $livestream) }}" data-confirm="Xóa buổi livestream {{ $livestream->title }}? Nội dung đã xóa sẽ không thể khôi phục.">@csrf @method('DELETE')<button type="submit" class="live-admin-btn live-admin-btn--danger" aria-label="Xóa buổi phát {{ $livestream->title }}">{{ __('Xóa') }}</button></form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4"><small class="text-muted">{{ __('Hiển thị') }} {{ $livestreams->firstItem() }}–{{ $livestreams->lastItem() }} / {{ $livestreams->total() }} {{ __('buổi phát') }}</small>{{ $livestreams->links() }}</div>
        @endif
    </section>
</div>
@endsection
