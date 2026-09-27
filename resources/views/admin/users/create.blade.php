@extends('layouts.admin')

@section('title', 'Thêm người dùng mới · Lab 8')
@section('page_title', 'Thêm người dùng mới')

@section('content')
<div class="admin-card p-4" style="max-width: 680px; margin: 0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h2 class="h4 font-weight-bold text-dark mb-0">Thêm người dùng</h2>
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

    <form action="{{ route('admin.users.store') }}" method="POST">
        @csrf
        <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">Họ và tên <span class="text-danger">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control" placeholder="Ví dụ: Nguyễn Văn A" required>
        </div>

        <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">Địa chỉ Email <span class="text-danger">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="email@example.com" required>
        </div>

        <div class="form-group mb-3">
            <label class="font-weight-bold text-dark">Mật khẩu <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
        </div>

        <div class="form-group mb-4">
            <label class="font-weight-bold text-dark">Vai trò hệ thống <span class="text-danger">*</span></label>
            <select name="role" class="form-control form-select" required>
                <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>Khách hàng (User)</option>
                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Quản trị viên (Admin)</option>
                <option value="livestream_staff" {{ old('role') === 'livestream_staff' ? 'selected' : '' }}>Nhân viên livestream</option>
            </select>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('admin.users.index') }}" class="btn btn-light mr-2">Hủy bỏ</a>
            <button type="submit" class="btn btn-success font-weight-bold px-4" style="background:#059669; border-color:#059669;">
                <i class="fa-solid fa-check mr-1"></i> Lưu người dùng
            </button>
        </div>
    </form>
</div>
@endsection
