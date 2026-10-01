import '../css/scent-experience.css';

const moods = document.querySelector('[data-mood-collection]');
const flower = document.querySelector('[data-bloom]');
if (moods && flower) {
    const panels = [...moods.querySelectorAll('[data-mood-panel]')];
    const sync = () => {
        const active = panels.find(panel => panel.dataset.moodPanel === flower.dataset.mood);
        if (!active) return;
        panels.forEach(panel => { panel.hidden = panel !== active; });
        moods.dataset.tone = active.dataset.moodPanel;
        moods.querySelector('[data-mood-count]').textContent = `${active.dataset.count} gợi ý · ${active.dataset.moodLabel}`;
    };
    // The flower commits its colour after its image is ready; rapid clicks must
    // not show suggestions for a different colour while it is still preparing.
    const observer = new MutationObserver(sync);
    observer.observe(flower, { attributes: true, attributeFilter: ['data-mood'] });
    sync();
    window.addEventListener('pagehide', event => { if (!event.persisted) observer.disconnect(); });
}

document.querySelectorAll('[data-fragrance-notes]').forEach(section => {
    const list = section.querySelector('[data-note-tabs]');
    if (!list) return;
    const tabs = [...list.querySelectorAll('[data-note-tab]')];
    const panels = [...section.querySelectorAll('[data-note-panel]')];
    list.setAttribute('role', 'tablist');
    const activate = (tab, focus = false) => {
        tabs.forEach(item => {
            item.setAttribute('aria-selected', String(item === tab));
            item.tabIndex = item === tab ? 0 : -1;
        });
        panels.forEach(panel => { panel.hidden = panel.dataset.notePanel !== tab.dataset.noteTab; });
        if (focus) tab.focus();
    };
    panels.forEach(panel => { panel.setAttribute('role', 'tabpanel'); panel.tabIndex = 0; });
    tabs.forEach((tab, index) => {
        tab.setAttribute('role', 'tab');
        tab.addEventListener('click', () => activate(tab));
        tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            else if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            else if (event.key === 'Home') next = 0;
            else if (event.key === 'End') next = tabs.length - 1;
            else return;
            event.preventDefault();
            activate(tabs[next], true);
        });
    });
    activate(tabs[0]);
});

// Policy links should reveal the actual conditions, not a closed accordion.
function revealPolicy() {
    if (location.hash !== '#doi-tra') return;
    const question = document.querySelector('#doi-tra .ht-faq-question[aria-expanded="false"]');
    question?.click();
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => setTimeout(revealPolicy, 0));
else setTimeout(revealPolicy, 0);
window.addEventListener('hashchange', revealPolicy);
