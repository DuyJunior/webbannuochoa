@extends('layouts.admin')

@section('title', 'Quản lý người dùng · Lab 8')
@section('page_title', 'Danh sách người dùng')

@section('content')
<div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 font-weight-bold text-dark mb-1">Danh sách người dùng</h2>
            <small class="text-muted">Quản lý tài khoản quản trị viên và khách hàng trong hệ thống</small>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-success font-weight-bold px-3" style="border-radius: 8px;">
            <i class="fa-solid fa-user-plus mr-1"></i> + Thêm người dùng
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle mb-0">
            <thead class="bg-light text-muted" style="font-size: 0.82rem; text-transform: uppercase;">
                <tr>
                    <th width="70" class="text-center">ID</th>
                    <th>Họ và tên</th>
                    <th>Email</th>
                    <th width="140" class="text-center">Vai trò</th>
                    <th width="200" class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="text-center font-weight-bold text-muted">#{{ $user->id }}</td>
                        <td class="font-weight-600 text-dark">
                            <i class="fa-solid fa-circle-user mr-1 text-pink"></i> {{ $user->name }}
                        </td>
                        <td>{{ $user->email }}</td>
                        <td class="text-center">
                            @if($user->role === 'admin')
                                <span class="badge badge-danger px-3 py-1 font-weight-bold" style="border-radius: 12px;">Quản trị viên</span>
                            @elseif($user->role === 'livestream_staff')
                                <span class="badge badge-primary px-3 py-1 font-weight-bold" style="border-radius: 12px;">Nhân viên livestream</span>
                            @else
                                <span class="badge badge-info px-3 py-1 font-weight-bold" style="border-radius: 12px;">Khách hàng</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($user->id !== Auth::id())
                                <button type="button" class="btn btn-outline-success btn-sm px-2 py-1 mr-1" onclick="openChatWithUser({{ $user->id }}, '{{ addslashes($user->name) }}')" title="Nhắn tin cho người dùng này">
                                    <i class="fa-solid fa-comment-dots mr-1"></i> Nhắn tin
                                </button>
                            @endif
                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-info btn-sm px-2 py-1" title="Xem chi tiết">
                                <i class="fa-solid fa-eye mr-1"></i> Xem
                            </a>
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary btn-sm px-2 py-1" title="Chỉnh sửa">
                                <i class="fa-solid fa-pen mr-1"></i> Sửa
                            </a>
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng {{ $user->name }}?')" class="btn btn-danger btn-sm px-2 py-1" title="Xóa">
                                    <i class="fa-solid fa-trash mr-1"></i> Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Không có người dùng nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
            <div class="text-muted small">
                Hiển thị <strong>{{ $users->firstItem() }}</strong> - <strong>{{ $users->lastItem() }}</strong> trong tổng số <strong>{{ $users->total() }}</strong> người dùng
            </div>
            <div>
                {{ $users->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
