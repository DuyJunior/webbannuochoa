const toggle = document.querySelector('.ht-admin-toggle');
const backdrop = document.querySelector('.ht-admin-backdrop');
const sidebar = document.getElementById('admin-sidebar');
function setMenu(open) {
    document.body.classList.toggle('admin-menu-open', open);
    toggle?.setAttribute('aria-expanded', String(open));
    toggle?.setAttribute('aria-label', open ? 'Đóng menu quản trị' : 'Mở menu quản trị');
    if (backdrop) backdrop.hidden = !open;
    if (sidebar) sidebar.inert = !open && window.innerWidth <= 991;
}
toggle?.addEventListener('click', () => setMenu(!document.body.classList.contains('admin-menu-open')));
backdrop?.addEventListener('click', () => { setMenu(false); toggle?.focus(); });
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.body.classList.contains('admin-menu-open')) {
        setMenu(false);
        toggle?.focus();
    }
});
window.addEventListener('resize', () => setMenu(false));
setMenu(false);

document.querySelectorAll('[data-chat-user]').forEach(button => button.addEventListener('click', () => {
    window.openChatWithUser?.(Number(button.dataset.chatUser), button.dataset.chatName);
}));
document.querySelectorAll('.studio-confirm-delete').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirmMessage)) event.preventDefault();
}));

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
const precisePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
let motionEnabled = true;
try { motionEnabled = localStorage.getItem('soopi-admin-motion') !== 'off'; } catch { /* Storage may be restricted. */ }
function syncMotion() {
    const enabled = motionEnabled && !reducedMotion.matches;
    document.body.classList.toggle('studio-motion-off', !enabled);
    motionToggle?.setAttribute('aria-pressed', String(enabled));
    motionToggle?.setAttribute('aria-label', enabled ? 'Tắt hiệu ứng chuyển động' : 'Bật hiệu ứng chuyển động');
    if (motionToggle) {
        motionToggle.disabled = reducedMotion.matches;
        motionToggle.title = reducedMotion.matches ? 'Hiệu ứng đã tắt theo cài đặt giảm chuyển động của thiết bị' : (enabled ? 'Tắt hiệu ứng chuyển động' : 'Bật hiệu ứng chuyển động');
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

// Small tilts only on summary cards. Tables, forms and video controls stay still.
document.querySelectorAll('.studio-metric, .finance-metric, .live-admin-metric, .studio-function-metric').forEach(card => {
    let frame = 0;
    function resetDepth() {
        cancelAnimationFrame(frame);
        frame = 0;
        card.classList.remove('studio-depth-active');
    }
    card.addEventListener('pointermove', event => {
        if (!motionEnabled || reducedMotion.matches || !precisePointer.matches || event.pointerType === 'touch') return;
        const x = event.clientX, y = event.clientY;
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => {
            const bounds = card.getBoundingClientRect();
            card.style.setProperty('--tilt-x', `${(0.5 - (y - bounds.top) / bounds.height) * 4}deg`);
            card.style.setProperty('--tilt-y', `${((x - bounds.left) / bounds.width - 0.5) * 4}deg`);
            card.classList.add('studio-depth-active');
        });
    });
    card.addEventListener('pointerleave', resetDepth);
    card.addEventListener('pointercancel', resetDepth);
    card.addEventListener('blur', resetDepth);
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
