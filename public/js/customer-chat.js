(() => {
    'use strict';
    const config = document.getElementById('customer-chat-config')?.dataset;
    if (!config) return;
    const el = (id) => document.getElementById(id);
    const popup = el('chat-popup');
    const toggle = el('chat-toggle');
    const input = el('chat-input');
    const history = el('chat-history');
    const scroll = el('chat-messages');
    const modeButton = el('chat-mode-toggle');
    let state = null;
    let sending = false;
    let refreshing = false;
    let changingMode = false;
    let version = 0;
    let rendered = '';

    function showError(message = '') {
        el('chat-error').textContent = message;
        el('chat-error').hidden = !message;
    }

    async function request(url, body) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(url, {
                method: body ? 'POST' : 'GET',
                credentials: 'same-origin',
                signal: controller.signal,
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf },
                ...(body ? { body: JSON.stringify(body) } : {}),
            });
            if (!response.ok) {
                const errors = {
                    401: 'Vui lòng đăng nhập lại để tiếp tục chat.',
                    419: 'Phiên làm việc hết hạn. Hãy tải lại trang.',
                    422: 'Nội dung không hợp lệ hoặc quá dài (tối đa 1.000 ký tự).',
                    429: 'Bạn thao tác quá nhanh. Vui lòng chờ một phút.',
                    503: 'Kênh hỗ trợ chưa sẵn sàng. Vui lòng thử lại sau.',
                };
                throw new Error(errors[response.status] || 'Không thể hoàn thành yêu cầu. Vui lòng thử lại.');
            }
            return await response.json();
        } finally {
            clearTimeout(timeout);
        }
    }

    function renderState(value) {
        state = value;
        const human = value.mode === 'human';
        el('chat-mode-label').textContent = human ? 'Hỗ trợ từ nhân viên' : (value.ai_available ? 'Trợ lý AI · Groq' : 'Hỗ trợ khách hàng');
        el('chat-assistant-label').textContent = value.pending ? 'AI đang soạn câu trả lời…' : 'Tư vấn mùi hương và mua hàng';
        modeButton.textContent = human ? 'Bật lại AI' : 'Gặp nhân viên';
        modeButton.hidden = human && !value.ai_available;
        const notice = value.pending ? 'AI đang trả lời… Bạn vẫn có thể gửi thêm thông tin.' : value.notice;
        el('chat-ai-notice').textContent = notice || '';
        el('chat-ai-notice').hidden = !notice;
    }

    function renderMessages(messages, forceScroll) {
        if (!Array.isArray(messages)) throw new Error('Không tải được lịch sử chat.');
        const signature = JSON.stringify(messages.map((m) => [m.id, m.content, m.is_ai]));
        if (signature === rendered) {
            if (forceScroll) scroll.scrollTop = scroll.scrollHeight;
            return;
        }
        const atBottom = scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 70;
        const fragment = document.createDocumentFragment();
        for (const message of messages) {
            const mine = String(message.sender_id) === config.userId;
            const row = document.createElement('div');
            row.className = `chat-bubble-row ${mine ? 'user' : 'admin'}-bubble-row`;
            const bubble = document.createElement('div');
            bubble.className = mine ? 'user-bubble' : 'admin-bubble';
            if (!mine) {
                const author = document.createElement('div');
                author.className = 'bubble-author';
                author.textContent = message.is_ai ? 'Trợ lý AI · Groq' : 'Nhân viên Hạ Thu';
                bubble.append(author);
            }
            const text = document.createElement('div');
            text.className = 'bubble-text';
            text.textContent = message.content;
            const time = document.createElement('div');
            time.className = 'bubble-time';
            const date = new Date(message.created_at);
            time.textContent = Number.isNaN(date.getTime()) ? '' : date.toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
            bubble.append(text, time);
            row.append(bubble);
            fragment.append(row);
        }
        history.replaceChildren(fragment);
        rendered = signature;
        if (forceScroll || atBottom) scroll.scrollTop = scroll.scrollHeight;
    }

    async function refresh(forceScroll = false) {
        if (refreshing) return;
        refreshing = true;
        const revision = version;
        try {
            const [messages, status] = await Promise.all([request(config.messagesUrl), request(config.statusUrl)]);
            if (revision === version) {
                renderMessages(messages, forceScroll);
                renderState(status);
            }
        } catch (error) {
            showError(error.name === 'AbortError' ? 'Kết nối chậm. Vui lòng thử lại.' : error.message);
        } finally {
            refreshing = false;
            if (revision !== version && popup.style.display !== 'none') refresh(forceScroll);
        }
    }

    async function send() {
        const message = input.value.trim();
        if (!message || sending) return;
        sending = true;
        input.disabled = true;
        el('send-btn').disabled = true;
        showError();
        try {
            await request(config.sendUrl, { message });
            input.value = '';
            version++;
            await refresh(true);
        } catch (error) {
            showError(error.name === 'AbortError'
                ? 'Chưa xác nhận được tin nhắn đã gửi. Kiểm tra lịch sử trước khi gửi lại.'
                : error.message);
        } finally {
            sending = false;
            input.disabled = false;
            el('send-btn').disabled = false;
            input.focus();
        }
    }

    toggle.addEventListener('click', () => {
        popup.style.display = 'flex';
        toggle.style.display = 'none';
        toggle.setAttribute('aria-expanded', 'true');
        refresh(true);
        input.focus();
    });
    function close() {
        popup.style.display = 'none';
        toggle.style.display = 'inline-flex';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
    }
    el('chat-close').addEventListener('click', close);
    popup.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
    el('chat-input-form').addEventListener('submit', (event) => { event.preventDefault(); send(); });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            send();
        }
    });
    document.querySelectorAll('.quick-chip-btn').forEach((chip) => {
        chip.addEventListener('click', () => { if (!sending) { input.value = chip.dataset.text; send(); } });
    });
    modeButton.addEventListener('click', async () => {
        if (!state || changingMode) return;
        changingMode = true;
        modeButton.disabled = true;
        showError();
        try {
            const next = await request(config.modeUrl, { mode: state.mode === 'human' ? 'ai' : 'human' });
            version++;
            renderState(next);
        } catch (error) {
            showError(error.name === 'AbortError' ? 'Chưa đổi được chế độ hỗ trợ. Vui lòng thử lại.' : error.message);
        } finally {
            changingMode = false;
            modeButton.disabled = false;
        }
    });
    setInterval(() => { if (!document.hidden && popup.style.display !== 'none') refresh(); }, 3000);
})();
