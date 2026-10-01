const header = document.querySelector('.soopi-header');
if (header) {
    const panels = [...header.querySelectorAll('.sn-panel')];
    const triggers = [...header.querySelectorAll('[data-nav-panel]')];
    const backdrop = document.querySelector('.sn-backdrop');
    let current = null, origin = null, focusTimer = 0;
    const setPanel = (panel, trigger = null) => {
        clearTimeout(focusTimer);
        current = panel;
        if (trigger) origin = trigger;
        panels.forEach(item => { item.inert = item !== panel; item.classList.toggle('is-open', item === panel); });
        triggers.forEach(item => item.setAttribute('aria-expanded', String(item.dataset.navPanel === panel?.id)));
        backdrop.hidden = !panel;
        if (panel) {
            header.classList.remove('ht-header-away');
            header.querySelectorAll('details[open]').forEach(item => { item.open = false; });
        }
        if (panel?.id === 'sn-search') focusTimer = setTimeout(() => {
            if (current === panel) panel.querySelector('input').focus({ preventScroll: true });
        }, 100);
    };
    triggers.forEach(trigger => trigger.addEventListener('click', () => {
        const panel = document.getElementById(trigger.dataset.navPanel);
        setPanel(panel === current ? null : panel, trigger);
    }));
    document.querySelectorAll('[data-nav-close]').forEach(button => button.addEventListener('click', () => {
        setPanel(null); origin?.focus({ preventScroll: true });
    }));
    header.addEventListener('navigation:close', () => setPanel(null));
    header.querySelector('.ht-account')?.addEventListener('toggle', event => {
        if (event.target.open) setPanel(null);
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && current) { setPanel(null); origin?.focus({ preventScroll: true }); }
    });
    document.addEventListener('focusin', event => {
        if (current && !header.contains(event.target)) setPanel(null);
    });
    header.addEventListener('click', event => {
        if (event.target.closest('.sn-panel a')) setPanel(null);
    });
    header.querySelectorAll('[data-search-query]').forEach(button => button.addEventListener('click', () => {
        const input = header.querySelector('#sn-search input');
        input.value = button.dataset.searchQuery; input.focus();
    }));
    const art = header.querySelector('.sn-art');
    let frame = 0;
    const reset = () => { cancelAnimationFrame(frame); frame = 0; art.style.removeProperty('--rx'); art.style.removeProperty('--ry'); };
    art.addEventListener('pointermove', event => {
        if (event.pointerType === 'touch' || document.body.classList.contains('bloom-motion-off')) return;
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => {
            const bounds = art.getBoundingClientRect();
            art.style.setProperty('--rx', `${(.5 - (event.clientY - bounds.top) / bounds.height) * 5}deg`);
            art.style.setProperty('--ry', `${((event.clientX - bounds.left) / bounds.width - .5) * 5}deg`);
        });
    }, { passive: true });
    art.addEventListener('pointerleave', reset);
    window.addEventListener('soopi:motion', reset);
    window.addEventListener('pagehide', () => { setPanel(null); reset(); });
}
