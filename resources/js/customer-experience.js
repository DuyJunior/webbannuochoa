import '../css/customer-experience.css';
import '../css/order-archive.css';

document.querySelectorAll('[data-star-picker]').forEach(picker => {
    const inputs = [...picker.querySelectorAll('input')];
    const paint = value => picker.querySelectorAll('[data-star]').forEach(label => {
        label.classList.toggle('is-selected', Number(label.dataset.star) <= Number(value));
    });
    const restore = () => paint(inputs.find(input => input.checked)?.value || 0);
    picker.addEventListener('change', restore);
    picker.addEventListener('pointerover', event => {
        const label = event.target.closest('[data-star]');
        if (label) paint(label.dataset.star);
    });
    picker.addEventListener('pointerleave', restore);
    restore();
});

document.querySelectorAll('[data-review-upload]').forEach(upload => {
    const input = upload.querySelector('input[type=file]');
    const preview = upload.querySelector('[data-upload-preview]');
    const error = upload.querySelector('[data-upload-error]');
    const clear = upload.querySelector('[data-upload-clear]');
    let urls = [];
    const resetPreview = () => { urls.forEach(url => URL.revokeObjectURL(url)); urls = []; preview.replaceChildren(); };
    const render = () => {
        resetPreview();
        const files = [...input.files];
        const invalidType = files.some(file => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type));
        const invalidSize = files.length > 4 || files.some(file => file.size > 3 * 1024 * 1024);
        error.textContent = invalidType ? upload.dataset.typeError : invalidSize ? upload.dataset.error : '';
        input.setCustomValidity(error.textContent);
        clear.hidden = files.length === 0;
        if (invalidType || invalidSize) return;
        files.forEach(file => {
            const img = document.createElement('img');
            const url = URL.createObjectURL(file);
            urls.push(url); img.src = url; img.alt = file.name;
            preview.append(img);
        });
    };
    input.addEventListener('change', render);
    clear.addEventListener('click', () => { input.value = ''; render(); input.focus(); });
    ['dragenter', 'dragover'].forEach(name => upload.addEventListener(name, event => { event.preventDefault(); upload.classList.add('is-dragging'); }));
    upload.addEventListener('dragleave', event => { if (!upload.contains(event.relatedTarget)) upload.classList.remove('is-dragging'); });
    upload.addEventListener('drop', event => {
        event.preventDefault(); upload.classList.remove('is-dragging');
        if (event.dataTransfer?.files.length) { input.files = event.dataTransfer.files; render(); }
    });
    window.addEventListener('pagehide', resetPreview, { once: true });
});
