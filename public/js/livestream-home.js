(() => {
    const banner = document.getElementById('home-live-banner');
    if (!banner) return;

    const title = banner.querySelector('strong');
    const refresh = async () => {
        try {
            const response = await fetch(banner.dataset.stateUrl, { cache: 'no-store' });
            if (!response.ok) return;
            const state = await response.json();
            if (state.on_air && String(state.livestream_id) !== banner.dataset.currentId) {
                title.textContent = 'Soopi đang livestream';
            }
            banner.dataset.currentId = state.on_air ? String(state.livestream_id) : '';
            banner.hidden = !state.on_air;
        } catch (_) {
            // Keep the last known state until the next check.
        }
    };

    setInterval(refresh, 10000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });
})();
