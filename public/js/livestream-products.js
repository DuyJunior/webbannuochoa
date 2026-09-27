(() => {
    const shelf = document.getElementById('live-product-shelf');
    if (!shelf) return;

    shelf.addEventListener('click', (event) => {
        const link = event.target.closest('[data-live-follow]');
        if (link) sessionStorage.setItem('ha-thu-follow-live', link.dataset.liveFollow);
    });

    const refresh = async () => {
        if (document.hidden) return;
        try {
            const response = await fetch(shelf.dataset.productsUrl, { cache: 'no-store' });
            if (!response.ok) return;
            const result = await response.json();
            if (shelf.innerHTML.trim() !== result.html.trim()) shelf.innerHTML = result.html;
        } catch (_) {
            // Keep the current shelf while the connection is unavailable.
        }
    };
    setInterval(refresh, 15000);
})();
