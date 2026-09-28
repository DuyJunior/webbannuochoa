{{-- LAB 7: LUXURY BOUTIQUE CHAT POPUP (Soopi) --}}
@auth
<div id="chat-box" class="boutique-chat-wrapper">
    {{-- Nút bấm mở chat nổi --}}
    <button id="chat-toggle" class="chat-floating-btn" type="button" aria-label="Mở tư vấn Soopi" aria-controls="chat-popup" aria-expanded="false">
        <span class="chat-btn-pulse"></span>
        <span class="chat-icon-wrap">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
            </svg>
        </span>
        <span class="chat-online-dot" aria-hidden="true"></span>
        <span class="chat-btn-label">Tư vấn</span>
    </button>

    {{-- Khung cửa sổ chat --}}
    <div id="chat-popup" class="chat-modal-window" style="display:none;" role="dialog" aria-labelledby="chat-header-title">
        {{-- Header cửa sổ --}}
        <div class="chat-window-header">
            <div class="chat-header-left">
                <div class="chat-avatar-frame">
                    <span class="chat-avatar-text">HT</span>
                    <span class="avatar-status-pip"></span>
                </div>
                <div class="chat-header-meta">
                    <div id="chat-header-title" class="chat-title">Soopi</div>
                    <div class="chat-subtitle">
                        <span class="live-indicator"></span>
                        <span id="chat-assistant-label">Tư vấn mùi hương và đơn hàng</span>
                    </div>
                </div>
            </div>
            <div class="chat-header-actions">
                <button id="chat-close" class="chat-close-btn" type="button" aria-label="Đóng cửa sổ chat" title="Đóng">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <div class="chat-mode-bar">
            <span id="chat-mode-label">Đang kết nối cuộc trò chuyện...</span>
            <button id="chat-mode-toggle" class="chat-mode-btn" type="button" hidden>Gặp nhân viên</button>
        </div>

        {{-- Vùng hiển thị tin nhắn --}}
        <div id="chat-messages" class="chat-messages-scroll" tabindex="0">
            <div class="chat-welcome-card">
                <div class="welcome-flower">🌸</div>
                <div class="welcome-heading">Chào mừng bạn đến với Soopi!</div>
                <div class="welcome-text">Hãy nhắn nhu cầu chọn mùi hương hoặc câu hỏi về đơn hàng. Bạn có thể chọn gặp nhân viên bất cứ lúc nào.</div>
                <div class="quick-chips-group">
                    <button type="button" class="quick-chip-btn" data-text="Shop tư vấn giúp mình mùi hương nữ nhẹ nhàng, đi làm hàng ngày với ạ! 🌸">
                        🌸 Tìm mùi thanh lịch
                    </button>
                    <button type="button" class="quick-chip-btn" data-text="Shop kiểm tra tiến độ đơn hàng gần nhất giúp mình nhé! 📦">
                        📦 Kiểm tra đơn hàng
                    </button>
                    <button type="button" class="quick-chip-btn" data-text="Shop có hỗ trợ khắc tên và gói quà tặng không ạ? 🎁">
                        🎁 Dịch vụ quà tặng
                    </button>
                </div>
            </div>
            <div id="chat-stream-loading" class="text-center py-2 text-muted" style="display:none;">
                <small class="chat-loading-text">Đang tải cuộc trò chuyện...</small>
            </div>
            <div id="chat-history" class="chat-history" role="log" aria-label="Lịch sử trò chuyện" aria-live="polite" aria-relevant="additions"></div>
        </div>

        {{-- Footer nhập tin nhắn --}}
        <div class="chat-window-footer">
            <div id="chat-ai-notice" class="chat-ai-notice" role="status" aria-live="polite" hidden></div>
            <div id="chat-error" class="chat-error" role="alert" hidden></div>
            <form id="chat-input-form" class="chat-form-row">
                <div class="chat-input-wrapper">
                    <textarea id="chat-input" class="chat-text-input" placeholder="Nhập tin nhắn..." aria-label="Nội dung tin nhắn" autocomplete="off" maxlength="1000" rows="1"></textarea>
                </div>
                <button id="send-btn" class="chat-send-action-btn" type="submit" aria-label="Gửi tin nhắn" title="Gửi (Enter), xuống dòng (Shift + Enter)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </form>
            <div class="chat-footer-note">
                Khi bật AI, lịch sử chat được Groq xử lý để tư vấn. AI có thể trả lời sai. Không gửi mật khẩu, OTP hoặc thông tin thẻ.
            </div>
        </div>
    </div>
</div>
@else
<div id="chat-box" class="boutique-chat-wrapper">
    <a href="{{ route('login') }}" id="chat-toggle" class="chat-floating-btn guest-btn" title="Đăng nhập để chat trực tiếp với chuyên gia mùi hương Soopi">
        <span class="chat-btn-pulse"></span>
        <span class="chat-icon-wrap">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
            </svg>
        </span>
        <span class="chat-online-dot"></span>
        <span class="chat-btn-label">Tư vấn</span>
    </a>
</div>
@endauth

