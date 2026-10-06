    @if(Auth::user()->role === 'admin')
    {{-- LAB 7: ADMIN LIVECHAT POPUP (PDF Trang 13 - 14 + Chủ động nhắn tin) --}}
    <div id="admin-chat-box" data-users-url="{{ route('admin.chat.users') }}" data-messages-url="{{ route('admin.chat.messages', ['userId' => '__USER__']) }}" data-send-url="{{ route('admin.chat.send') }}" data-admin-id="{{ Auth::id() }}">
        <button type="button" id="chat-toggle" aria-expanded="false" aria-controls="chat-popup" class="btn btn-dark shadow">@include('partials.icon', ['name' => 'chat', 'size' => '1em']) <span>{{ __('Tin nhắn') }}</span></button>
        <div id="chat-popup" role="region" aria-label="{{ __('Hỗ trợ khách hàng') }}" class="card shadow-lg" style="display:none;">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge badge-success" style="width:8px; height:8px; border-radius:50%; display:inline-block; padding:0;"></span>
                    <strong style="font-size:0.95rem;">{{ __('Hỗ trợ khách hàng') }}</strong>
                </div>
                <button type="button" id="chat-close" aria-label="{{ __('Đóng hộp tin nhắn') }}" class="btn btn-sm btn-outline-light py-0 px-2 font-weight-bold">&times;</button>
            </div>

            {{-- Thanh tìm kiếm & chuyển chế độ --}}
            <div class="p-2 bg-light border-bottom">
                <div class="input-group input-group-sm mb-1">
                    <input type="text" id="chat-search-input" aria-label="{{ __('Tìm khách hàng') }}" maxlength="100" class="form-control form-control-sm" placeholder="{{ __('Tìm khách hàng (tên, email)...') }}">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="btn-clear-search" aria-label="{{ __('Xóa tìm kiếm') }}" style="display:none;">&times;</button>
                    </div>
                </div>
                <div class="d-flex gap-1 justify-content-between align-items-center">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 active" id="chat-tab-recent" style="font-size:0.75rem;">{{ __('Đã nhắn tin') }}</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0" id="chat-tab-all" style="font-size:0.75rem;">{{ __('Tất cả khách') }}</button>
                    </div>
                </div>
            </div>

            {{-- Header user đang chọn --}}
            <div id="chat-active-header" class="px-3 py-1 bg-white border-bottom text-truncate small" style="display:none; color:#db2777; font-weight:600;">
                @include('partials.icon', ['name' => 'chat', 'size' => '1em']) {{ __('Đang trò chuyện với:') }} <span id="active-user-name" class="text-dark"></span>
            </div>

            {{-- Danh sách User --}}
            <div id="user-list">
                <div class="p-2 text-center text-muted"><small>{{ __('Đang tải danh sách...') }}</small></div>
            </div>

            {{-- Vùng hiển thị tin nhắn --}}
            <div id="chat-messages">
                <div class="text-center mt-5 text-muted small">
                    <i class="fa-regular fa-comments mb-2" style="font-size:2rem; opacity:0.35;"></i>
                    <div>{{ __('Chọn một khách hàng hoặc tìm kiếm để bắt đầu nhắn tin') }}</div>
                </div>
            </div>

            <p id="admin-chat-error" data-toast-source="error" class="studio-chat-error" role="alert" hidden></p>
            {{-- Ô nhập tin nhắn --}}
            <div class="card-footer bg-white p-2">
                <div class="input-group">
                    <input type="text" id="chat-input" aria-label="{{ __('Nội dung tin nhắn') }}" maxlength="5000" class="form-control form-control-sm" placeholder="{{ __('Nhập tin nhắn gửi khách...') }}" disabled>
                    <div class="input-group-append">
                        <button type="button" id="send-btn" class="btn btn-success btn-sm px-3" disabled>
                            <i class="fa-solid fa-paper-plane"></i> {{ __('Gửi') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
