@extends('layouts.admin')

@section('title', 'Quản lý mã ưu đãi')
@section('page_title', 'Mã ưu đãi')

@section('content')
@php
    $inputValue = function ($field, $default = '') {
        $value = old($field, $default);
        return is_scalar($value) || $value === null ? $value : $default;
    };
@endphp
<div class="admin-coupons-page">
    <details class="admin-card" id="coupon-create" @if($errors->any()) open @endif>
        <summary class="font-weight-bold">Tạo mã ưu đãi mới <span class="text-muted small ml-2">Thiết lập mức giảm và điều kiện sử dụng</span></summary>
        <form class="mt-4" method="POST" action="{{ route('admin.coupons.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="coupon-code" class="font-weight-bold small">Mã ưu đãi <span class="text-danger">*</span></label>
                    <input id="coupon-code" class="form-control @error('code') is-invalid @enderror" name="code" maxlength="30" pattern="[A-Za-z0-9_-]+" value="{{ $inputValue('code') }}" placeholder="VD: SOOPI50K" required autocapitalize="characters" spellcheck="false" @error('code') aria-invalid="true" aria-describedby="coupon-code-error" @enderror>
                    @error('code')<div id="coupon-code-error" class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text text-muted">Chữ, số, dấu gạch ngang hoặc gạch dưới; tối đa 30 ký tự.</small>
                </div>
                <div class="col-md-4 form-group">
                    <label for="coupon-type" class="font-weight-bold small">Loại giảm giá <span class="text-danger">*</span></label>
                    <select id="coupon-type" class="form-control @error('type') is-invalid @enderror" name="type" required><option value="fixed" @selected(old('type', 'fixed') === 'fixed')>Giảm số tiền (₫)</option><option value="percent" @selected(old('type') === 'percent')>Giảm theo phần trăm (%)</option></select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 form-group">
                    <label for="coupon-value" class="font-weight-bold small">Giá trị giảm <span class="text-danger">*</span></label>
                    <input id="coupon-value" class="form-control @error('value') is-invalid @enderror" name="value" type="number" min="1" max="4294967295" step="1" value="{{ $inputValue('value') }}" placeholder="VD: 50000 hoặc 15" required aria-describedby="coupon-value-hint @error('value') coupon-value-error @enderror" @error('value') aria-invalid="true" @enderror>
                    @error('value')<div id="coupon-value-error" class="invalid-feedback">{{ $message }}</div>@enderror
                    <small id="coupon-value-hint" class="form-text text-muted">Mức giảm phần trăm tối đa là 100%.</small>
                </div>
                @foreach([
                    ['minimum_order', 'Đơn hàng tối thiểu (₫)', 'number', 0, 'Nhập 0 nếu không yêu cầu giá trị đơn tối thiểu.'],
                    ['usage_limit', 'Giới hạn lượt dùng', 'number', null, 'Để trống nếu không giới hạn lượt dùng.'],
                    ['starts_at', 'Bắt đầu áp dụng (UTC)', 'datetime-local', null, 'Giờ Việt Nam bằng UTC + 7 giờ. Để trống để có hiệu lực ngay.'],
                    ['expires_at', 'Hết hạn vào lúc (UTC)', 'datetime-local', null, 'Giờ Việt Nam bằng UTC + 7 giờ. Để trống nếu không đặt ngày hết hạn.'],
                ] as [$field, $label, $type, $default, $hint])
                    <div class="col-md-4 form-group">
                        <label for="coupon-{{ $field }}" class="font-weight-bold small">{{ $label }} @if($field === 'minimum_order')<span class="text-danger">*</span>@endif</label>
                        <input id="coupon-{{ $field }}" class="form-control @error($field) is-invalid @enderror" name="{{ $field }}" type="{{ $type }}" value="{{ $inputValue($field, $default) }}" @if($type === 'number') min="{{ $field === 'minimum_order' ? 0 : 1 }}" max="4294967295" step="1" @endif @required($field === 'minimum_order') aria-describedby="coupon-{{ $field }}-hint @error($field) coupon-{{ $field }}-error @enderror" @error($field) aria-invalid="true" @enderror>
                        @error($field)<div id="coupon-{{ $field }}-error" class="invalid-feedback">{{ $message }}</div>@enderror
                        <small id="coupon-{{ $field }}-hint" class="form-text text-muted">{{ $hint }}</small>
                    </div>
                @endforeach
            </div>
            <div class="studio-form-actions d-flex justify-content-end pt-3 border-top"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus" aria-hidden="true"></i> Tạo mã ưu đãi</button></div>
        </form>
    </details>

    <div class="admin-card">
        <h2 class="h5 font-weight-bold mb-4">Danh sách mã ưu đãi</h2>
        <form class="studio-filter-bar mb-3" method="GET" action="{{ route('admin.coupons.index') }}">
            <div class="form-row align-items-end">
                <div class="col-md-6 mb-2"><label for="coupon-search" class="small font-weight-bold">Tìm mã</label><input id="coupon-search" class="form-control" type="search" name="search" maxlength="30" value="{{ request('search') }}" placeholder="Nhập mã ưu đãi…"></div>
                <div class="col-md-3 mb-2"><label for="coupon-status" class="small font-weight-bold">Trạng thái bật / tắt</label><select id="coupon-status" name="status" class="form-control"><option value="">Tất cả mã</option><option value="active" @selected(request('status') === 'active')>Đang bật</option><option value="inactive" @selected(request('status') === 'inactive')>Đã tắt</option></select></div>
                <div class="col-md-3 mb-2 d-flex gap-2"><button class="btn btn-primary" type="submit">Lọc mã</button>@if(request()->filled('search') || request()->filled('status'))<a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>@endif</div>
            </div>
        </form>
        <p class="studio-result-count text-muted small">{{ number_format($coupons->total()) }} mã ưu đãi · Lượt dùng tính theo đơn chưa hủy.</p>
        <div class="table-responsive">
            <table class="table table-hover table-admin mb-0">
                <thead><tr><th scope="col">Mã ưu đãi</th><th scope="col">Mức giảm</th><th scope="col">Đơn tối thiểu</th><th scope="col">Đã dùng / Giới hạn</th><th scope="col">Thời gian áp dụng (UTC)</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead>
                <tbody>
                @forelse($coupons as $coupon)
                    @php
                        $statusLabel = 'Có hiệu lực';
                        $statusClass = 'badge-active';
                        if (!$coupon->is_active) { $statusLabel = 'Đã tắt'; $statusClass = 'badge-inactive'; }
                        elseif ($coupon->expires_at && $coupon->expires_at->isPast()) { $statusLabel = 'Đã hết hạn'; $statusClass = 'badge-inactive'; }
                        elseif ($coupon->starts_at && $coupon->starts_at->isFuture()) { $statusLabel = 'Chưa tới ngày'; $statusClass = 'badge-inactive'; }
                        elseif ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) { $statusLabel = 'Hết lượt dùng'; $statusClass = 'badge-inactive'; }
                    @endphp
                    <tr>
                        <td><strong>{{ $coupon->code }}</strong></td>
                        <td class="font-weight-bold">{{ $coupon->type === 'percent' ? $coupon->value.'%' : number_format($coupon->value, 0, ',', '.').'₫' }}</td>
                        <td>{{ $coupon->minimum_order > 0 ? number_format($coupon->minimum_order, 0, ',', '.').'₫' : 'Không yêu cầu' }}</td>
                        <td><strong>{{ number_format($coupon->used_count) }}</strong> / {{ $coupon->usage_limit !== null ? number_format($coupon->usage_limit).' lượt' : 'Không giới hạn' }}</td>
                        <td><small class="d-block">Từ: {{ $coupon->starts_at?->format('H:i d/m/Y') ?? 'Ngay khi tạo' }}</small><small class="d-block text-muted">Đến: {{ $coupon->expires_at?->format('H:i d/m/Y') ?? 'Không hết hạn' }}</small></td>
                        <td><span class="{{ $statusClass }}">{{ $statusLabel }}</span>@if($coupon->is_active && $statusLabel !== 'Có hiệu lực')<small class="d-block text-muted mt-1">Mã đang bật</small>@endif</td>
                        <td><form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}" @if($coupon->is_active) data-confirm="Tắt mã {{ $coupon->code }}? Khách hàng sẽ không thể áp dụng mã này cho đơn mới." @endif>@csrf<button class="btn btn-outline-secondary btn-sm" type="submit" aria-label="{{ $coupon->is_active ? 'Tắt' : 'Bật' }} mã {{ $coupon->code }}">{{ $coupon->is_active ? 'Tắt mã' : 'Bật mã' }}</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="studio-empty-state text-center py-5"><i class="fa-solid fa-ticket-simple text-muted mb-3" aria-hidden="true"></i><strong class="d-block">{{ request()->filled('search') || request()->filled('status') ? 'Không tìm thấy mã phù hợp' : 'Chưa có mã ưu đãi' }}</strong><p class="text-muted small mb-0 mt-2">{{ request()->filled('search') || request()->filled('status') ? 'Thử tìm mã khác hoặc xóa bộ lọc.' : 'Mở mục tạo mã ưu đãi ở trên để phát hành ưu đãi đầu tiên.' }}</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($coupons->total())<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top"><span class="text-muted small">Hiển thị {{ $coupons->firstItem() }}–{{ $coupons->lastItem() }} / {{ $coupons->total() }} mã</span>{{ $coupons->links() }}</div>@endif
    </div>
</div>
@endsection
