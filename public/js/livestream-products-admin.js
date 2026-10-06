(() => {
    const form = document.getElementById('studio-product-form');
    const list = document.getElementById('studio-product-list');
    if (!form || !list) return;

    const select = document.getElementById('studio-product-select');
    const message = document.getElementById('studio-product-message');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const send = async (url, method, body) => {
        const response = await fetch(url, {
            method,
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json', ...(body ? { 'Content-Type': 'application/json' } : {}) },
            body: body ? JSON.stringify(body) : undefined,
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(result.message || (window.soopiT || (text => text))("Không thể cập nhật sản phẩm."));
        list.innerHTML = result.html;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!select.value) return;
        const button = form.querySelector('button');
        button.disabled = true;
        message.dataset.toastSource = 'info';
        message.textContent = (window.soopiT || (text => text))("Đang thêm sản phẩm...");
        try {
            await send(form.dataset.url, 'POST', { perfume_id: Number(select.value) });
            select.value = '';
            message.dataset.toastSource = 'success';
            message.textContent = (window.soopiT || (text => text))("Đã cập nhật sản phẩm cho khách xem.");
        } catch (error) {
            message.dataset.toastSource = 'error';
            message.textContent = error.message;
        } finally {
            button.disabled = false;
        }
    });

    list.addEventListener('click', async (event) => {
        const pin = event.target.closest('[data-pin-id]');
        if (pin) {
            pin.disabled = true;
            try {
                await send(form.dataset.pinUrl, 'PATCH', { perfume_id: pin.dataset.pinId ? Number(pin.dataset.pinId) : null });
                message.dataset.toastSource = 'success';
                message.textContent = pin.dataset.pinId ? (window.soopiT || (text => text))("Đã ghim sản phẩm nổi bật cho khách.") : (window.soopiT || (text => text))("Đã bỏ ghim sản phẩm.");
            } catch (error) {
                pin.disabled = false;
                message.dataset.toastSource = 'error';
                message.textContent = error.message;
            }
            return;
        }
        const button = event.target.closest('[data-remove-url]');
        if (!button) return;
        button.disabled = true;
        try {
            await send(button.dataset.removeUrl, 'DELETE');
            message.dataset.toastSource = 'success';
            message.textContent = (window.soopiT || (text => text))("Đã gỡ sản phẩm khỏi buổi live.");
        } catch (error) {
            button.disabled = false;
            message.dataset.toastSource = 'error';
            message.textContent = error.message;
        }
    });
})();
