(() => {
    const root = document.getElementById('ht-live-page');
    if (!root) return;

    let checking = false;
    const check = async () => {
        if (document.hidden || checking) return;
        checking = true;
        try {
            const response = await fetch(root.dataset.stateUrl, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) return;
            const state = await response.json();
            if (String(state.livestream_id ?? '') !== root.dataset.currentId
                || Boolean(state.on_air) !== (root.dataset.onAir === '1')) {
                window.location.reload();
            }
        } catch (_) {
            // A temporary network problem should not interrupt a playing video.
        } finally {
            checking = false;
        }
    };

    setTimeout(() => {
        check();
        setInterval(check, 25000);
    }, 15000 + Math.random() * 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) check();
    });
})();
