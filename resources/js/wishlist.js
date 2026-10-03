import '../css/wishlist.css';

// Delegation also covers collection cards replaced by the in-page filters.
const pending = new Set();
const confirmed = new Map();
let notice;
let noticeTimer;

function notify(message, needsLogin = false, loginUrl = '') {
    if (!notice) {
        notice = document.createElement('aside');
        notice.className = 'wishlist-notice';
        notice.setAttribute('aria-label', (window.soopiT || (text => text))("Thông báo yêu thích"));
        const text = document.createElement('span');
        text.setAttribute('role', 'status');
        text.setAttribute('aria-live', 'polite');
        text.setAttribute('aria-atomic', 'true');
        const login = document.createElement('a');
        login.textContent = (window.soopiT || (text => text))("Đăng nhập");
        login.hidden = true;
        const close = document.createElement('button');
        close.type = 'button';
        close.setAttribute('aria-label', (window.soopiT || (text => text))("Đóng thông báo"));
        close.textContent = (window.soopiT || (text => text))("×");
        close.addEventListener('click', () => { notice.hidden = true; });
        notice.append(text, login, close);
        document.body.append(notice);
    }
    clearTimeout(noticeTimer);
    notice.hidden = false;
    notice.querySelector('[role="status"]').textContent = message;
    const login = notice.querySelector('a');
    login.hidden = !needsLogin;
    if (needsLogin) login.href = loginUrl;
    noticeTimer = setTimeout(() => {
        if (!notice.contains(document.activeElement)) notice.hidden = true;
    }, needsLogin ? 15000 : 6000);
}

function formsFor(id) {
    return [...document.querySelectorAll('form[data-wishlist]')]
        .filter(form => form.dataset.wishlist === id);
}

function setBusy(id, busy) {
    formsFor(id).forEach(form => {
        form.toggleAttribute('aria-busy', busy);
        if (busy) form.setAttribute('aria-busy', 'true');
        form.querySelector('button[type="submit"]')?.setAttribute('aria-disabled', String(busy));
    });
}

function syncSaved(id, saved) {
    confirmed.set(id, saved);
    formsFor(id).forEach(form => {
        form.dataset.wishlistSaved = String(saved);
        const button = form.querySelector('button[type="submit"]');
        button?.setAttribute('aria-pressed', String(saved));
        button?.setAttribute('aria-label', `${saved ? (window.soopiT || (text => text))("Bỏ yêu thích") : (window.soopiT || (text => text))("Yêu thích")} ${form.dataset.wishlistName || ''}`.trim());
        const label = form.querySelector('[data-wishlist-label]');
        if (label) label.textContent = saved ? (window.soopiT || (text => text))("Đã yêu thích") : (window.soopiT || (text => text))("Lưu yêu thích");
    });

    if (!saved) {
        document.querySelectorAll('[data-wishlist-list]').forEach(list => {
            const cards = [...list.querySelectorAll('[data-wishlist-card]')];
            const removed = cards.filter(card => card.dataset.wishlistCard === id);
            const moveFocus = removed.some(card => card.contains(document.activeElement));
            const next = cards.find(card => card.dataset.wishlistCard !== id);
            removed.forEach(card => card.remove());
            const empty = !list.querySelector('[data-wishlist-card]');
            list.hidden = empty;
            const section = list.closest('[data-wishlist-page]');
            const emptyPanel = section?.querySelector('[data-wishlist-empty]');
            if (emptyPanel) emptyPanel.hidden = !empty;
            if (moveFocus) (next?.querySelector('form[data-wishlist] button[type="submit"]') || emptyPanel?.querySelector('a'))?.focus({ preventScroll: true });
        });
    }
}

// A filter started before a save may return older HTML after the save finishes.
document.addEventListener('scent-gallery:updated', () => {
    confirmed.forEach((saved, id) => syncSaved(id, saved));
    pending.forEach(id => setBusy(id, true));
});

document.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-wishlist]')) return;
    event.preventDefault();
    const id = form.dataset.wishlist;
    if (pending.has(id)) return;
    const endpoint = new URL(form.action, location.href);
    if (endpoint.origin !== location.origin) return;

    const saved = form.dataset.wishlistSaved !== 'true';
    const body = new FormData(form);
    // An explicit desired state makes retrying a timed-out save safe.
    body.set('saved', saved ? '1' : '0');
    pending.add(id);
    setBusy(id, true);
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 15000);
    try {
        const response = await fetch(endpoint.href, {
            method: 'POST', body, credentials: 'same-origin', redirect: 'error',
            signal: controller.signal,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (response.status === 401 || response.status === 419) {
            notify((window.soopiT || (text => text))("Phiên đăng nhập đã hết hạn. Đăng nhập lại để lưu mùi hương."), true, form.dataset.wishlistLogin);
            return;
        }
        if (response.status === 404) {
            notify((window.soopiT || (text => text))("Mùi hương này hiện không còn được mở bán."));
            return;
        }
        if (!response.ok) throw new Error('save-failed');
        const result = await response.json();
        if (String(result.perfume_id) !== id || typeof result.saved !== 'boolean') throw new Error('invalid-response');
        syncSaved(id, result.saved);
        notify(result.saved ? (window.soopiT || (text => text))("Đã lưu mùi hương yêu thích.") : (window.soopiT || (text => text))("Đã bỏ mùi hương khỏi danh sách yêu thích."));
    } catch {
        notify((window.soopiT || (text => text))("Chưa lưu được thay đổi. Bạn bấm lại để thử nhé."));
    } finally {
        clearTimeout(timeout);
        pending.delete(id);
        setBusy(id, false);
    }
});
