@php
    $editing = isset($user);
    $self = $editing && $user->id === auth()->id();
    $role = $self ? 'admin' : old('role', $editing ? $user->role : 'user');
@endphp
<div class="studio-list-heading mb-4"><div><span class="studio-form-kicker">{{ __('KHÁCH HÀNG & ĐỘI NGŨ') }}</span><h2>{{ $editing ? $user->name : __('Tạo tài khoản mới') }}</h2><p class="text-muted mb-0">{{ __('Quản lý thông tin liên hệ và quyền truy cập hệ thống.') }}</p></div><a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i> {{ __('Danh sách tài khoản') }}</a></div>

<form action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST">
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="studio-form-layout">
        <section class="admin-card studio-form-section" aria-labelledby="account-details">
            <span class="studio-form-kicker">{{ __('THÔNG TIN TÀI KHOẢN') }}</span><h3 id="account-details">{{ __('Thông tin đăng nhập') }}</h3><p class="studio-field-help">{{ __('Các trường có dấu * là bắt buộc.') }}</p>
            <div class="form-group"><label for="user-name">{{ __('Họ và tên') }} <span class="text-danger">*</span></label><input id="user-name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $editing ? $user->name : '') }}" maxlength="255" autocomplete="name" placeholder="{{ __('Nguyễn Lan Anh') }}" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label for="user-email">{{ __('Địa chỉ email') }} <span class="text-danger">*</span></label><input id="user-email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $editing ? $user->email : '') }}" maxlength="255" autocomplete="email" placeholder="email@example.com" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label for="user-password">{{ $editing ? __('Mật khẩu mới') : __('Mật khẩu') }} @unless($editing)<span class="text-danger">*</span>@endunless</label><input id="user-password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" minlength="6" autocomplete="new-password" aria-describedby="user-password-help" @required(!$editing)><small id="user-password-help" class="studio-field-help">{{ $editing ? __('Để trống để giữ nguyên mật khẩu. Mật khẩu mới cần ít nhất 6 ký tự.') : __('Sử dụng mật khẩu có ít nhất 6 ký tự.') }}</small>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="form-group mb-0"><label for="user-role">{{ __('Vai trò') }} <span class="text-danger">*</span></label>
                @if($self)<input type="hidden" name="role" value="admin">@endif
                <select id="user-role" name="{{ $self ? 'current_role' : 'role' }}" class="form-control @error('role') is-invalid @enderror" aria-describedby="user-role-help" @disabled($self) required>
                    <option value="user" @selected($role === 'user')>{{ __('Khách hàng') }}</option>
                    @if(($editing && $user->role === 'customer') || $role === 'customer')<option value="customer" @selected($role === 'customer')>{{ __('Khách hàng (tài khoản hiện có)') }}</option>@endif
                    <option value="livestream_staff" @selected($role === 'livestream_staff')>{{ __('Nhân viên livestream') }}</option>
                    <option value="admin" @selected($self || $role === 'admin')>{{ __('Quản trị viên') }}</option>
                </select>
                <small id="user-role-help" class="studio-field-help">{{ $self ? __('Quyền quản trị của tài khoản đang đăng nhập được giữ nguyên để bạn tiếp tục truy cập.') : __('Chọn phạm vi truy cập phù hợp với công việc của tài khoản.') }}</small>@error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </section>
        <aside class="studio-form-aside">
            <section class="admin-card studio-form-section" aria-labelledby="account-permissions"><span class="studio-form-kicker">{{ __('PHÂN QUYỀN') }}</span><h3 id="account-permissions">{{ __('Mỗi vai trò được làm gì?') }}</h3><dl class="studio-detail-grid"><div><dt>{{ __('Khách hàng') }}</dt><dd>{{ __('Mua sắm, theo dõi đơn hàng và quản lý tài khoản cá nhân.') }}</dd></div><div><dt>{{ __('Nhân viên livestream') }}</dt><dd>{{ __('Quản lý các buổi phát trực tiếp và sản phẩm trong livestream.') }}</dd></div><div><dt>{{ __('Quản trị viên') }}</dt><dd>{{ __('Quản lý toàn bộ cửa hàng, đơn hàng, nội dung và tài khoản.') }}</dd></div></dl></section>
            @unless($editing)<div class="admin-card studio-form-section"><h3>{{ __('Xác thực email') }}</h3><p class="studio-field-help mb-0">{{ __('Tài khoản do quản trị viên tạo sẽ được đánh dấu đã xác thực email. Hãy kiểm tra đúng địa chỉ trước khi lưu.') }}</p></div>@endunless
        </aside>
    </div>
    <div class="studio-form-actions"><span class="studio-field-help">{{ $editing ? __('Thay đổi được áp dụng ngay khi lưu.') : __('Tài khoản có thể đăng nhập ngay sau khi tạo.') }}</span><div><a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary mr-2">{{ __('Hủy thay đổi') }}</a><button type="submit" class="btn btn-primary"><i class="fa-solid fa-check mr-1" aria-hidden="true"></i> {{ $editing ? __('Lưu thay đổi') : __('Thêm tài khoản') }}</button></div></div>
</form>
