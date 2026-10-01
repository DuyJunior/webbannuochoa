// Keep shopping content clear on downward scroll; bring navigation back on ascent.
const header = document.querySelector('.soopi-store > .ht-header');
if (header) {
    let previousY = Math.max(0, window.scrollY);
    let distance = 0;
    let direction = 0;
    let frame = 0;
    let headerHeight = header.offsetHeight;
    const show = () => header.classList.remove('ht-header-away');
    const reset = () => {
        previousY = Math.max(0, window.scrollY);
        distance = 0;
        direction = 0;
        show();
    };
    function update() {
        frame = 0;
        const y = Math.max(0, Math.min(window.scrollY, document.documentElement.scrollHeight - window.innerHeight));
        const delta = y - previousY;
        previousY = y;
        const focused = document.activeElement;
        const interacting = focused && header.contains(focused)
            && focused.matches('input, textarea, select, :focus-visible');
        if (y <= headerHeight + 24 || interacting) {
            distance = 0;
            show();
            return;
        }
        if (!delta) return;
        const nextDirection = delta > 0 ? 1 : -1;
        if (nextDirection !== direction) distance = 0;
        direction = nextDirection;
        distance += Math.abs(delta);
        if (distance < 12) return;
        if (direction > 0) {
            header.querySelectorAll('details[open]').forEach(menu => { menu.open = false; });
            header.dispatchEvent(new Event('navigation:close'));
            header.classList.add('ht-header-away');
        } else show();
        distance = 0;
    }
    window.addEventListener('scroll', () => {
        if (!frame) frame = requestAnimationFrame(update);
    }, { passive: true });
    header.addEventListener('focusin', reset);
    window.addEventListener('pageshow', reset);
    window.addEventListener('resize', () => { headerHeight = header.offsetHeight; reset(); }, { passive: true });
    if ('ResizeObserver' in window) new ResizeObserver(() => { headerHeight = header.offsetHeight; }).observe(header);
}
