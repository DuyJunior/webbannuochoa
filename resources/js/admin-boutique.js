import './admin-chat.js';
import './admin-shop-location.js';

const toggle = document.querySelector('.ht-admin-toggle');
const backdrop = document.querySelector('.ht-admin-backdrop');
const sidebar = document.getElementById('admin-sidebar');
const menuBreakpoint = window.matchMedia('(max-width: 991px)');
function setMenu(open) {
    open = open && menuBreakpoint.matches;
    document.body.classList.toggle('admin-menu-open', open);
    toggle?.setAttribute('aria-expanded', String(open));
    toggle?.setAttribute('aria-label', open ? (window.soopiT || (text => text))("Đóng menu quản trị") : (window.soopiT || (text => text))("Mở menu quản trị"));
    if (backdrop) backdrop.hidden = !open;
    if (sidebar) sidebar.inert = !open && menuBreakpoint.matches;
    document.querySelector('.admin-main-wrapper')?.toggleAttribute('inert', open);
    document.getElementById('admin-chat-box')?.toggleAttribute('inert', open);
    if (open) sidebar?.querySelector('.studio-sidebar-close')?.focus();
}
toggle?.addEventListener('click', () => setMenu(!document.body.classList.contains('admin-menu-open')));
backdrop?.addEventListener('click', () => { setMenu(false); toggle?.focus(); });
sidebar?.querySelector('.studio-sidebar-close')?.addEventListener('click', () => { setMenu(false); toggle?.focus(); });
sidebar?.addEventListener('keydown', event => {
    if (event.key !== 'Tab' || !document.body.classList.contains('admin-menu-open')) return;
    const items = [...sidebar.querySelectorAll('a[href],button:not(:disabled)')].filter(el => el.getClientRects().length);
    const first = items[0], last = items.at(-1);
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last?.focus(); }
    if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first?.focus(); }
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.body.classList.contains('admin-menu-open')) {
        setMenu(false);
        toggle?.focus();
    }
});
menuBreakpoint.addEventListener('change', () => setMenu(false));
setMenu(false);

document.querySelectorAll('[data-chat-user]').forEach(button => button.addEventListener('click', () => {
    window.openChatWithUser?.(Number(button.dataset.chatUser), button.dataset.chatName);
}));
// Request confirmation before the browser submits; retain the original submitter value.
const confirmation = document.getElementById('studio-confirm');
let pendingConfirmation = null;
const approvedForms = new WeakSet();
document.addEventListener('submit', event => {
    const form = event.target;
    const message = form.dataset.confirm || form.dataset.confirmMessage;
    if (!message || event.defaultPrevented) return;
    if (approvedForms.has(form)) { approvedForms.delete(form); return; }
    if (!confirmation?.showModal) { if (!window.confirm(message)) event.preventDefault(); return; }
    event.preventDefault();
    if (confirmation.open) return;
    pendingConfirmation = { form, submitter: event.submitter, trigger: document.activeElement };
    confirmation.querySelector('#studio-confirm-message').textContent = message;
    confirmation.showModal();
    confirmation.querySelector('[data-confirm-cancel]').focus();
});
confirmation?.querySelector('[data-confirm-cancel]')?.addEventListener('click', () => confirmation.close());
confirmation?.querySelector('[data-confirm-accept]')?.addEventListener('click', () => {
    const pending = pendingConfirmation;
    if (!pending) return;
    confirmation.close();
    pendingConfirmation = null;
    approvedForms.add(pending.form);
    if (pending.submitter?.isConnected && !pending.submitter.disabled) pending.form.requestSubmit(pending.submitter);
    else pending.form.requestSubmit();
    approvedForms.delete(pending.form);
});
confirmation?.addEventListener('close', () => { pendingConfirmation?.trigger?.focus(); pendingConfirmation = null; });
confirmation?.addEventListener('click', event => {
    if (event.target !== confirmation) return;
    const r = confirmation.getBoundingClientRect();
    if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) confirmation.close();
});

