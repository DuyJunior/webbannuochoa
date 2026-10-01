import './home-gallery.js';
import './gallery-filter.js';
import './home-moments.js';
import './home-cinema.js';
import './home-palette.js';

const hero = document.querySelector('[data-bloom]');

if (hero) {
    const stage = hero.querySelector('[data-bloom-stage]');
    const closed = hero.querySelector('[data-bloom-closed]');
    const open = hero.querySelector('.bloom-open');
    const motionButton = hero.querySelector('[data-bloom-motion]');
    const replayButton = hero.querySelector('[data-bloom-replay]');
    const hint = hero.querySelector('[data-bloom-hint]');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const fine = matchMedia('(hover: hover) and (pointer: fine)');
    const saveData = () => navigator.connection?.saveData === true;
    let preference = null;
    try { preference = localStorage.getItem('soopi.bloom.motion'); } catch { /* Storage may be unavailable. */ }
    let enabled = !reduced.matches && !saveData() && preference !== 'off';
    let visible = true;
    let frame = 0;
    let openingTimer = 0;
    let finishTimer = 0;
    let playback = 0;
    let pointerX = 0;
    let pointerY = 0;
    let rotationX = 0;
    let rotationY = 0;
    let dragStart = null;

    const reset = () => {
        cancelAnimationFrame(frame);
        frame = 0;
        pointerX = pointerY = rotationX = rotationY = 0;
        stage.classList.remove('is-interacting');
        stage.style.removeProperty('--bloom-rx');
        stage.style.removeProperty('--bloom-ry');
        stage.style.removeProperty('--bloom-light-x');
        stage.style.removeProperty('--bloom-light-y');
        stage.style.removeProperty('--bloom-scroll');
    };
    const stopOpening = () => {
        playback++;
        clearTimeout(openingTimer);
        clearTimeout(finishTimer);
        hero.classList.remove('is-bud', 'is-opening');
    };
    const syncMotion = () => {
        window.dispatchEvent(new CustomEvent('soopi:motion', { detail: { enabled } }));
        document.body.classList.toggle('bloom-motion-on', enabled);
        document.body.classList.toggle('bloom-motion-off', !enabled);
        motionButton.setAttribute('aria-pressed', String(enabled));
        motionButton.querySelector('[data-motion-label]').textContent = `Hiệu ứng: ${enabled ? 'bật' : 'tắt'}`;
        motionButton.disabled = reduced.matches || saveData();
        motionButton.title = motionButton.disabled ? 'Theo cài đặt giảm chuyển động hoặc tiết kiệm dữ liệu của thiết bị' : 'Bật hoặc tắt chuyển động trang chủ';
        replayButton.disabled = !enabled;
        hint.hidden = !enabled || !fine.matches;
        if (!enabled) { stopOpening(); reset(); }
    };
    const render = () => {
        frame = 0;
        if (!enabled || !visible || document.hidden) return;
        rotationX += (pointerY - rotationX) * .12;
        rotationY += (pointerX - rotationY) * .12;
        stage.style.setProperty('--bloom-rx', `${rotationX.toFixed(2)}deg`);
        stage.style.setProperty('--bloom-ry', `${rotationY.toFixed(2)}deg`);
        stage.style.setProperty('--bloom-light-x', `${(rotationY * 4).toFixed(1)}px`);
        stage.style.setProperty('--bloom-light-y', `${(-rotationX * 4).toFixed(1)}px`);
        if (Math.abs(pointerX - rotationY) + Math.abs(pointerY - rotationX) > .02) {
            frame = requestAnimationFrame(render);
        } else stage.classList.remove('is-interacting');
    };
    const schedule = () => {
        if (enabled && visible && !document.hidden && !frame) {
            stage.classList.add('is-interacting');
            frame = requestAnimationFrame(render);
        }
    };
    stage.addEventListener('pointermove', event => {
        if (!enabled || event.pointerType === 'touch' || !fine.matches) return;
        const bounds = stage.getBoundingClientRect();
        const x = (event.clientX - bounds.left) / bounds.width - .5;
        const y = (event.clientY - bounds.top) / bounds.height - .5;
        pointerX = Math.max(-10, Math.min(10, dragStart === null ? x * 12 : (event.clientX - dragStart) * .07));
        pointerY = Math.max(-5, Math.min(5, -y * 8));
        schedule();
    });
    stage.addEventListener('pointerdown', event => {
        if (!enabled || event.pointerType === 'touch' || event.button !== 0 || event.target.closest('a, button')) return;
        dragStart = event.clientX;
        stage.setPointerCapture(event.pointerId);
    });
    const release = () => { dragStart = null; pointerX = pointerY = 0; schedule(); };
    stage.addEventListener('pointerup', release);
    stage.addEventListener('pointercancel', release);
    stage.addEventListener('lostpointercapture', release);
    stage.addEventListener('pointerleave', () => { if (dragStart === null) release(); });

    // A matched pair of artwork states keeps the silk detail without a heavy WebGL scene.
    // The opening dissolves between them; pointer movement uses CSS perspective, not a 360° model.
    const bloom = async () => {
        if (!enabled || !visible || document.hidden) return;
        if (hero.classList.contains('cinema-hero')) {
            hero.dispatchEvent(new Event('cinema:replay'));
            return;
        }
        stopOpening();
        const run = playback;
        if (!closed.getAttribute('src')) closed.src = closed.dataset.src;
        try { await Promise.all([closed.decode(), open.decode()]); } catch { return; }
        if (run !== playback || !enabled || !visible || document.hidden) return;
        hero.classList.add('is-bud');
        openingTimer = setTimeout(() => {
            hero.classList.add('is-opening');
            hero.classList.remove('is-bud');
            finishTimer = setTimeout(() => hero.classList.remove('is-opening'), 1900);
        }, 900);
    };
    window.addEventListener('soopi:motion-request', event => {
        enabled = event.detail.enabled && !reduced.matches && !saveData();
        syncMotion();
    });
    motionButton.addEventListener('click', () => {
        enabled = !enabled && !reduced.matches && !saveData();
        try { localStorage.setItem('soopi.bloom.motion', enabled ? 'on' : 'off'); } catch { /* Optional preference. */ }
        syncMotion();
    });
    replayButton.addEventListener('click', bloom);
    stage.addEventListener('keydown', event => {
        if (event.target !== stage || !enabled) return;
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Escape'].includes(event.key)) return;
        event.preventDefault();
        if (event.key === 'Escape') { reset(); return; }
        stopOpening();
        if (event.key === 'ArrowLeft') pointerX = Math.max(-10, pointerX - 2);
        if (event.key === 'ArrowRight') pointerX = Math.min(10, pointerX + 2);
        if (event.key === 'ArrowUp') pointerY = Math.min(5, pointerY + 1);
        if (event.key === 'ArrowDown') pointerY = Math.max(-5, pointerY - 1);
        schedule();
    });
    reduced.addEventListener('change', () => {
        let saved = null;
        try { saved = localStorage.getItem('soopi.bloom.motion'); } catch { /* Optional preference. */ }
        enabled = !reduced.matches && !saveData() && saved !== 'off';
        syncMotion();
    });
    fine.addEventListener('change', () => { reset(); syncMotion(); });
    document.addEventListener('visibilitychange', () => { if (document.hidden) { reset(); stopOpening(); } });
    window.addEventListener('pagehide', () => { reset(); stopOpening(); });
    if ('IntersectionObserver' in window) {
        const heroObserver = new IntersectionObserver(entries => {
            visible = entries[0].isIntersecting;
            if (!visible) { reset(); stopOpening(); }
        });
        heroObserver.observe(hero);
    }

    // One short scroll update; no scroll hijacking or perpetual animation loop.
    let scrollFrame = 0;
    window.addEventListener('scroll', () => {
        if (!enabled || !visible || document.hidden || !fine.matches || scrollFrame) return;
        scrollFrame = requestAnimationFrame(() => {
            scrollFrame = 0;
            if (enabled && visible) stage.style.setProperty('--bloom-scroll', `${Math.min(32, Math.max(0, -hero.getBoundingClientRect().top) * .07)}px`);
        });
    }, { passive: true });

    hero.querySelector('[data-bloom-tools]').hidden = false;
    syncMotion();
    // Don't delay the first meaningful paint for the decorative closed state.
    if (enabled && hero.dataset.bloomIntro !== 'still') {
        const start = () => { if (enabled) bloom(); };
        if (document.readyState === 'complete') start();
        else window.addEventListener('load', start, { once: true });
    }

    if ('IntersectionObserver' in window) {
        const reveal = new IntersectionObserver(entries => entries.forEach(entry => {
            if (entry.isIntersecting) { entry.target.classList.remove('is-waiting'); reveal.unobserve(entry.target); }
        }), { rootMargin: '0px 0px 20px 0px', threshold: .04 });
        document.querySelectorAll('.ht-products-section .store-product-card, .bloom-live-editorial, .ht-shorts-showcase, .ht-collection-card').forEach(item => {
            item.classList.add('bloom-reveal');
            if (item.getBoundingClientRect().top > innerHeight) item.classList.add('is-waiting');
            reveal.observe(item);
        });
    }
}
