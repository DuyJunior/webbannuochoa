(() => {
    const banner = document.getElementById('home-live-banner');
    if (!banner) return;

    const title = banner.querySelector('strong');
    const journalStatus = document.querySelector('[data-journal-live-status]');
    const journalLabel = document.querySelector('[data-journal-live-label]');
    let refreshing = false;
    const refresh = async () => {
        if (document.hidden || refreshing) return;
        refreshing = true;
        try {
            const response = await fetch(banner.dataset.stateUrl, { cache: 'no-store' });
            if (!response.ok) return;
            const state = await response.json();
            if (state.on_air && String(state.livestream_id) !== banner.dataset.currentId) {
                title.textContent = (window.soopiT || (text => text))("Soopi đang livestream");
            }
            banner.dataset.currentId = state.on_air ? String(state.livestream_id) : '';
            banner.hidden = !state.on_air;
            if (journalStatus) journalStatus.hidden = !state.on_air;
            if (journalLabel) journalLabel.textContent = state.on_air ? (window.soopiT || (text => text))("Vào xem trực tiếp") : (window.soopiT || (text => text))("Xem lịch live");
        } catch (_) {
            // Keep the last known state until the next check.
        } finally {
            refreshing = false;
        }
    };

    setInterval(refresh, 10000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refresh();
    });
})();
