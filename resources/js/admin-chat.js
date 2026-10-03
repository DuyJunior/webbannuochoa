const root = document.getElementById('admin-chat-box');

if (root) {
    const find = id => root.querySelector(`#${id}`);
    const toggle = find('chat-toggle');
    const popup = find('chat-popup');
    const close = find('chat-close');
    const usersList = find('user-list');
    const messagesBox = find('chat-messages');
    const input = find('chat-input');
    const sendButton = find('send-btn');
    const search = find('chat-search-input');
    const clearSearch = find('btn-clear-search');
    const recentTab = find('chat-tab-recent');
    const allTab = find('chat-tab-all');
    const activeHeader = find('chat-active-header');
    const activeName = find('active-user-name');
    const errorBox = find('admin-chat-error');
    const adminId = Number(root.dataset.adminId);
    const drafts = new Map();
    const errors = new Map();
    let selectedId = null;
    let selectedName = '';
    let mode = 'recent';
    let opened = false;
    let sending = false;
    let searchTimer;
    let usersController;
    let messagesController;
    let usersRevision = 0;
    let messagesRevision = 0;
    let renderedMessages = null;

    const element = (tag, className, text) => {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = String(text);
        return node;
    };

    function displayErrors() {
        const message = errors.get(`send:${selectedId}`) || errors.get('messages') || errors.get('users') || '';
        errorBox.textContent = message;
        errorBox.hidden = !message;
    }

    function setError(key, message = '') {
        if (message) errors.set(key, message);
        else errors.delete(key);
        displayErrors();
    }

    function updateComposer() {
        input.disabled = !selectedId;
        sendButton.disabled = !selectedId || sending || !input.value.trim();
        sendButton.setAttribute('aria-busy', String(sending));
    }

    async function jsonResponse(response, fallback) {
        if (!response.ok) {
            const message = [401, 419].includes(response.status)
                ? (window.soopiT || (text => text))("Phiên đăng nhập đã hết hạn. Hãy tải lại trang để tiếp tục. Nội dung chưa gửi vẫn được giữ lại.")
                : response.status === 429 ? (window.soopiT || (text => text))("Bạn thao tác quá nhanh. Vui lòng thử lại sau ít giây.") : fallback;
            throw new Error(message);
        }
        try {
            return await response.json();
        } catch {
            throw new Error(fallback);
        }
    }

    function highlightUser() {
        usersList.querySelectorAll('button[data-user-id]').forEach(button => {
            const active = Number(button.dataset.userId) === selectedId;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', String(active));
        });
    }

    async function loadUsers() {
        usersController?.abort();
        const controller = new AbortController();
        usersController = controller;
        const revision = ++usersRevision;
        const url = new URL(root.dataset.usersUrl, window.location.href);
        const keyword = search.value.trim();
        if (keyword) url.searchParams.set('search', keyword);
        else if (mode === 'all') url.searchParams.set('mode', 'all');
        usersList.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
            const users = await jsonResponse(response, (window.soopiT || (text => text))("Không thể tải danh sách khách hàng. Vui lòng thử lại."));
            if (revision !== usersRevision || !opened) return;
            if (!Array.isArray(users)) throw new Error((window.soopiT || (text => text))("Danh sách khách hàng chưa tải được. Vui lòng thử lại."));
            const fragment = document.createDocumentFragment();
            users.forEach(user => {
                const id = Number(user.id);
                if (!Number.isSafeInteger(id) || id <= 0) return;
                const name = String(user.name || (window.soopiT || (text => text))("Khách hàng"));
                const button = element('button', 'user-item', name);
                button.type = 'button';
                button.dataset.userId = String(id);
                button.title = `${name}${user.email ? ` (${user.email})` : ''}`;
                button.setAttribute('aria-label', `${(window.soopiT || (text => text))("Trò chuyện với")} ${name}`);
                button.addEventListener('click', () => selectUser(id, name));
                fragment.append(button);
            });
            if (!fragment.childNodes.length) fragment.append(element('div', 'p-2 text-muted small w-100 text-center', (window.soopiT || (text => text))("Không tìm thấy khách hàng nào")));
            usersList.replaceChildren(fragment);
            highlightUser();
            setError('users');
        } catch (error) {
            if (error.name !== 'AbortError' && revision === usersRevision && opened) {
                setError('users', error.message || (window.soopiT || (text => text))("Không thể tải danh sách khách hàng."));
            }
        } finally {
            if (revision === usersRevision) {
                usersController = null;
                usersList.setAttribute('aria-busy', 'false');
            }
        }
    }

    function formatTime(value) {
        if (!value) return '';
        const normalized = String(value).trim().replace(/^(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2}:\d{2})/, '$1T$2');
        const date = new Date(normalized);
        if (Number.isNaN(date.getTime())) return '';
        const now = new Date();
        const time = `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
        if (date.toDateString() === now.toDateString()) return time;
        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        if (date.toDateString() === yesterday.toDateString()) return `${(window.soopiT || (text => text))("Hôm qua")} ${time}`;
        return `${String(date.getDate()).padStart(2, '0')}/${String(date.getMonth() + 1).padStart(2, '0')} ${time}`;
    }

    async function loadMessages({ reset = false, force = false } = {}) {
        if (!selectedId || !opened || (messagesController && !force)) return;
        messagesController?.abort();
        const controller = new AbortController();
        messagesController = controller;
        const revision = ++messagesRevision;
        const userId = selectedId;
        const userName = selectedName;
        messagesBox.setAttribute('aria-busy', 'true');
        try {
            const url = root.dataset.messagesUrl.replace('__USER__', encodeURIComponent(userId));
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal });
            const messages = await jsonResponse(response, (window.soopiT || (text => text))("Không thể tải hội thoại. Hệ thống sẽ thử lại sau ít giây."));
            if (revision !== messagesRevision || userId !== selectedId || !opened) return;
            if (!Array.isArray(messages)) throw new Error((window.soopiT || (text => text))("Hội thoại chưa tải được. Hệ thống sẽ thử lại sau ít giây."));
            const fingerprint = JSON.stringify(messages);
            if (fingerprint !== renderedMessages) {
                const wasNearBottom = messagesBox.scrollHeight - messagesBox.scrollTop - messagesBox.clientHeight < 70;
                const previousTop = messagesBox.scrollTop;
                const fragment = document.createDocumentFragment();
                messages.forEach(message => {
                    const isAi = message.is_ai === true || message.is_ai === 1 || message.is_ai === '1';
                    const isAdmin = isAi || message.sender?.role === 'admin' || Number(message.sender_id) === adminId;
                    const author = isAi ? (window.soopiT || (text => text))("Trợ lý AI · Groq") : Number(message.sender_id) === adminId ? (window.soopiT || (text => text))("Bạn (Admin)") : message.sender?.name || (window.soopiT || (text => text))("Khách hàng");
                    const row = element('div', `msg-row ${isAdmin ? 'msg-admin' : 'msg-customer'}`);
                    const meta = element('div', 'msg-meta-header');
                    meta.append(element('span', 'msg-author', author), element('span', 'msg-time', formatTime(message.created_at)));
                    const content = element('div', 'studio-chat-message-content', message.content || '');
                    content.style.whiteSpace = 'pre-wrap';
                    content.style.overflowWrap = 'anywhere';
                    content.style.marginTop = '2px';
                    row.append(meta, content);
                    fragment.append(row);
                });
                if (!messages.length) {
                    const empty = element('div', 'text-center mt-4 text-muted small');
                    empty.append(element('p', '', `${(window.soopiT || (text => text))("Chưa có tin nhắn nào với")} ${userName}.`), element('p', 'text-primary', (window.soopiT || (text => text))("Nhập nội dung bên dưới để bắt đầu trò chuyện.")));
                    fragment.append(empty);
                }
                messagesBox.replaceChildren(fragment);
                messagesBox.scrollTop = reset || wasNearBottom ? messagesBox.scrollHeight : previousTop;
                renderedMessages = fingerprint;
            }
            setError('messages');
        } catch (error) {
            if (error.name !== 'AbortError' && revision === messagesRevision && userId === selectedId && opened) {
                setError('messages', error.message || (window.soopiT || (text => text))("Không thể tải hội thoại."));
            }
        } finally {
            if (revision === messagesRevision) {
                messagesController = null;
                messagesBox.setAttribute('aria-busy', 'false');
            }
        }
    }

    function selectUser(rawId, name) {
        const id = Number(rawId);
        if (!Number.isSafeInteger(id) || id <= 0 || id === adminId) return;
        if (selectedId) drafts.set(selectedId, input.value);
        const changed = id !== selectedId;
        selectedId = id;
        selectedName = String(name || (window.soopiT || (text => text))("Khách hàng"));
        input.value = drafts.get(id) || '';
        input.placeholder = `${(window.soopiT || (text => text))("Nhắn tin cho")} ${selectedName}…`;
        activeName.textContent = selectedName;
        activeHeader.style.display = 'block';
        highlightUser();
        updateComposer();
        setError('messages');
        if (changed) {
            renderedMessages = null;
            messagesBox.replaceChildren(element('p', 'p-3 text-center text-muted small', (window.soopiT || (text => text))("Đang tải hội thoại…")));
        }
        loadMessages({ reset: changed, force: true });
        input.focus();
    }

    function openChat() {
        opened = true;
        popup.style.display = 'flex';
        toggle.setAttribute('aria-expanded', 'true');
        loadUsers();
        if (selectedId) loadMessages({ force: true });
    }

    function closeChat() {
        opened = false;
        popup.style.display = 'none';
        toggle.setAttribute('aria-expanded', 'false');
        usersController?.abort();
        messagesController?.abort();
        clearTimeout(searchTimer);
        toggle.focus();
    }

    async function sendMessage() {
        const rawMessage = input.value;
        const message = rawMessage.trim();
        if (!message || !selectedId || sending || !opened) return;
        const userId = selectedId;
        drafts.set(userId, rawMessage);
        sending = true;
        updateComposer();
        setError(`send:${userId}`);
        try {
            const response = await fetch(root.dataset.sendUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ user_id: userId, message }),
            });
            const sent = await jsonResponse(response, (window.soopiT || (text => text))("Không thể gửi tin nhắn. Nội dung vẫn được giữ lại để bạn thử lại."));
            if (!sent?.id) throw new Error((window.soopiT || (text => text))("Chưa xác nhận tin nhắn đã gửi. Hãy kiểm tra hội thoại trước khi thử lại."));
            if (drafts.get(userId) === rawMessage) drafts.delete(userId);
            if (selectedId === userId) {
                if (input.value === rawMessage) input.value = '';
                loadMessages({ force: true });
            }
            if (opened) loadUsers();
        } catch (error) {
            setError(`send:${userId}`, error instanceof TypeError
                ? (window.soopiT || (text => text))("Chưa xác nhận tin nhắn đã gửi. Nội dung vẫn được giữ lại; hãy kiểm tra hội thoại trước khi thử lại.")
                : error.message || (window.soopiT || (text => text))("Không thể gửi tin nhắn. Nội dung vẫn được giữ lại."));
        } finally {
            sending = false;
            updateComposer();
        }
    }

    function setMode(nextMode) {
        mode = nextMode;
        recentTab.classList.toggle('active', mode === 'recent');
        allTab.classList.toggle('active', mode === 'all');
        recentTab.setAttribute('aria-pressed', String(mode === 'recent'));
        allTab.setAttribute('aria-pressed', String(mode === 'all'));
        search.value = '';
        clearSearch.style.display = 'none';
        clearTimeout(searchTimer);
        loadUsers();
    }

    window.selectUser = selectUser;
    window.openChatWithUser = (id, name) => { openChat(); selectUser(id, name); };
    toggle.addEventListener('click', () => { if (opened) closeChat(); else { openChat(); search.focus(); } });
    close.addEventListener('click', closeChat);
    popup.addEventListener('keydown', event => { if (event.key === 'Escape') { event.stopPropagation(); closeChat(); } });
    recentTab.addEventListener('click', () => setMode('recent'));
    allTab.addEventListener('click', () => setMode('all'));
    recentTab.setAttribute('aria-pressed', 'true');
    allTab.setAttribute('aria-pressed', 'false');
    search.addEventListener('input', () => {
        clearSearch.style.display = search.value ? 'inline-block' : 'none';
        clearTimeout(searchTimer);
        usersController?.abort();
        usersRevision++;
        searchTimer = setTimeout(loadUsers, 300);
    });
    clearSearch.addEventListener('click', () => { search.value = ''; clearSearch.style.display = 'none'; clearTimeout(searchTimer); loadUsers(); search.focus(); });
    input.addEventListener('input', () => { if (selectedId) drafts.set(selectedId, input.value); updateComposer(); });
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.isComposing && event.keyCode !== 229) { event.preventDefault(); sendMessage(); }
    });
    sendButton.addEventListener('click', sendMessage);
    messagesBox.setAttribute('role', 'log');
    messagesBox.setAttribute('aria-label', (window.soopiT || (text => text))("Lịch sử hội thoại"));
    messagesBox.setAttribute('aria-live', 'off');
    messagesBox.setAttribute('tabindex', '0');
    updateComposer();
    const startPolling = () => setInterval(() => { if (opened && selectedId && !document.hidden) loadMessages(); }, 3000);
    let poll = startPolling();
    window.addEventListener('pagehide', () => { clearInterval(poll); clearTimeout(searchTimer); usersController?.abort(); messagesController?.abort(); });
    window.addEventListener('pageshow', event => { if (event.persisted) { clearInterval(poll); poll = startPolling(); if (opened) openChat(); } });
}
