(() => {
    if (window.soopiToast) return;
    const active = new Map();
    const queue = [];
    const types = ['success', 'error', 'warning', 'info'];
    const config = document.querySelector('[data-toast-config]');
    const closeLabel = config?.dataset.close || 'Close notification';
    const regionLabel = config?.dataset.label || 'Notifications';

    function host() {
        // A toast inside an open modal remains visible and keyboard-accessible.
        const parent = [...document.querySelectorAll('dialog[open]')].at(-1) || document.body;
        let region = [...parent.children].find(node => node.classList.contains('soopi-toasts'));
        if (!region) {
            region = document.createElement('section');
            region.className = 'soopi-toasts';
            region.setAttribute('aria-label', regionLabel);
            parent.append(region);
            if (parent.tagName === 'DIALOG') {
                parent.addEventListener('close', () => {
                    active.forEach(({toast}) => {if (parent.contains(toast)) host().append(toast);});
                });
            }
        }
        return region;
    }

    function show(message, type = 'info', options = {}) {
        message = typeof message === 'string' ? message.trim() : '';
        if (!message) return;
        type = types.includes(type) ? type : 'info';
        const key = `${type}:${message}`;
        if (active.has(key) || queue.some(item => item.key === key)) return;
        if (active.size >= 4) {
            if (queue.length < 20) queue.push({message, type, options, key});
            return;
        }
        const previousFocus = document.activeElement;
        const toast = document.createElement('div');
        toast.className = 'soopi-toast';
        toast.dataset.type = type;
        const icon = document.createElement('span');
        icon.className = 'soopi-toast__icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.textContent = {success: '✓', error: '!', warning: '!', info: 'i'}[type];
        const content = document.createElement('div');
        const text = document.createElement('p');
        text.className = 'soopi-toast__message';
        text.setAttribute('role', type === 'error' ? 'alert' : 'status');
        text.setAttribute('aria-atomic', 'true');
        content.append(text);
        if (options.action?.href && options.action?.label) {
            try {
                const url = new URL(options.action.href, location.href);
                if (url.origin === location.origin && /^https?:$/.test(url.protocol)) {
                    const link = document.createElement('a');
                    link.className = 'soopi-toast__action';
                    link.href = url.href;
                    link.textContent = options.action.label;
                    content.append(link);
                }
            } catch { /* Invalid links are not rendered. */ }
        }
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'soopi-toast__close';
        close.setAttribute('aria-label', closeLabel);
        close.textContent = '×';
        toast.append(icon, content, close);
        host().append(toast);
        let remaining = options.duration ?? (type === 'error' || options.action ? 0 : type === 'warning' ? 10000 : 6500);
        const automatic = remaining > 0;
        let timer;
        let started;
        let hovering = false;
        const pause = () => {
            if (!timer) return;
            clearTimeout(timer); timer = null;
            remaining = Math.max(0, remaining - (Date.now() - started));
        };
        const dismiss = () => {
            pause();
            const focused = toast.contains(document.activeElement);
            toast.remove(); active.delete(key);
            document.removeEventListener('visibilitychange', visibility);
            if (focused && previousFocus?.isConnected) previousFocus.focus({preventScroll: true});
            const next = queue.shift();
            if (next) show(next.message, next.type, next.options);
        };
        const resume = () => {
            if (timer || !automatic || hovering || document.hidden || toast.contains(document.activeElement)) return;
            started = Date.now(); timer = setTimeout(dismiss, remaining);
        };
        const visibility = () => document.hidden ? pause() : resume();
        active.set(key, {toast, dismiss});
        close.addEventListener('click', dismiss);
        toast.addEventListener('mouseenter', () => {hovering = true; pause();});
        toast.addEventListener('mouseleave', () => {hovering = false; resume();});
        toast.addEventListener('focusin', pause);
        toast.addEventListener('focusout', () => setTimeout(resume, 0));
        toast.addEventListener('keydown', event => {
            if (event.key === 'Escape') { event.stopPropagation(); dismiss(); }
        });
        document.addEventListener('visibilitychange', visibility);
        // Populate an attached live region so assistive technology announces it.
        text.textContent = message;
        resume();
        return dismiss;
    }
    window.soopiToast = show;
    document.addEventListener('soopi:toast', event => show(event.detail?.message, event.detail?.type, event.detail?.options));

    function initialize() {
        document.querySelectorAll('[data-toast-source]').forEach(source => {
            let previous = '';
            const update = () => {
                const message = source.textContent.trim();
                if (!message || source.hidden) { previous = ''; return; }
                if (message === previous) return;
                previous = message;
                show(message, source.dataset.toastSource);
                source.setAttribute('data-toast-consumed', '');
                source.removeAttribute('role');
                source.removeAttribute('aria-live');
            };
            update();
            new MutationObserver(update).observe(source, {childList: true, characterData: true, subtree: true, attributes: true, attributeFilter: ['hidden']});
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
})();