<style>
/* ── LUXURY BOUTIQUE CHAT POPUP STYLES ── */
.boutique-chat-wrapper {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 999999;
    font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* 1. Nút nổi Floating Button */
.chat-floating-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 18px 0 14px;
    height: 52px;
    border-radius: 9999px;
    background: linear-gradient(135deg, #e11d48 0%, #db2777 55%, #be185d 100%);
    border: 1.5px solid rgba(255, 255, 255, 0.4);
    color: #ffffff !important;
    text-decoration: none !important;
    box-shadow: 0 8px 24px rgba(219, 39, 119, 0.42), 0 2px 6px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
    outline: none;
}
.chat-floating-btn:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 12px 30px rgba(219, 39, 119, 0.52), 0 4px 10px rgba(0, 0, 0, 0.12);
    color: #ffffff !important;
}
.chat-floating-btn:active {
    transform: translateY(0) scale(0.98);
}
.chat-btn-pulse {
    position: absolute;
    inset: -3px;
    border-radius: 9999px;
    border: 2px solid rgba(244, 114, 182, 0.6);
    animation: chatPulse 2.4s infinite cubic-bezier(0.4, 0, 0.6, 1);
    pointer-events: none;
}
@keyframes chatPulse {
    0% { transform: scale(0.96); opacity: 0.9; }
    50% { transform: scale(1.08); opacity: 0; }
    100% { transform: scale(0.96); opacity: 0; }
}
.chat-icon-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
}
.chat-btn-label {
    font-size: 0.88rem;
    font-weight: 700;
    letter-spacing: 0.3px;
    white-space: nowrap;
}
.chat-online-dot {
    position: absolute;
    top: 6px;
    left: 32px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #10b981;
    border: 2px solid #ffffff;
    box-shadow: 0 0 6px rgba(16, 185, 129, 0.8);
}

