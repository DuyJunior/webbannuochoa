@extends('layouts.admin')

@section('title', 'Quản lý mã ưu đãi')
@section('page_title', 'Quản lý mã ưu đãi & Voucher')

@section('content')
<div class="admin-coupons-page">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i> {{ $errors->first() }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Form tạo mã ưu đãi --}}
    <div class="admin-card mb-4">
        <h5 class="font-weight-bold mb-3" style="color: #0f172a;">
            <i class="fa-solid fa-ticket-simple text-primary mr-2"></i> Tạo mã giảm giá mới
        </h5>
        <p class="text-muted small mb-4">Tạo các voucher khuyến mãi tri ân khách hàng khi mua sắm tại boutique</p>

        <form method="POST" action="{{ route('admin.coupons.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4 col-sm-6 mb-3">
                    <label class="form-label font-weight-bold small text-muted text-uppercase">Mã voucher <span class="text-danger">*</span></label>
                    <input class="form-control" name="code" value="{{ old('code') }}" placeholder="VD: SOOPI50K, VALENTINE..." style="text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;" required>
                </div>
                <div class="col-md-4 col-sm-6 mb-3">
                    <label class="form-label font-weight-bold small text-muted text-uppercase">Loại giảm giá <span class="text-danger">*</span></label>
                    <select class="form-control custom-select" name="type">
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Giảm trực tiếp số tiền (VNĐ)</option>
                        <option value="percent" {{ old('type') === 'percent' ? 'selected' : '' }}>Giảm theo phần trăm (%)</option>
                    </select>
                </div>
                <div class="col-md-4 col-sm-6 mb-3">
                    <label class="form-label font-weight-bold small text-muted text-uppercase">Giá trị giảm <span class="text-danger">*</span></label>
                    <input class="form-control" name="value" type="number" min="1" value="{{ old('value') }}" placeholder="VD: 50000 hoặc 15" required>
                </div>
                <div class="col-md-4 col-sm-6 mb-3">
                    <label class="form-label font-weight-bold small text-muted text-uppercase">Đơn hàng tối thiểu (₫)</label>
                    <input class="form-control" name="minimum_order" type="number" min="0" value="{{ old('minimum_order', 0) }}" placeholder="0 nếu không yêu cầu" required>
                </div>
                <div class="col-md-4 col-sm-6 mb-3">
                    <label class="form-label font-weight-bold small text-muted text-uppercase">Giới hạn lượt dùng</label>
                    <input class="form-control" name="usage_limit" type="number" min="1" value="{{ old('usage_limit') }}" placeholder="Để trống nếu không giới hạn">
                </div>
                <div class="col-md-4 col-sm-6 mb-3">
                    <label class="form-label font-weight-bold small text-muted text-uppercase">Thời gian hiệu lực</label>
                    <div class="d-flex gap-2">
                        <input class="form-control" name="starts_at" type="datetime-local" title="Bắt đầu" value="{{ old('starts_at') }}">
                    </div>
                </div>
                <div class="col-md-4 col-sm-6 mb-3">
                    <label class="form-label font-weight-bold small text-muted text-uppercase">Hết hạn vào lúc</label>
                    <input class="form-control" name="expires_at" type="datetime-local" title="Hết hạn" value="{{ old('expires_at') }}">
                </div>
            </div>

            <div class="mt-2 text-right">
                <button class="btn btn-primary px-4 py-2 font-weight-bold" type="submit" style="border-radius: 8px;">
                    <i class="fa-solid fa-plus mr-1"></i> Tạo mã ưu đãi
                </button>
            </div>
        </form>
    </div>

    {{-- Danh sách mã ưu đãi --}}
    <div class="admin-card">
        <h5 class="font-weight-bold mb-3" style="color: #0f172a;">
            <i class="fa-solid fa-list-check text-primary mr-2"></i> Danh sách mã ưu đãi đã phát hành
        </h5>

        <div class="table-responsive">
            <table class="table table-hover table-admin align-middle mb-0">
                <thead>
                    <tr>
                        <th width="140">Mã voucher</th>
                        <th width="150">Mức giảm</th>
                        <th>Đơn tối thiểu</th>
                        <th>Đã dùng / Giới hạn</th>
                        <th width="130" class="text-center">Trạng thái</th>
                        <th width="100" class="text-center">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                        <tr>
                            <td>
                                <span class="badge badge-light border text-primary font-weight-bold px-3 py-2" style="font-family: monospace; font-size: 0.95rem; letter-spacing: 0.5px;">
                                    {{ $coupon->code }}
                                </span>
                            </td>
                            <td>
                                @if($coupon->type === 'percent')
                                    <span class="badge badge-warning text-dark px-2 py-1 font-weight-bold">
                                        Giảm {{ $coupon->value }}%
                                    </span>
                                @else
                                    <span class="badge badge-success px-2 py-1 font-weight-bold">
                                        -{{ number_format($coupon->value, 0, ',', '.') }}₫
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="text-dark font-weight-500">
                                    {{ $coupon->minimum_order > 0 ? number_format($coupon->minimum_order, 0, ',', '.') . '₫' : 'Không giới hạn' }}
                                </span>
                            </td>
                            <td class="text-muted small">
                                <strong>{{ $coupon->used_count ?? 0 }}</strong> / {{ $coupon->usage_limit ? $coupon->usage_limit . ' lượt' : '∞' }}
                            </td>
                            <td class="text-center">
                                @if($coupon->is_active)
                                    <span class="badge-active"><i class="fa-solid fa-circle mr-1" style="font-size:0.5rem;"></i> Đang bật</span>
                                @else
                                    <span class="badge-inactive"><i class="fa-solid fa-circle mr-1" style="font-size:0.5rem;"></i> Đã tắt</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}" class="d-inline">
                                    @csrf
                                    @if($coupon->is_active)
                                        <button class="btn btn-outline-secondary btn-sm px-3" type="submit" title="Tắt mã này">
                                            <i class="fa-solid fa-power-off mr-1"></i> Tắt
                                        </button>
                                    @else
                                        <button class="btn btn-outline-success btn-sm px-3" type="submit" title="Kích hoạt lại">
                                            <i class="fa-solid fa-play mr-1"></i> Bật
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-ticket-simple mb-2" style="font-size: 2rem; color: #cbd5e1; display: block;"></i>
                                Chưa có mã ưu đãi nào được tạo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($coupons->hasPages())
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
                <div class="text-muted small">
                    Hiển thị <strong>{{ $coupons->firstItem() }}</strong> - <strong>{{ $coupons->lastItem() }}</strong> trong tổng số <strong>{{ $coupons->total() }}</strong> mã
                </div>
                <div>
                    {{ $coupons->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
