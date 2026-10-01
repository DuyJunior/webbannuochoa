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
    const size = form.querySelector('[name=size]');
    const picks = [...form.querySelectorAll('[name="samples[]"]')];
    const tray = form.querySelector('[data-sample-tray]');
    const status = form.querySelector('[data-sample-status]');
    const selected = () => picks.filter(input => input.checked);
    function render(message = '') {
        const capacity = Number(size.value);
        const samples = selected();
        tray.replaceChildren(); tray.dataset.size = String(capacity);
        for (let i = 0; i < capacity; i++) {
            const slot = document.createElement('div'); slot.className = 'sample-slot';
            const sample = samples[i];
            if (sample) {
                const img = document.createElement('img'); img.src = sample.dataset.sampleImg; img.alt = '';
                const name = document.createElement('span'); name.textContent = sample.dataset.sampleName;
                const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = '×'; remove.setAttribute('aria-label', 'Bỏ ' + sample.dataset.sampleName);
                remove.addEventListener('click', () => { sample.checked = false; render(); sample.focus(); });
                slot.append(img, name, remove);
            } else {
                const vial = document.createElement('i'); vial.setAttribute('aria-hidden', 'true');
                const text = document.createElement('span'); text.textContent = 'Mẫu ' + (i + 1); slot.append(vial, text);
            }
            tray.append(slot);
        }
        form.querySelector('[data-sample-price]').textContent = (capacity === 5 ? 299000 : 199000).toLocaleString('vi-VN') + '₫';
        status.textContent = message || `${samples.length}/${capacity} mẫu đã chọn`;
    }
    picks.forEach(input => input.addEventListener('change', () => {
        if (selected().length > Number(size.value)) { input.checked = false; render('Khay đã đủ mẫu. Bỏ một mẫu hoặc chọn hộp 5.'); }
        else render();
    }));
    size.addEventListener('change', () => {
        const overflow = selected().slice(Number(size.value)); overflow.forEach(input => { input.checked = false; });
        render(overflow.length ? 'Đã giữ 3 mẫu đầu tiên cho hộp nhỏ.' : '');
    });
    render();
}
