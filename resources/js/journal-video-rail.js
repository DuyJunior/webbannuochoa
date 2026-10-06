document.querySelectorAll('[data-video-rail]').forEach(rail => {
    const library = rail.closest('.journal-video-library');
    const navigation = library.querySelector('[data-video-navigation]');
    const previous = library.querySelector('[data-video-previous]');
    const next = library.querySelector('[data-video-next]');
    const range = library.querySelector('[data-video-range]');
    const cards = [...rail.querySelectorAll('.journal-video')];
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let frame;

    function update() {
        const bounds = rail.getBoundingClientRect();
        const visible = cards.map((card, index) => ({ bounds: card.getBoundingClientRect(), index }))
            .filter(item => Math.min(item.bounds.right, bounds.right) - Math.max(item.bounds.left, bounds.left) > item.bounds.width / 2);
        const canScroll = rail.scrollWidth > rail.clientWidth + 2;
        navigation.hidden = !canScroll;
        previous.disabled = rail.scrollLeft <= 2;
        next.disabled = rail.scrollLeft >= rail.scrollWidth - rail.clientWidth - 2;
        range.textContent = visible.length ? `${visible[0].index + 1}–${visible.at(-1).index + 1} / ${cards.length}` : '';
    }

    function scheduleUpdate() {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(update);
    }

    function move(direction) {
        const gap = parseFloat(getComputedStyle(rail).columnGap) || 0;
        const width = cards[0]?.getBoundingClientRect().width || rail.clientWidth;
        const perPage = Math.max(1, Math.floor((rail.clientWidth + gap) / (width + gap)));
        rail.scrollBy({ left: direction * perPage * (width + gap), behavior: reducedMotion.matches ? 'instant' : 'smooth' });
    }

    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    rail.addEventListener('scroll', scheduleUpdate, { passive: true });
    rail.addEventListener('keydown', event => {
        if (event.target !== rail || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') move(event.key === 'ArrowRight' ? 1 : -1);
        else rail.scrollTo({ left: event.key === 'Home' ? 0 : rail.scrollWidth, behavior: reducedMotion.matches ? 'instant' : 'smooth' });
    });
    if (typeof ResizeObserver === 'function') new ResizeObserver(scheduleUpdate).observe(rail);
    else window.addEventListener('resize', scheduleUpdate);
    document.fonts?.ready.then(scheduleUpdate);
    update();
});
