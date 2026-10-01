import '../css/product-quick-view.css';

const dialog = document.querySelector('[data-quick-view-dialog]');

if (dialog && typeof dialog.showModal === 'function') {
    const find = name => dialog.querySelector(`[data-qv-${name}]`);
    const status = find('status');
    const product = find('product');
    const loading = find('loading');
    const errorPanel = find('error');
    const form = find('form');
    const quantity = find('quantity');
    const submit = find('submit');
    const login = find('login');
    const image = find('image');
    const placeholder = find('image-fallback');
    const currency = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 });
    let request = null;
    let serial = 0;
    let source = null;
    let opener = null;
    let scrollPosition = null;
    let current = null;
    let selected = null;
    let lockWasPresent = false;

    function sameOriginUrl(value) {
        try {
            const url = new URL(value, location.href);
            return url.origin === location.origin && /^https?:$/.test(url.protocol) ? url.href : null;
        } catch { return null; }
    }

    function updateQuantityButtons() {
        const amount = Number(quantity.value);
        find('minus').disabled = !selected?.max_quantity || !Number.isFinite(amount) || amount <= 1;
        find('plus').disabled = !selected?.max_quantity || !Number.isFinite(amount) || amount >= selected.max_quantity;
    }

    function selectVariant(variant) {
        selected = variant;
        const canAdd = variant.max_quantity > 0;
        const oldAmount = Number(quantity.value) || 1;
        quantity.value = String(Math.max(1, Math.min(oldAmount, variant.max_quantity || 1)));
        quantity.max = String(variant.max_quantity || 1);
        quantity.disabled = !canAdd;
        find('price').textContent = currency.format(variant.price);
        find('original').hidden = !variant.original_price;
        find('original').textContent = variant.original_price ? currency.format(variant.original_price) : '';
        find('stock').textContent = variant.stock < 1
            ? 'Dung tích này đang hết hàng.'
            : !canAdd
                ? 'Số lượng trong giỏ đã đạt giới hạn hiện có.'
                : `Còn ${variant.stock} sản phẩm${variant.in_cart ? ` · ${variant.in_cart} trong giỏ của bạn` : ''}`;
        find('stock').dataset.unavailable = String(!canAdd);
        submit.hidden = !current.authenticated;
        submit.disabled = !canAdd;
        login.hidden = current.authenticated || !canAdd;
        find('signin-note').hidden = current.authenticated || !canAdd;
        find('sold-out').hidden = canAdd;
        updateQuantityButtons();
    }

    function render(data) {
        if (!Array.isArray(data.variants) || !data.variants.length || !data.name) throw new Error('invalid-product');
        const productUrl = sameOriginUrl(data.product_url);
        const cartUrl = sameOriginUrl(data.cart_url);
        const loginUrl = sameOriginUrl(data.login_url);
        if (!productUrl || !cartUrl || !loginUrl) throw new Error('invalid-url');
        if (!data.variants.every(item => Number.isInteger(item.volume) && item.volume > 0
            && Number.isFinite(item.price) && item.price >= 0 && Number.isInteger(item.max_quantity) && item.max_quantity >= 0)) {
            throw new Error('invalid-variant');
        }

        current = data;
        find('brand').textContent = data.brand || 'Soopi';
        find('title').textContent = data.name;
        find('concentration').textContent = data.concentration || '';
        find('concentration').hidden = !data.concentration;
        find('description').textContent = data.description || '';
        find('description').hidden = !data.description;
        find('detail').href = productUrl;
        find('fallback').href = productUrl;
        form.action = cartUrl;
        find('token').value = data.csrf_token;
        login.href = loginUrl;
        image.hidden = true;
        placeholder.hidden = false;
        image.removeAttribute('src');
        image.alt = data.name;
        find('image-caption').hidden = !data.image_is_editorial;
        if (data.image) {
            try {
                const photo = new URL(data.image, location.href);
                if (/^https?:$/.test(photo.protocol)) image.src = photo.href;
            } catch { /* Keep the decorative flower for an unavailable image. */ }
        }

        const first = data.variants.find(variant => variant.max_quantity > 0) || data.variants[0];
        const options = data.variants.map(variant => {
            const label = document.createElement('label');
            label.className = 'qv-variant';
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'volume_ml';
            input.value = String(variant.volume);
            input.checked = variant === first;
            input.disabled = variant.max_quantity < 1;
            const text = document.createElement('span');
            text.textContent = variant.label;
            if (variant.max_quantity < 1) {
                const note = document.createElement('small');
                note.textContent = variant.stock < 1 ? 'Hết hàng' : 'Đã đủ trong giỏ';
                text.append(note);
            }
            input.addEventListener('change', () => selectVariant(variant));
            label.append(input, text);
            return label;
        });
        find('variants').replaceChildren(...options);
        quantity.value = '1';
        submit.replaceChildren(document.createTextNode('Thêm vào giỏ hàng '));
        const arrow = document.createElement('span');
        arrow.setAttribute('aria-hidden', 'true');
        arrow.textContent = '↗';
        submit.append(arrow);
        selectVariant(first);
        product.hidden = false;
    }

    async function load() {
        request?.abort();
        const controller = new AbortController();
        request = controller;
        const id = ++serial;
        let timedOut = false;
        const timeout = setTimeout(() => { timedOut = true; controller.abort(); }, 18000);
        current = null;
        selected = null;
        product.hidden = true;
        errorPanel.hidden = true;
        loading.hidden = false;
        status.textContent = 'Đang mở mùi hương của bạn…';
        dialog.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(source.endpoint, {
                signal: controller.signal, credentials: 'same-origin', cache: 'no-store',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error(response.status === 404 ? 'unavailable' : 'network');
            const data = await response.json();
            if (id !== serial || !dialog.open) return;
            render(data);
            status.textContent = '';
        } catch (error) {
            if (id !== serial || !dialog.open || (error.name === 'AbortError' && !timedOut)) return;
            status.textContent = error.message === 'unavailable'
                ? 'Sản phẩm này hiện không còn được mở bán.'
                : 'Chưa tải được sản phẩm. Bạn thử lại nhé.';
            errorPanel.hidden = false;
        } finally {
            clearTimeout(timeout);
            if (id === serial) {
                loading.hidden = true;
                dialog.removeAttribute('aria-busy');
                request = null;
            }
        }
    }

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-quick-view]');
        if (!trigger || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const endpoint = sameOriginUrl(trigger.dataset.quickView);
        const fallback = sameOriginUrl(trigger.href);
        if (!endpoint || !fallback) return;
        event.preventDefault();
        source = { endpoint, fallback };
        opener = trigger;
        find('fallback').href = fallback;
        find('title').textContent = trigger.dataset.productName || 'Xem nhanh mùi hương';
        if (!dialog.open) {
            scrollPosition = { x: scrollX, y: scrollY };
            lockWasPresent = document.documentElement.classList.contains('qv-is-open');
            document.documentElement.classList.add('qv-is-open');
            dialog.showModal();
        }
        // Native dialog keeps keyboard focus inside; the close control remains the initial focus.
        find('close').focus({ preventScroll: true });
        dialog.querySelector('.qv-scroll').scrollTop = 0;
        load();
    });

    find('close').addEventListener('click', () => dialog.close());
    find('retry').addEventListener('click', load);
    dialog.addEventListener('keydown', event => {
        if (event.key !== 'Tab') return;
        const focusable = [...dialog.querySelectorAll('a[href], button:not(:disabled), input:not(:disabled):not([type="hidden"]), [tabindex="0"]')]
            .filter(element => element.getClientRects().length && getComputedStyle(element).visibility !== 'hidden');
        const first = focusable[0];
        const last = focusable.at(-1);
        if (!first) return;
        if (event.shiftKey && (document.activeElement === first || !dialog.contains(document.activeElement))) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || !dialog.contains(document.activeElement))) {
            event.preventDefault();
            first.focus();
        }
    });
    let backdropDown = false;
    dialog.addEventListener('pointerdown', event => {
        const bounds = dialog.getBoundingClientRect();
        backdropDown = event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom);
    });
    dialog.addEventListener('click', event => {
        const bounds = dialog.getBoundingClientRect();
        const outside = event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom;
        if (event.target === dialog && backdropDown && outside) dialog.close();
        backdropDown = false;
    });
    dialog.addEventListener('close', () => {
        ++serial;
        request?.abort();
        request = null;
        if (!lockWasPresent) document.documentElement.classList.remove('qv-is-open');
        if (scrollPosition) window.scrollTo({ left: scrollPosition.x, top: scrollPosition.y, behavior: 'instant' });
        if (opener?.isConnected) opener.focus({ preventScroll: true });
        dialog.removeAttribute('aria-busy');
    });

    image.addEventListener('load', () => { image.hidden = false; placeholder.hidden = true; });
    image.addEventListener('error', () => { image.hidden = true; placeholder.hidden = false; });
    quantity.addEventListener('input', updateQuantityButtons);
    quantity.addEventListener('change', () => {
        quantity.value = String(Math.max(1, Math.min(Math.floor(Number(quantity.value)) || 1, selected?.max_quantity || 1)));
        updateQuantityButtons();
    });
    find('minus').addEventListener('click', () => {
        quantity.value = String(Math.max(1, Number(quantity.value) - 1));
        updateQuantityButtons();
    });
    find('plus').addEventListener('click', () => {
        quantity.value = String(Math.min(selected?.max_quantity || 1, Number(quantity.value) + 1));
        updateQuantityButtons();
    });
    form.addEventListener('submit', event => {
        if (!current?.authenticated || !selected?.max_quantity || !form.checkValidity() || submit.disabled) {
            event.preventDefault();
            form.reportValidity();
            return;
        }
        // Use the existing server-authoritative POST. Never infer a successful cart mutation from a redirect.
        submit.disabled = true;
        submit.textContent = 'Đang thêm vào giỏ…';
    });
    window.addEventListener('pageshow', event => {
        if (event.persisted && current && selected) {
            submit.textContent = 'Thêm vào giỏ hàng ↗';
            if (dialog.open) load();
        }
    });
}
