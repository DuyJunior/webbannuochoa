const cinema = document.querySelector('.cinema-hero');
if (cinema) {
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    let enabled = !reduced.matches && !navigator.connection?.saveData;
    try { enabled &&= localStorage.getItem('soopi.bloom.motion') !== 'off'; } catch { /* Optional storage. */ }
    let visible = true, pageActive = true, replayTimer;
    const sync = () => {
        const playing = enabled && visible && pageActive && !document.hidden;
        cinema.classList.toggle('cinema-playing', playing);
        cinema.dataset.cinemaState = playing ? 'playing' : 'paused';
    };
    window.addEventListener('soopi:motion', event => { enabled = event.detail.enabled; sync(); });
    document.addEventListener('visibilitychange', sync);
    window.addEventListener('pagehide', () => { pageActive = false; clearTimeout(replayTimer); sync(); });
    window.addEventListener('pageshow', () => { pageActive = true; sync(); });
    const observer = new IntersectionObserver(entries => { visible = entries[0].isIntersecting; sync(); });
    observer.observe(cinema);
    cinema.addEventListener('cinema:replay', () => {
        if (!enabled) return;
        clearTimeout(replayTimer);
        cinema.classList.remove('cinema-replay');
        // Restart the entrance wash only on an explicit replay request.
        void cinema.offsetWidth;
        cinema.classList.add('cinema-replay');
        replayTimer = setTimeout(() => cinema.classList.remove('cinema-replay'), 2400);
        cinema.querySelector('[data-bloom-stage]').dispatchEvent(new Event('cinema:gust'));
    });
    sync();
}
