@extends('layouts.admin')

@section('title', 'Thông tin người dùng #' . $user->id)
@section('page_title', 'Thông tin người dùng')

@section('content')
<div class="admin-card p-4" style="max-width: 680px; margin: 0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h2 class="h4 font-weight-bold text-dark mb-0">Chi tiết người dùng</h2>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 6px;">
            <i class="fa-solid fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius: 12px; background: #fafafa;">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-4">
                <div class="mr-3" style="width: 64px; height: 64px; border-radius: 50%; background: linear-gradient(135deg, #db2777, #f472b6); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; box-shadow: 0 4px 12px rgba(219,39,119,0.3);">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="h5 font-weight-bold mb-1 text-dark">{{ $user->name }}</h3>
                    <span class="badge {{ $user->role === 'admin' ? 'badge-danger' : 'badge-info' }} px-3 py-1" style="border-radius: 20px;">
                        {{ $user->role === 'admin' ? 'Quản trị viên (Admin)' : ($user->role === 'livestream_staff' ? 'Nhân viên livestream' : 'Khách hàng (User)') }}
                    </span>
                </div>
            </div>

            <hr class="my-3">

            <div class="row mb-3">
                <div class="col-sm-4 text-muted font-weight-bold">Mã người dùng (ID):</div>
                <div class="col-sm-8 font-weight-bold text-dark">#{{ $user->id }}</div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-4 text-muted font-weight-bold">Địa chỉ Email:</div>
                <div class="col-sm-8 font-weight-600 text-dark">{{ $user->email }}</div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-4 text-muted font-weight-bold">Vai trò:</div>
                <div class="col-sm-8 font-weight-bold {{ $user->role === 'admin' ? 'text-danger' : 'text-primary' }}">
                    {{ ucfirst($user->role) }}
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-4 text-muted font-weight-bold">Ngày đăng ký:</div>
                <div class="col-sm-8 text-dark">{{ $user->created_at ? $user->created_at->format('d/m/Y H:i:s') : '—' }}</div>
            </div>
            <div class="row mb-3">
                <div class="col-sm-4 text-muted font-weight-bold">Trạng thái xác thực email:</div>
                <div class="col-sm-8">
                    @if($user->email_verified_at)
                        <span class="text-success font-weight-bold"><i class="fa-solid fa-circle-check mr-1"></i> Đã xác thực ({{ $user->email_verified_at->format('d/m/Y') }})</span>
                    @else
                        <span class="text-warning font-weight-bold"><i class="fa-solid fa-clock mr-1"></i> Chưa xác thực</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary px-3" style="border-radius: 6px;">
            <i class="fa-solid fa-arrow-left mr-1"></i> Quay lại danh sách
        </a>
        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary px-3" style="border-radius: 6px; background:#db2777; border-color:#db2777;">
            <i class="fa-solid fa-pen mr-1"></i> Chỉnh sửa tài khoản
        </a>
    </div>
</div>
@endsection
