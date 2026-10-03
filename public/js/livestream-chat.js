(() => {
    const root = document.querySelector('[data-live-chat]');
    if (!root) return;

    const list = root.querySelector('[data-live-messages]');
    const form = root.querySelector('[data-live-form]');
    const input = form.querySelector('input');
    const feedback = root.querySelector('[data-live-feedback]');
    const watching = root.querySelector('[data-live-watching]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let previous = '';
    let joined = root.dataset.autoPresence === '1';
    let polling = false;

    const request = async (url, method = 'GET', body) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, ...(body ? { 'Content-Type': 'application/json' } : {}) },
            body: body ? JSON.stringify(body) : undefined,
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || (window.soopiT || (text => text))("Không thể cập nhật trò chuyện."));
        return result;
    };

    const render = (messages) => {
        const nearBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 90;
        list.replaceChildren();
        if (!messages.length) {
            const empty = document.createElement('p');
            empty.className = 'live-chat-empty';
            empty.textContent = (window.soopiT || (text => text))("Hãy gửi lời chào hoặc hỏi về mùi hương bạn thích");
            list.append(empty);
            return;
        }
        for (const item of messages) {
            const row = document.createElement('article');
            row.className = `live-chat-message${item.is_staff ? ' live-chat-message--staff' : ''}`;
            const meta = document.createElement('div');
            meta.className = 'live-chat-message-meta';
            const name = document.createElement('strong');
            name.textContent = item.display_name;
            meta.append(name);
            if (item.is_staff) {
                const badge = document.createElement('span');
                badge.textContent = 'Soopi';
                meta.append(badge);
            }
            if (root.dataset.hideBase) {
                const hide = document.createElement('button');
                hide.type = 'button';
                hide.dataset.hideId = item.id;
                hide.textContent = (window.soopiT || (text => text))("Ẩn");
                hide.setAttribute('aria-label', `${(window.soopiT || (text => text))("Ẩn bình luận của")} ${item.display_name}`);
                meta.append(hide);
            }
            const body = document.createElement('p');
            body.textContent = item.body;
            row.append(meta, body);
            list.append(row);
        }
        if (nearBottom) list.scrollTop = list.scrollHeight;
    };

    const refresh = async () => {
        if (document.hidden || polling) return;
        polling = true;
        try {
            const result = await request(root.dataset.listUrl);
            watching.textContent = result.watching;
            form.querySelector('button').disabled = !result.on_air;
            input.disabled = !result.on_air;
            input.placeholder = result.on_air ? (window.soopiT || (text => text))("Viết lời nhắn của bạn...") : (window.soopiT || (text => text))("Trò chuyện mở khi buổi live bắt đầu");
            const signature = JSON.stringify(result.messages);
            if (signature !== previous) {
                previous = signature;
                render(result.messages);
            }
        } catch (error) {
            feedback.textContent = error.message;
        } finally {
            polling = false;
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = input.value.trim();
        if (!body) return;
        const button = form.querySelector('button');
        button.disabled = true;
        try {
            await request(root.dataset.sendUrl, 'POST', { body });
            input.value = '';
            feedback.textContent = (window.soopiT || (text => text))("Đã gửi bình luận.");
            await refresh();
        } catch (error) {
            feedback.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    });

    list.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-hide-id]');
        if (!button || !root.dataset.hideBase) return;
        button.disabled = true;
        try {
            await request(`${root.dataset.hideBase}/${button.dataset.hideId}`, 'DELETE');
            feedback.textContent = (window.soopiT || (text => text))("Đã ẩn bình luận.");
            await refresh();
        } catch (error) {
            feedback.textContent = error.message;
            button.disabled = false;
        }
    });

    const presence = () => {
        if (joined && !document.hidden) request(root.dataset.presenceUrl, 'POST').catch(() => {});
    };
    document.addEventListener('ha-thu-live-viewer-joined', () => { joined = true; presence(); });
    document.addEventListener('ha-thu-live-viewer-left', () => { joined = false; });
    document.addEventListener('visibilitychange', () => { if (!document.hidden) { refresh(); presence(); } });
    refresh();
    presence();
    setInterval(refresh, 5000);
    setInterval(presence, 20000);
})();
