@extends('layouts.admin')

@section('title', 'Chỉnh sửa người dùng #' . $user->id . ' · Lab 8')
@section('page_title', 'Chỉnh sửa người dùng')

@section('content')
<div class="admin-card p-4" style="max-width: 680px; margin: 0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h2 class="h4 font-weight-bold text-dark mb-0">Chỉnh sửa người dùng</h2>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 6px;">
            <i class="fa-solid fa-arrow-left mr-1"></i> Quay lại
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">Họ và tên <span class="text-danger">*</span></label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
        </div>

        <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
        </div>

        <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">Mật khẩu mới (Để trống nếu không đổi)</label>
            <input type="password" name="password" class="form-control" placeholder="Để trống nếu giữ nguyên">
        </div>

        <div class="form-group mb-4">
            <label class="font-weight-bold text-dark">Vai trò <span class="text-danger">*</span></label>
            <select name="role" class="form-control form-select" required>
                <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>Khách hàng (User)</option>
                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Quản trị viên (Admin)</option>
                <option value="livestream_staff" {{ old('role', $user->role) === 'livestream_staff' ? 'selected' : '' }}>Nhân viên livestream</option>
            </select>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-light mr-2">Hủy</a>
            <button type="submit" class="btn btn-primary font-weight-bold px-4" style="background:#db2777; border-color:#db2777;">
                <i class="fa-solid fa-save mr-1"></i> Cập nhật người dùng
            </button>
        </div>
    </form>
</div>
@endsection
