import '../css/story-journal.css';

document.querySelectorAll('[data-story-explorer]').forEach((explorer) => {
    const tabs = [...explorer.querySelectorAll('[data-story-tab]')];
    const panels = [...explorer.querySelectorAll('[data-story-panel]')];
    if (!tabs.length || tabs.length !== panels.length) return;
    const tablist = explorer.querySelector('[data-story-tabs]');
    tablist.setAttribute('role', 'tablist');
    tablist.setAttribute('aria-orientation', 'vertical');
    const select = (index, focus = false) => {
        tabs.forEach((tab, i) => {
            tab.setAttribute('aria-selected', String(i === index));
            tab.tabIndex = i === index ? 0 : -1;
            panels[i].hidden = i !== index;
        });
        if (focus) tabs[index].focus({ preventScroll: true });
    };
    tabs.forEach((tab, index) => {
        tab.setAttribute('role', 'tab');
        tab.setAttribute('aria-controls', panels[index].id);
        panels[index].setAttribute('role', 'tabpanel');
        panels[index].tabIndex = 0;
        tab.addEventListener('click', (event) => { event.preventDefault(); select(index); });
        tab.addEventListener('keydown', (event) => {
            let next;
            if (event.key === 'ArrowDown' || event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (event.key === ' ') next = index;
            if (next !== undefined) { event.preventDefault(); select(next, true); }
        });
    });
    const initial = panels.findIndex(panel => `#${panel.id}` === window.location.hash);
    select(initial < 0 ? 0 : initial);
});

document.querySelectorAll('[data-story-reader]').forEach((reader) => {
    const prose = reader.querySelector('[data-story-prose]');
    const tools = reader.querySelector('[data-reading-tools]');
    if (!prose || !tools) return;
    const progress = tools.querySelector('progress');
    const label = tools.querySelector('[data-reading-percent]');
    const buttons = [...tools.querySelectorAll('[data-reading-size]')];
    let fontSize = 18, frame = 0;
    const update = () => {
        frame = 0;
        const rect = prose.getBoundingClientRect();
        const percentage = Math.round(Math.min(100, Math.max(0, (window.innerHeight * .7 - rect.top) / Math.max(1, rect.height) * 100)));
        progress.value = percentage;
        progress.textContent = `${percentage}%`;
        label.textContent = `${percentage}%`;
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(update); };
    buttons.forEach(button => button.addEventListener('click', () => {
        fontSize = Math.min(24, Math.max(16, fontSize + Number(button.dataset.readingSize) * 2));
        prose.style.setProperty('--sj-reading-size', `${fontSize}px`);
        buttons.forEach(control => { control.disabled = Number(control.dataset.readingSize) < 0 ? fontSize === 16 : fontSize === 24; });
        schedule();
    }));
    tools.hidden = false;
    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule, { passive: true });
    if ('ResizeObserver' in window) new ResizeObserver(schedule).observe(prose);
    schedule();
});
