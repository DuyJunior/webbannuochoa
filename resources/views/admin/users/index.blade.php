@extends('layouts.admin')
@section('title', __('Khách hàng & đội ngũ'))
@section('page_title', __('Khách hàng & đội ngũ'))
@section('content')
<div class="studio-function-metrics">
    @foreach([
        [__('Tổng tài khoản'), $roleCounts->sum(), 'users', __('Toàn bộ tài khoản trong hệ thống')],
        [__('Khách hàng'), ($roleCounts['user'] ?? 0) + ($roleCounts['customer'] ?? 0), 'heart', __('Đồng hành cùng thương hiệu')],
        [__('Đội ngũ cửa hàng'), ($roleCounts['admin'] ?? 0) + ($roleCounts['livestream_staff'] ?? 0), 'user-shield', __('Quản trị viên & nhân viên livestream')],
    ] as [$label, $value, $icon, $note])
    <div class="studio-function-metric"><div><span>{{ __($label) }}</span><strong>{{ number_format($value) }}</strong><small>{{ $note }}</small></div><i class="fa-solid fa-{{ $icon }}" aria-hidden="true"></i></div>
    @endforeach
</div>
<section class="admin-card studio-directory">
    <form method="GET" action="{{ route('admin.users.index') }}" class="studio-directory-filters">
        <div class="studio-search-field"><label for="customer-search">{{ __('Tìm tài khoản') }}</label><input class="form-control" type="search" id="customer-search" name="search" value="{{ request('search') }}" placeholder="{{ __('Họ tên hoặc địa chỉ email…') }}"></div>
        <div><label for="customer-role">{{ __('Vai trò') }}</label><select id="customer-role" class="form-control" name="role"><option value="">{{ __('Tất cả vai trò') }}</option>@foreach(['customer' => __('Khách hàng'), 'admin' => __('Quản trị viên'), 'livestream_staff' => __('Nhân viên livestream')] as $value => $label)<option value="{{ $value }}" @selected(request('role') === $value)>{{ __($label) }}</option>@endforeach</select></div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass mr-1" aria-hidden="true"></i> {{ __('Tìm kiếm') }}</button>
        @if(request()->anyFilled(['search', 'role']))<a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">{{ __('Xóa bộ lọc') }}</a>@endif
    </form>
    <div class="studio-list-heading"><h3>{{ __('Danh sách người dùng') }} <span>{{ number_format($users->total()) }}</span></h3><small>{{ __('Thông tin & quyền truy cập') }}</small></div>
    <div class="table-responsive"><table class="table table-hover studio-people-table mb-0">
        <thead><tr><th>{{ __('Tài khoản') }}</th><th>{{ __('Vai trò') }}</th><th>Tham gia</th><th class="text-right">{{ __('Thao tác') }}</th></tr></thead>
        <tbody>@forelse($users as $user)
            <tr>
                <td><div class="studio-person"><span class="studio-person-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span><div><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a><span>{{ $user->email }}</span><small>#{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}@if($user->id === Auth::id()) {{ __('· Tài khoản của bạn') }} @endif</small></div></div></td>
                <td><span class="studio-role studio-role--{{ $user->role === 'admin' ? 'admin' : ($user->role === 'livestream_staff' ? 'staff' : 'customer') }}">{{ ['admin' => __('Quản trị viên'), 'livestream_staff' => __('Nhân viên livestream')][$user->role] ?? __('Khách hàng') }}</span></td>
                <td class="text-muted">{{ $user->created_at?->format('d/m/Y') ?? '—' }}</td>
                <td><div class="studio-row-actions">
                    @if($user->id !== Auth::id())<button type="button" class="studio-icon-action" data-chat-user="{{ $user->id }}" data-chat-name="{{ $user->name }}" title="{{ __('Nhắn tin') }}" aria-label="Nhắn tin cho {{ $user->name }}"><i class="fa-regular fa-comment-dots" aria-hidden="true"></i></button>@endif
                    <a class="studio-icon-action" href="{{ route('admin.users.show', $user) }}" title="{{ __('Xem chi tiết') }}" aria-label="Xem {{ $user->name }}"><i class="fa-regular fa-eye" aria-hidden="true"></i></a>
                    <a class="studio-icon-action" href="{{ route('admin.users.edit', $user) }}" title="{{ __('Chỉnh sửa') }}" aria-label="Chỉnh sửa {{ $user->name }}"><i class="fa-regular fa-pen-to-square" aria-hidden="true"></i></a>
                    @if($user->id !== Auth::id())<form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Xóa tài khoản {{ $user->name }}? Tài khoản sẽ không thể đăng nhập lại. Hành động này không thể hoàn tác.">@csrf @method('DELETE')<button type="submit" class="studio-icon-action studio-icon-action--danger" title="{{ __('Xóa tài khoản') }}" aria-label="Xóa {{ $user->name }}"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button></form>@endif
                </div></td>
            </tr>
        @empty<tr><td colspan="4"><div class="studio-directory-empty"><i class="fa-solid fa-users" aria-hidden="true"></i><strong>{{ __('Chưa tìm thấy tài khoản phù hợp') }}</strong><p>{{ __('Thử một tên, email hoặc vai trò khác.') }}</p></div></td></tr>@endforelse</tbody>
    </table></div>
    <div class="studio-list-footer"><span>{{ __('Hiển thị') }} {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} / {{ number_format($users->total()) }} {{ __('tài khoản') }}</span>{{ $users->links() }}</div>
</section>
@endsection
