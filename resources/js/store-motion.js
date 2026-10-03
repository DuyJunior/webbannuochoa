// Load WebGL only when decoration is visible and the customer's preferences allow it.
const headerScene = document.querySelector('[data-petal-scene]');
const stage = document.querySelector('[data-bloom-stage]');
const button = document.querySelector('[data-site-motion]');
if (headerScene || stage || button) {
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const connection = navigator.connection;
    const hosts = headerScene ? [headerScene] : [];
    if (stage) {
        const host = document.createElement('div');
        host.className = 'soopi-petal-stage';
        host.dataset.petalScene = 'hero';
        host.setAttribute('aria-hidden', 'true');
        stage.append(host); hosts.push(host);
    }
    let saved = null;
    try { saved = localStorage.getItem('soopi.bloom.motion'); } catch { /* Optional storage. */ }
    let enabled = !reduced.matches && !connection?.saveData && saved !== 'off';
    let modulePromise, disposed = false;
    const states = hosts.map(host => ({ host, visible: false, instance: null, pending: false }));
    const permitted = () => enabled && !document.hidden && !disposed;
    async function update(state) {
        const active = permitted() && state.visible;
        state.instance?.setActive(active);
        if (!active || state.instance || state.pending || state.failed) return;
        state.pending = true;
        try {
            modulePromise ||= import('./three-petals.js');
            const { createPetalScene } = await modulePromise;
            if (disposed || !permitted() || !state.visible) return;
            state.instance = createPetalScene(state.host);
            state.instance.setActive(true);
        } catch {
            // Photography and all shopping controls remain available without WebGL.
            state.failed = true;
            state.host.dataset.renderState = 'fallback';
        } finally { state.pending = false; }
    }
    function sync() {
        document.body.classList.toggle('bloom-motion-off', !enabled);
        document.body.classList.toggle('bloom-motion-on', enabled);
        button.setAttribute('aria-pressed', String(enabled));
        button.textContent = enabled ? (window.soopiT || (text => text))("Hiệu ứng: bật") : (window.soopiT || (text => text))("Hiệu ứng: tắt");
        button.disabled = reduced.matches || !!connection?.saveData;
        button.title = button.disabled ? (window.soopiT || (text => text))("Theo cài đặt giảm chuyển động hoặc tiết kiệm dữ liệu của thiết bị") : (window.soopiT || (text => text))("Bật hoặc tắt hiệu ứng chuyển động");
        states.forEach(update);
    }
    button.addEventListener('click', () => {
        enabled = !enabled && !reduced.matches && !connection?.saveData;
        try { localStorage.setItem('soopi.bloom.motion', enabled ? 'on' : 'off'); } catch { /* Optional storage. */ }
        window.dispatchEvent(new CustomEvent('soopi:motion-request', { detail: { enabled } }));
        window.dispatchEvent(new CustomEvent('soopi:motion', { detail: { enabled } }));
    });
    window.addEventListener('soopi:motion', event => {
        enabled = !!event.detail.enabled && !reduced.matches && !connection?.saveData; sync();
    });
    function preferencesChanged() {
        try { saved = localStorage.getItem('soopi.bloom.motion'); } catch { /* Optional storage. */ }
        enabled = !reduced.matches && !connection?.saveData && saved !== 'off';
        window.dispatchEvent(new CustomEvent('soopi:motion-request', { detail: { enabled } }));
        sync();
    }
    reduced.addEventListener('change', preferencesChanged);
    connection?.addEventListener('change', preferencesChanged);
    window.addEventListener('storage', event => { if (event.key === 'soopi.bloom.motion') preferencesChanged(); });
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
        const state = states.find(item => item.host === entry.target);
        state.visible = entry.isIntersecting; update(state);
    }), { threshold: .01 });
    states.forEach(state => observer.observe(state.host));
    document.addEventListener('visibilitychange', () => states.forEach(update));
    window.addEventListener('pagehide', event => {
        // A bfcache page keeps its scene, paused; normal navigation releases GPU resources.
        states.forEach(state => state.instance?.setActive(false));
        if (!event.persisted) {
            disposed = true; observer.disconnect();
            states.forEach(state => state.instance?.dispose());
        }
    });
    window.addEventListener('pageshow', () => states.forEach(update));
    sync();
}
