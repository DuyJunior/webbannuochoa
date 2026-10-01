import { createPetalMask, dyePetalPixels, PETAL_PALETTES } from './petal-palette.js';

const hero = document.querySelector('.cinema-hero');
const buttons = [...document.querySelectorAll('[data-bloom-mood]')];
if (hero && buttons.length) {
    const photo = hero.querySelector('.bloom-open');
    const sculpture = photo.parentElement;
    const status = document.querySelector('[data-palette-status]');
    const selectedName = document.querySelector('[data-palette-name]');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    let version = 0, source = '', mask, disposed = false;
    let requestedMood = hero.dataset.mood || 'rose';
    const surfaces = new Map();
    const names = { rose: 'hồng phấn', velvet: 'đỏ rượu', sage: 'xanh sage' };
    const clearSurfaces = () => {
        for (const canvas of surfaces.values()) canvas.remove();
        surfaces.clear(); mask = null;
    };
    const paint = async mood => {
        if (surfaces.has(mood) || mood === 'rose') return surfaces.get(mood);
        await photo.decode();
        // srcset density adjusts naturalWidth to CSS pixels. Snapshot the source
        // pixels so recoloured petals stay sharp when cover crops on a phone.
        const bitmap = typeof createImageBitmap === 'function' ? await createImageBitmap(photo) : photo;
        const canvas = document.createElement('canvas');
        canvas.width = bitmap === photo ? photo.naturalWidth : bitmap.width;
        canvas.height = bitmap === photo ? photo.naturalHeight : bitmap.height;
        canvas.className = 'bloom-palette-photo';
        canvas.setAttribute('aria-hidden', 'true');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        ctx.drawImage(bitmap, 0, 0);
        if (bitmap !== photo) bitmap.close();
        const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height);
        if (!mask || mask.length !== pixels.data.length) {
            const stencil = createPetalMask(canvas.width, canvas.height);
            mask = stencil.getContext('2d').getImageData(0, 0, canvas.width, canvas.height).data;
        }
        const localMask = mask;
        // Yield between strips so the first colour choice does not block touch/scroll.
        const stride = canvas.width * 4 * 48;
        for (let i = 0; i < pixels.data.length; i += stride) {
            const end = Math.min(i + stride, pixels.data.length);
            dyePetalPixels(pixels.data, localMask, mood, i, end);
            // Leave the glass and background to the original responsive <img>.
            // Redrawing them on canvas can subtly change browser colour sampling.
            for (let pixel = i; pixel < end; pixel += 4) {
                if (!localMask[pixel + 3]) pixels.data[pixel + 3] = 0;
            }
            await new Promise(resolve => setTimeout(resolve, 0));
        }
        ctx.putImageData(pixels, 0, 0);
        return canvas;
    };
    const choose = async (mood, announce = true) => {
        if (!PETAL_PALETTES[mood] || disposed) return;
        requestedMood = mood;
        const run = ++version;
        hero.dataset.paletteState = 'loading';
        if (announce) status.textContent = 'Đang đổi sắc cánh hoa…';
        try {
            const canvas = await paint(mood);
            if (run !== version || disposed) return;
            if (canvas && !surfaces.has(mood)) {
                sculpture.append(canvas); surfaces.set(mood, canvas);
                // Establish the transparent frame before fading the still fallback.
                void canvas.offsetWidth;
            }
            hero.dataset.mood = mood;
            for (const [tone, surface] of surfaces) surface.classList.toggle('is-selected', tone === mood);
            buttons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.bloomMood === mood)));
            selectedName.textContent = names[mood];
            hero.dataset.paletteState = 'ready';
            if (announce) status.textContent = `Đã đổi cánh hoa sang ${names[mood]}.`;
        } catch {
            if (run !== version) return;
            hero.dataset.paletteState = 'error';
            status.textContent = 'Chưa đổi được màu hoa. Bạn thử lại nhé.';
        }
    };
    buttons.forEach(button => button.addEventListener('click', () => choose(button.dataset.bloomMood)));
    // Keep the fallback matched to the current responsive source after a resize.
    photo.addEventListener('load', () => {
        if (source === photo.currentSrc) return;
        source = photo.currentSrc; version++; clearSurfaces();
        choose(requestedMood, false);
    });
    source = photo.currentSrc;
    document.querySelector('[data-palette-view]')?.addEventListener('click', () => {
        hero.querySelector('[data-bloom-stage]').scrollIntoView({ behavior: reduced.matches ? 'instant' : 'smooth', block: 'center' });
    });
    window.addEventListener('pagehide', event => {
        if (!event.persisted) { disposed = true; version++; clearSurfaces(); }
    });
}