/* 2. Cửa sổ chat Window */
.chat-modal-window {
    width: 380px;
    max-width: calc(100vw - 32px);
    height: 540px;
    max-height: calc(100vh - 100px);
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(157, 23, 77, 0.2), 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(244, 114, 182, 0.35);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: chatWindowPop 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes chatWindowPop {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.94);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* 3. Header */
.chat-window-header {
    background: linear-gradient(135deg, #be185d 0%, #db2777 55%, #e11d48 100%);
    color: #ffffff;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(190, 24, 93, 0.25);
    position: relative;
    z-index: 2;
}
.chat-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.chat-avatar-frame {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
    flex-shrink: 0;
}
.chat-avatar-text {
    font-size: 0.95rem;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: 0.5px;
}
.avatar-status-pip {
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 11px;
    height: 11px;
    border-radius: 50%;
    background: #10b981;
    border: 2px solid #be185d;
}
.chat-header-meta {
    display: flex;
    flex-direction: column;
}
.chat-title {
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.2px;
    line-height: 1.25;
}
.chat-subtitle {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.88);
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 2px;
}
.live-indicator {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #34d399;
    box-shadow: 0 0 6px #34d399;
    display: inline-block;
}
.chat-close-btn {
    background: rgba(255, 255, 255, 0.18);
    border: none;
    color: #ffffff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}
.chat-close-btn:hover {
    background: rgba(255, 255, 255, 0.32);
    transform: rotate(90deg);
}
.chat-mode-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 8px 14px;
    border-bottom: 1px solid #f3e8ee;
    background: #fff5f8;
    font-size: 0.74rem;
    color: #6b4b57;
}
.chat-mode-btn {
    border: 1px solid #db2777;
    border-radius: 16px;
    padding: 5px 9px;
    background: #ffffff;
    color: #9d174d;
    font: inherit;
    font-weight: 600;
    cursor: pointer;
    flex-shrink: 0;
}
.chat-mode-btn:disabled { opacity: 0.6; cursor: wait; }
.boutique-chat-wrapper button:focus-visible {
    outline: 3px solid #7c3aed;
    outline-offset: 3px;
}
.chat-ai-notice, .chat-error {
    margin-bottom: 8px;
    font-size: 0.76rem;
    line-height: 1.4;
}
.chat-ai-notice { color: #9d174d; }
.chat-error { color: #b42318; }
.chat-history { display: contents; }
.bubble-text { white-space: pre-wrap; overflow-wrap: anywhere; }

/* 4. Vùng tin nhắn Chat Messages */
.chat-messages-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 16px 14px;
    background: #faf7f9;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scroll-behavior: smooth;
}
.chat-messages-scroll::-webkit-scrollbar {
    width: 5px;
}
.chat-messages-scroll::-webkit-scrollbar-thumb {
    background: #f4a7b6;
    border-radius: 4px;
}

/* Welcome Card */
.chat-welcome-card {
    background: #ffffff;
    border: 1px solid #fce7f3;
    border-radius: 14px;
    padding: 14px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(225, 29, 72, 0.04);
    margin-bottom: 4px;
}
.welcome-flower {
    font-size: 1.6rem;
    margin-bottom: 4px;
}
.welcome-heading {
    font-size: 0.9rem;
    font-weight: 700;
    color: #be185d;
    margin-bottom: 4px;
}
.welcome-text {
    font-size: 0.8rem;
    color: #6b7280;
    line-height: 1.45;
    margin-bottom: 12px;
}
.quick-chips-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.quick-chip-btn {
    background: #fff5f8;
    border: 1px dashed #f472b6;
    color: #9d174d;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 7px 12px;
    border-radius: 20px;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
    width: 100%;
}
.quick-chip-btn:hover {
    background: #fce7f3;
    border-color: #db2777;
    transform: translateX(3px);
}

/* Date separator */
.chat-date-separator {
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 8px 0;
}
.chat-date-separator span {
    background: #eedde5;
    color: #6b4b57;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    letter-spacing: 0.3px;
}

/* Message Bubble Structures */
.chat-bubble-row {
    display: flex;
    gap: 8px;
    max-width: 86%;
    animation: fadeInBubble 0.2s ease;
}
@keyframes fadeInBubble {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Tin nhắn của Khách (Me - Gửi đi) */
.user-bubble-row {
    align-self: flex-end;
    flex-direction: row-reverse;
}
.user-bubble {
    background: linear-gradient(135deg, #e11d48 0%, #db2777 100%);
    color: #ffffff;
    border-radius: 16px 16px 4px 16px;
    padding: 10px 14px 7px 14px;
    box-shadow: 0 3px 12px rgba(219, 39, 119, 0.24);
    position: relative;
    word-break: break-word;
}
.user-bubble .bubble-text {
    font-size: 0.88rem;
    line-height: 1.45;
    color: #ffffff;
}
.user-bubble .bubble-time {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 3px;
    font-size: 0.68rem;
    color: rgba(255, 255, 255, 0.82);
    margin-top: 4px;
    font-variant-numeric: tabular-nums;
}

/* Tin nhắn của Admin / Tư vấn viên (Đến) */
.admin-bubble-row {
    align-self: flex-start;
}
.admin-mini-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #fce7f3;
    border: 1px solid #f472b6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    flex-shrink: 0;
    margin-top: 2px;
}
.admin-bubble {
    background: #ffffff;
    border: 1px solid #f3e8ee;
    border-radius: 16px 16px 16px 4px;
    padding: 9px 14px 7px 14px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    word-break: break-word;
}
.admin-bubble .bubble-author {
    font-size: 0.72rem;
    font-weight: 700;
    color: #be185d;
    margin-bottom: 2px;
    display: flex;
    align-items: center;
    gap: 4px;
}
.admin-bubble .bubble-text {
    font-size: 0.88rem;
    line-height: 1.45;
    color: #1f2937;
}
.admin-bubble .bubble-time {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    font-size: 0.68rem;
    color: #9ca3af;
    margin-top: 4px;
    font-variant-numeric: tabular-nums;
}

/* 5. Footer & Form nhập liệu */
.chat-window-footer {
    background: #ffffff;
    border-top: 1px solid #f3e8ee;
    padding: 10px 14px 8px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.02);
}
.chat-form-row {
    display: flex;
    align-items: center;
    gap: 8px;
}
.chat-input-wrapper {
    flex: 1;
    position: relative;
}
.chat-text-input {
    width: 100%;
    box-sizing: border-box;
    border-radius: 24px;
    border: 1.5px solid #f3e8ee;
    background: #faf8f9;
    padding: 10px 16px;
    font-size: 0.88rem;
    color: #1f2937;
    outline: none;
    transition: all 0.2s ease;
    font-family: inherit;
    resize: vertical;
    min-height: 42px;
    max-height: 100px;
    display: block;
    line-height: 1.4;
}
.chat-text-input:focus {
    background: #ffffff;
    border-color: #db2777;
    box-shadow: 0 0 0 3px rgba(219, 39, 119, 0.12);
}
.chat-text-input::placeholder {
    color: #9ca3af;
}
.chat-send-action-btn {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #e11d48 0%, #db2777 100%);
    border: none;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(219, 39, 119, 0.35);
    transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    flex-shrink: 0;
}
.chat-send-action-btn:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 16px rgba(219, 39, 119, 0.45);
}
.chat-send-action-btn:active {
    transform: scale(0.94);
}
.chat-send-action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}
.chat-footer-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 0.68rem;
    color: #6b7280;
    margin-top: 6px;
    letter-spacing: 0.2px;
    text-align: center;
    line-height: 1.4;
}
.note-dot {
    color: #d1d5db;
}

@media (max-width: 480px) {
    .boutique-chat-wrapper {
        bottom: 16px;
        right: 16px;
    }
    .chat-modal-window {
        width: calc(100vw - 32px);
        height: calc(100vh - 120px);
        bottom: 0;
    }
}
</style>

@auth
<script id="customer-chat-config"
    data-user-id="{{ Auth::id() }}"
    data-messages-url="{{ route('user.chat.messages') }}"
    data-send-url="{{ route('user.chat.send') }}"
    data-status-url="{{ route('user.chat.status') }}"
    data-mode-url="{{ route('user.chat.mode') }}"
    data-csrf="{{ csrf_token() }}"
    src="{{ asset('js/customer-chat.js') }}" defer></script>
@endauth
