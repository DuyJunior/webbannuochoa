import '../css/home-sampling.css';

const moments = document.querySelector('.soopi-moments');
if (moments) {
    const choices = [...moments.querySelectorAll('[data-moment-choice]')];
    const panels = [...moments.querySelectorAll('[data-moment-panel]')];
    function activate(key) {
        choices.forEach(link => link.setAttribute('aria-current', String(link.dataset.momentChoice === key)));
        panels.forEach(panel => { panel.hidden = panel.dataset.momentPanel !== key; });
    }
    choices.forEach(link => link.addEventListener('click', event => { event.preventDefault(); activate(link.dataset.momentChoice); }));
    const hashChoice = choices.find(link => link.hash === location.hash);
    activate(hashChoice?.dataset.momentChoice || choices[0].dataset.momentChoice);
}
const note = document.querySelector('[data-gift-note]');
const preview = document.querySelector('[data-gift-note-preview]');
if (note && preview) {
    const placeholder = preview.textContent;
    note.addEventListener('input', () => { preview.textContent = note.value.trim() || placeholder; });
}
const form = document.querySelector('[data-sample-form]');
if (form) {
    const sizes = [...form.querySelectorAll('[name=size]')];
    const picks = [...form.querySelectorAll('[name="samples[]"]')];
    const tray = form.querySelector('[data-sample-tray]');
    const status = form.querySelector('[data-sample-status]');
    const available = picks.filter(input => !input.disabled);
    const workbench = form.querySelector('.sample-workbench');
    let stickyFrame = 0;
    let disposed = false;
    function updateStickyFit() {
        stickyFrame = 0;
        if (!workbench || disposed) return;
        const bounds = workbench.getBoundingClientRect();
        const scale = workbench.offsetHeight ? bounds.height / workbench.offsetHeight : 1;
        const top = (parseFloat(getComputedStyle(workbench).top) || 108) * scale;
        const height = window.visualViewport?.height || window.innerHeight;
        workbench.dataset.stickyFit = String(window.innerWidth > 900 && bounds.height + top + 24 <= height);
    }
    function scheduleStickyFit() {
        if (!disposed && !stickyFrame) stickyFrame = requestAnimationFrame(updateStickyFit);
    }
    const workbenchObserver = workbench && typeof ResizeObserver === 'function' ? new ResizeObserver(scheduleStickyFit) : null;
    workbenchObserver?.observe(workbench);
    window.addEventListener('resize', scheduleStickyFit, { passive: true });
    window.visualViewport?.addEventListener('resize', scheduleStickyFit, { passive: true });
    document.fonts?.ready.then(scheduleStickyFit);
    window.addEventListener('pageshow', scheduleStickyFit);
    window.addEventListener('pagehide', event => {
        cancelAnimationFrame(stickyFrame); stickyFrame = 0;
        if (!event.persisted) { disposed = true; workbenchObserver?.disconnect(); }
    });
    const capacity = () => Number(sizes.find(input => input.checked)?.value || 3);
    let selection = available.filter(input => input.checked).slice(0, capacity());
    let previouslyShown = new Set();
    function render(message = '') {
        const limit = capacity();
        const samples = selection;
        tray.replaceChildren(); tray.dataset.size = String(limit);
        form.dataset.sampleReady = String(samples.length === limit);
        for (let i = 0; i < limit; i++) {
            const slot = document.createElement('div'); slot.className = 'sample-slot';
            const sample = samples[i];
            if (sample) {
                slot.classList.add('is-filled');
                if (!previouslyShown.has(sample.value)) slot.classList.add('is-arriving');
                const number = document.createElement('small'); number.className = 'sample-slot-number'; number.textContent = String(i + 1).padStart(2, '0');
                const img = document.createElement('img'); img.alt = ''; if (sample.dataset.sampleImg) img.src = sample.dataset.sampleImg;
                else img.hidden = true;
                const name = document.createElement('span'); name.className = 'sample-slot-name'; name.textContent = sample.dataset.sampleName; name.title = sample.dataset.sampleName;
                const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×'; remove.setAttribute('aria-label', 'Bỏ ' + sample.dataset.sampleName);
                remove.addEventListener('click', () => {
                    selection = selection.filter(item => item !== sample);
                    render();
                    sample.focus({ preventScroll: true });
                });
                slot.append(number, img, name, remove);
            } else {
                const vial = document.createElement('i'); vial.setAttribute('aria-hidden', 'true');
                const text = document.createElement('span'); text.textContent = 'Mẫu ' + String(i + 1).padStart(2, '0'); slot.append(vial, text);
            }
            tray.append(slot);
        }
        previouslyShown = new Set(samples.map(input => input.value));
        picks.forEach(input => {
            const index = samples.indexOf(input);
            input.checked = index !== -1;
            const card = input.closest('[data-sample-card]');
            card.classList.toggle('is-selected', index !== -1);
            card.querySelector('[data-sample-action]').textContent = input.disabled ? input.dataset.sampleUnavailableReason : index !== -1 ? 'Đã chọn vào hộp' : 'Thêm vào hộp';
            card.querySelector('[data-sample-order]').textContent = index !== -1 ? String(index + 1).padStart(2, '0') : input.disabled ? '—' : '+';
            card.querySelector('.sample-choice-mark').textContent = index !== -1 ? '✓' : '+';
        });
        form.querySelector('[data-sample-price]').textContent = (limit === 5 ? 299000 : 199000).toLocaleString('vi-VN') + '₫';
        form.querySelector('[data-sample-count]').textContent = `${samples.length}/${limit}`;
        form.querySelector('[data-sample-remaining]').textContent = samples.length === limit ? 'Sẵn sàng để khám phá' : available.length ? `Thêm ${limit - samples.length} mùi hương` : 'Đang chờ bổ sung';
        form.querySelector('[data-sample-progress]').style.width = `${samples.length / limit * 100}%`;
        status.textContent = message || (available.length ? `${samples.length}/${limit} mẫu đã chọn` : 'Chưa có mẫu để thêm vào hộp');
        form.querySelector('.sample-complete > span').textContent = samples.length === limit ? `Xem lại hộp ${limit} mẫu` : samples.length ? `Tiếp tục với ${samples.length} mẫu đã chọn` : 'Xem toàn bộ mẫu hương';
        scheduleStickyFit();
    }
    picks.forEach(input => input.addEventListener('change', () => {
        if (input.disabled) return;
        if (input.checked && selection.length >= capacity()) {
            input.checked = false;
            render(capacity() === 3 ? 'Hộp đã đủ 3 mẫu. Bỏ một mẫu hoặc chọn hộp 5.' : 'Hộp đã đủ 5 mẫu. Bỏ một mẫu để thay bằng mùi hương khác.');
            return;
        }
        selection = input.checked ? [...selection, input] : selection.filter(item => item !== input);
        render();
    }));
    sizes.forEach(size => size.addEventListener('change', () => {
        const overflow = selection.length > capacity();
        selection = selection.slice(0, capacity());
        render(overflow ? 'Đã giữ 3 mẫu bạn chọn đầu tiên cho hộp nhỏ.' : '');
    }));
    form.addEventListener('formdata', event => {
        event.formData.delete('samples[]');
        selection.forEach(input => event.formData.append('samples[]', input.value));
    });
    window.addEventListener('pageshow', event => {
        if (!event.persisted) return;
        selection = selection.filter(input => input.checked && !input.disabled);
        render();
    });
    render();
}
