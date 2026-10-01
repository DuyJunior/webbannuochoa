import '../css/atelier-typewriter.css';

const phrases = [...document.querySelectorAll('[data-typewriter]')].filter(element =>
    element.textContent.trim() && !element.children.length
    && !element.closest('a, button, label, input, select, textarea, [contenteditable="true"]'));

if (phrases.length && typeof window.IntersectionObserver === 'function' && typeof Intl.Segmenter === 'function') {
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const connection = navigator.connection;
    const prefersStatic = () => reduced.matches || connection?.saveData || document.body.classList.contains('bloom-motion-off');

    // Leave the complete server-rendered phrase untouched when animation is not appropriate.
    if (!prefersStatic()) {
        const segmenter = new Intl.Segmenter('vi', { granularity: 'grapheme' });
        let suspended = false;
        const states = phrases.map(element => {
            const text = element.textContent;
            const measure = document.createElement('span');
            measure.className = 'atelier-typewriter-measure';
            measure.textContent = text;
            const visual = document.createElement('span');
            visual.className = 'atelier-typewriter-visual';
            visual.setAttribute('aria-hidden', 'true');
            const characters = [...segmenter.segment(text)].map(({ segment }) => {
                const character = document.createElement('span');
                character.className = 'atelier-typewriter-character';
                character.textContent = segment;
                visual.append(character);
                return character;
            });
            const cursor = document.createElement('span');
            cursor.className = 'atelier-typewriter-cursor';
            cursor.setAttribute('aria-hidden', 'true');
            element.replaceChildren(measure, visual, cursor);
            element.classList.add('atelier-typewriter-ready');
            element.dataset.typewriterState = 'waiting';
            return { element, visual, characters, cursor, index: 0, visible: false, done: false, timer: null };
        });

        function positionCursor(state) {
            const character = state.characters[Math.max(0, state.index - 1)];
            if (!character || !state.visible) return;
            const bounds = character.getBoundingClientRect();
            const host = state.element.getBoundingClientRect();
            state.cursor.style.left = `${(state.index ? bounds.right : bounds.left) - host.left + 2}px`;
            state.cursor.style.top = `${bounds.top - host.top + bounds.height * .12}px`;
            state.cursor.style.height = `${bounds.height * .76}px`;
        }

        function finish(state) {
            if (state.done) return;
            clearTimeout(state.timer);
            state.timer = null;
            state.characters.forEach(character => character.classList.add('is-visible'));
            state.index = state.characters.length;
            state.done = true;
            state.element.dataset.typewriterState = 'complete';
            observer.unobserve(state.element);
        }

        function pause(state) {
            clearTimeout(state.timer);
            state.timer = null;
            if (!state.done) state.element.dataset.typewriterState = state.index > 0 ? 'paused' : 'waiting';
        }

        function advance(state) {
            state.timer = null;
            if (state.done) return;
            if (prefersStatic()) { finish(state); return; }
            if (document.hidden || suspended || !state.visible) { pause(state); return; }
            state.element.dataset.typewriterState = 'running';
            const character = state.characters[state.index++];
            character.classList.add('is-visible');
            positionCursor(state);
            if (state.index >= state.characters.length) {
                // The caret disappears at completion; there is no recurring blink or retyping loop.
                finish(state);
                return;
            }
            const delay = /[.,!?…]/u.test(character.textContent) ? 145 : /\s/u.test(character.textContent) ? 45 : 62 + state.index % 4 * 8;
            state.timer = setTimeout(() => advance(state), delay);
        }

        function sync() {
            states.forEach(state => {
                if (state.done) return;
                if (prefersStatic()) { finish(state); return; }
                if (document.hidden || suspended || !state.visible) { pause(state); return; }
                if (state.timer === null) {
                    state.element.dataset.typewriterState = 'running';
                    positionCursor(state);
                    state.timer = setTimeout(() => advance(state), state.index ? 70 : 160);
                }
            });
        }

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                const state = states.find(item => item.element === entry.target);
                if (state) state.visible = entry.isIntersecting && entry.intersectionRatio >= .3;
            });
            sync();
        }, { threshold: [0, .3] });
        states.forEach(state => observer.observe(state.element));

        const classObserver = new MutationObserver(sync);
        classObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        document.addEventListener('visibilitychange', sync);
        reduced.addEventListener('change', sync);
        connection?.addEventListener('change', sync);
        window.addEventListener('soopi:motion', event => {
            if (event.detail?.enabled === false) states.forEach(finish);
            else sync();
        });
        window.addEventListener('resize', () => states.filter(state => !state.done).forEach(positionCursor), { passive: true });
        window.addEventListener('pagehide', event => {
            suspended = true;
            states.forEach(pause);
            if (!event.persisted) {
                states.forEach(finish);
                observer.disconnect();
                classObserver.disconnect();
            }
        });
        window.addEventListener('pageshow', () => { suspended = false; sync(); });
    }
}