// The launcher mirrors authorized sidebar links; it never invents destinations.
const commandMenu = document.getElementById('studio-command-menu');
const commandInput = document.getElementById('studio-command-input');
const commandResults = document.getElementById('studio-command-results');
const commandOpen = document.querySelector('.studio-command-open');
if (commandMenu && commandInput && commandResults && commandOpen) {
    const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/gi, 'd').toLowerCase();
    const destinations = [...document.querySelectorAll('.sidebar-menu a')].map(link => ({ url: link.href, text: link.textContent.trim(), icon: link.querySelector('i')?.className || 'fa-solid fa-arrow-right' }));
    let commandReturnFocus;
    function renderCommands() {
        const query = normalize(commandInput.value.trim());
        commandResults.replaceChildren();
        destinations.filter(item => normalize(item.text).includes(query)).forEach(item => {
            const link = document.createElement('a'), icon = document.createElement('i'), label = document.createElement('span');
            link.href = item.url;
            icon.className = item.icon; icon.setAttribute('aria-hidden', 'true');
            label.textContent = item.text;
            link.append(icon, label);
            commandResults.append(link);
        });
        document.getElementById('studio-command-empty').hidden = commandResults.children.length > 0;
    }
    function openCommands() {
        if (commandMenu.open) return;
        commandReturnFocus = document.activeElement;
        commandInput.value = ''; renderCommands(); commandMenu.showModal(); commandInput.focus();
    }
    commandOpen.addEventListener('click', openCommands);
    commandInput.addEventListener('input', renderCommands);
    commandMenu.querySelector('[data-command-close]').addEventListener('click', () => commandMenu.close());
    commandMenu.addEventListener('close', () => commandReturnFocus?.focus());
    commandMenu.addEventListener('click', event => { if (event.target === commandMenu) { const r = commandMenu.getBoundingClientRect(); if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) commandMenu.close(); } });
    commandMenu.addEventListener('keydown', event => {
        const links = [...commandResults.querySelectorAll('a')];
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault(); const index = links.indexOf(document.activeElement);
            if (links.length) {
                const next = index < 0 ? (event.key === 'ArrowDown' ? 0 : links.length - 1) : (index + (event.key === 'ArrowDown' ? 1 : -1) + links.length) % links.length;
                links[next].focus();
            }
        }
        if (event.key === 'Enter' && event.target === commandInput && links.length) { event.preventDefault(); links[0].click(); }
    });
    document.addEventListener('keydown', event => { if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') { event.preventDefault(); openCommands(); } });
}

// Respect the system preference and remember the user's own motion setting.
const motionToggle = document.querySelector('.studio-motion-toggle');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let motionEnabled = true;
try { motionEnabled = localStorage.getItem('soopi-admin-motion') !== 'off'; } catch { /* Storage may be restricted. */ }
function syncMotion() {
    const enabled = motionEnabled && !reducedMotion.matches;
    document.body.classList.toggle('studio-motion-off', !enabled);
    motionToggle?.setAttribute('aria-pressed', String(enabled));
    motionToggle?.setAttribute('aria-label', enabled ? (window.soopiT || (text => text))("Tắt hiệu ứng chuyển động") : (window.soopiT || (text => text))("Bật hiệu ứng chuyển động"));
    if (motionToggle) {
        motionToggle.disabled = reducedMotion.matches;
        motionToggle.title = reducedMotion.matches ? (window.soopiT || (text => text))("Hiệu ứng đã tắt theo cài đặt giảm chuyển động của thiết bị") : (enabled ? (window.soopiT || (text => text))("Tắt hiệu ứng chuyển động") : (window.soopiT || (text => text))("Bật hiệu ứng chuyển động"));
    }
    document.querySelectorAll('.studio-depth-active').forEach(card => card.classList.remove('studio-depth-active'));
}
motionToggle?.addEventListener('click', () => {
    motionEnabled = !motionEnabled;
    try { localStorage.setItem('soopi-admin-motion', motionEnabled ? 'on' : 'off'); } catch { /* Keep this session's setting. */ }
    syncMotion();
});
reducedMotion.addEventListener('change', syncMotion);
syncMotion();

// Missing uploads get a neutral placeholder instead of broken-image text.
document.querySelectorAll('.admin-content-area img').forEach(img => {
    function showPlaceholder() {
        if (img.classList.contains('studio-media-unavailable')) return;
        img.classList.add('studio-media-unavailable');
        img.src = '/images/admin/media-unavailable.svg';
    }
    img.addEventListener('error', showPlaceholder);
    if (img.complete && !img.naturalWidth && img.getAttribute('src')) showPlaceholder();
});

// Give simple management tables a readable, labelled layout on narrow screens.
document.querySelectorAll('.admin-content-area .table-responsive > table').forEach(table => {
    if (table.classList.contains('order-table-populated') || table.classList.contains('order-table-empty')) return;
    const rows = table.tHead?.rows;
    if (!rows || rows.length !== 1) return;
    const headings = Array.from(rows[0].cells, cell => cell.textContent.trim());
    if (Array.from(rows[0].cells).some(cell => cell.colSpan !== 1 || cell.querySelector('input'))) return;
    table.classList.add('studio-responsive-table');
    Array.from(table.tBodies).forEach(body => Array.from(body.rows).forEach(row => {
        if (row.cells.length !== headings.length) return;
        Array.from(row.cells).forEach((cell, index) => {
            if (headings[index]) cell.dataset.column = headings[index];
        });
    }));
});

// Errors stay visible until the operator has corrected the form.
document.querySelector('[data-validation-summary]')?.focus();
document.querySelectorAll('.table-responsive').forEach(region => {
    region.tabIndex = 0;
    region.setAttribute('role','region');
    if (!region.hasAttribute('aria-label')) region.setAttribute('aria-label',(window.soopiT || (text => text))("Bảng dữ liệu — có thể cuộn ngang trên màn hình nhỏ"));
});
