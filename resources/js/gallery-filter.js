// Progressive enhancement: filter only the product gallery, keeping the rest of
// the page and its current interactions alive. Links remain usable without JS.
const gallery = document.getElementById('san-pham');
if (gallery) {
    let request = null;
    let serial = 0;
    const heading = gallery.querySelector('.scent-heading');
    const startsFiltered = !!heading?.querySelector('.scent-result');
    const status = document.createElement('p');
    status.className = 'scent-filter-status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    const attachStatus = () => gallery.querySelector('.scent-toolbar')?.after(status);
    attachStatus();
    history.replaceState({ ...history.state, scentGallery: true }, '', location.href);

    async function load(url, push = true, focus = null) {
        request?.abort();
        const controller = new AbortController();
        request = controller;
        const id = ++serial;
        gallery.setAttribute('aria-busy', 'true');
        status.textContent = 'Đang chọn những mùi hương phù hợp…';
        try {
            const response = await fetch(url, {
                signal: controller.signal, cache: 'no-store',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) {
                if (response.status === 422) {
                    const data = await response.json();
                    throw new Error(Object.values(data.errors || {}).flat()[0] || 'Bạn kiểm tra lại bộ lọc nhé.');
                }
                throw new Error('Chưa tải được sản phẩm. Bạn thử lại nhé.');
            }
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = html.getElementById('san-pham');
            if (!next || id !== serial) throw new Error('Chưa tải được sản phẩm. Bạn thử lại nhé.');
            if (startsFiltered && heading) {
                const nextHeading = next.querySelector('.scent-heading');
                if (nextHeading) heading.replaceChildren(...[...nextHeading.childNodes].map(node => document.importNode(node, true)));
            }
            const content = [...next.children].filter(node => !node.classList.contains('scent-heading'));
            gallery.replaceChildren(heading, ...content.map(node => document.importNode(node, true)));
            attachStatus();
            status.textContent = `Đã hiển thị ${gallery.querySelectorAll('.gallery-piece').length} mùi hương.`;
            if (push) history.pushState({ ...history.state, scentGallery: true }, '', url);
            document.dispatchEvent(new CustomEvent('scent-gallery:updated'));
            if (focus) {
                const target = focus === 'form'
                    ? gallery.querySelector('.ht-filter-form button[type=submit]')
                    : [...gallery.querySelectorAll('a')].find(link => link.href === focus);
                target?.focus({ preventScroll: true });
            }
        } catch (error) {
            if (error.name === 'AbortError' || id !== serial) return;
            status.textContent = error.message || 'Kết nối bị gián đoạn. Bạn thử lại nhé.';
        } finally {
            if (id === serial) { gallery.removeAttribute('aria-busy'); request = null; }
        }
    }

    gallery.addEventListener('click', event => {
        const link = event.target.closest('.scent-categories a, .scent-sort a, .scent-active-filters a, .scent-empty a');
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        const url = new URL(link.href);
        if (url.origin !== location.origin || url.pathname !== location.pathname) return;
        event.preventDefault();
        load(url.href, true, link.matches(':focus-visible') ? url.href : null);
    });
    gallery.addEventListener('submit', event => {
        if (!event.target.matches('.ht-filter-form')) return;
        event.preventDefault();
        const url = new URL(event.target.action);
        url.search = new URLSearchParams(new FormData(event.target)).toString();
        load(url.href, true, 'form');
    });
    window.addEventListener('popstate', () => load(location.href, false));
}
