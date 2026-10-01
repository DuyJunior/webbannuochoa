@extends('layouts.admin')
@section('title', 'Thông tin tài khoản')
@section('page_title', 'Thông tin tài khoản')
@section('content')
@php($roleLabel = ['admin' => 'Quản trị viên', 'livestream_staff' => 'Nhân viên livestream'][$user->role] ?? 'Khách hàng')
<div class="studio-list-heading mb-4"><div><span class="studio-form-kicker">TÀI KHOẢN #{{ $user->id }}</span><h2>{{ $user->name }}</h2><p class="text-muted mb-0">Thông tin liên hệ và quyền truy cập của tài khoản.</p></div><a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> Danh sách tài khoản</a></div>
<div class="studio-form-layout">
    <section class="admin-card studio-form-section">
        <div class="studio-person mb-4"><span class="studio-person-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span><div><h3 class="mb-1">{{ $user->name }}</h3><span class="studio-role studio-role--{{ $user->role === 'admin' ? 'admin' : ($user->role === 'livestream_staff' ? 'staff' : 'customer') }}">{{ $roleLabel }}</span></div></div>
        <dl class="studio-detail-grid">
            <div><dt>Địa chỉ email</dt><dd>{{ $user->email }}</dd></div>
            <div><dt>Ngày tham gia</dt><dd>{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div><dt>Quyền truy cập</dt><dd>{{ $roleLabel }}@if($user->id === auth()->id()) · Tài khoản của bạn @endif</dd></div>
            <div><dt>Xác thực email</dt><dd>@if($user->email_verified_at)<span class="badge-active"><i class="fa-solid fa-circle-check mr-1" aria-hidden="true"></i> Đã xác thực</span><small class="d-block text-muted mt-2">{{ $user->email_verified_at->format('d/m/Y H:i') }}</small>@else<span class="badge-inactive">Chưa xác thực</span>@endif</dd></div>
        </dl>
    </section>
    <aside class="studio-form-aside"><section class="admin-card studio-form-section"><span class="studio-form-kicker">THAO TÁC</span><h3>Quản lý tài khoản</h3><a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary btn-block"><i class="fa-regular fa-pen-to-square mr-1" aria-hidden="true"></i> Chỉnh sửa thông tin</a>@if($user->id !== auth()->id())<button type="button" class="btn btn-outline-secondary btn-block mt-2" data-chat-user="{{ $user->id }}" data-chat-name="{{ $user->name }}"><i class="fa-regular fa-comment-dots mr-1" aria-hidden="true"></i> Mở cuộc trò chuyện</button>@endif</section></aside>
</div>
@endsection
