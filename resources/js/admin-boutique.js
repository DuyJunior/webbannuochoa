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
