import '../css/store-location.css';

document.querySelectorAll('[data-shop-location]').forEach(root => {
    const button = root.querySelector('[data-copy-shop-address]');
    if (!navigator.clipboard?.writeText || !button) return;
    button.hidden = false;
    button.addEventListener('click', async () => {
        const status = root.querySelector('[data-copy-status]');
        try {
            await navigator.clipboard.writeText(root.querySelector('[data-shop-address]').textContent.trim());
            status.textContent = 'Đã sao chép địa chỉ.';
        } catch { status.textContent = 'Bạn có thể chọn và sao chép dòng địa chỉ ở trên.'; }
    });
});
