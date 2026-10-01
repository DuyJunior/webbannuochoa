// Subtle, event-driven depth. Touch devices and reduced-motion users get static cards.
const boundCards = new WeakSet();
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
const pointer = window.matchMedia('(hover: hover) and (pointer: fine)');
let frame = 0;
let active = null;
const enabled = () => pointer.matches && !reduced.matches && !document.body.classList.contains('bloom-motion-off');
function reset() {
    cancelAnimationFrame(frame);
    frame = 0;
    if (!active) return;
    active.classList.remove('is-lit');
    for (const key of ['rx', 'ry', 'x', 'y']) active.style.removeProperty(`--pearl-${key}`);
    active = null;
}
function bindCards() {
for (const card of document.querySelectorAll('.soopi-homepage .gallery-piece, .soopi-homepage .store-product-card, .soopi-homepage .ht-collection-card, .soopi-homepage .exp-banner-card')) {
    if (boundCards.has(card)) continue;
    boundCards.add(card);
    if (!card.classList.contains('gallery-piece')) card.classList.add('pearl-depth');
    card.addEventListener('pointermove', event => {
        if (!enabled() || event.pointerType === 'touch') return;
        if (active !== card) { reset(); active = card; }
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => {
            const box = card.getBoundingClientRect();
            const x = Math.max(0, Math.min(1, (event.clientX - box.left) / box.width));
            const y = Math.max(0, Math.min(1, (event.clientY - box.top) / box.height));
            card.style.setProperty('--pearl-rx', `${(0.5 - y) * 2}deg`);
            card.style.setProperty('--pearl-ry', `${(x - 0.5) * 2}deg`);
            card.style.setProperty('--pearl-x', `${x * 100}%`);
            card.style.setProperty('--pearl-y', `${y * 100}%`);
            card.classList.add('is-lit');
            frame = 0;
        });
    }, { passive: true });
    card.addEventListener('pointerleave', reset);
    card.addEventListener('pointercancel', reset);
}
}
bindCards();
reduced.addEventListener('change', reset);
pointer.addEventListener('change', reset);
window.addEventListener('blur', reset);
document.addEventListener('visibilitychange', reset);
document.querySelector('[data-bloom-motion]')?.addEventListener('click', reset);

let sortMenu = document.querySelector('.scent-sort');
document.addEventListener('scent-gallery:updated', () => {
    reset();
    bindCards();
    sortMenu = document.querySelector('.scent-sort');
});
document.addEventListener('click', event => {
    if (sortMenu?.open && !sortMenu.contains(event.target)) sortMenu.open = false;
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && sortMenu?.open) {
        sortMenu.open = false;
        sortMenu.querySelector('summary').focus();
    }
});
