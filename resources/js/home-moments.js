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
    const capacity = () => Number(sizes.find(input => input.checked)?.value || 3);
    let selection = available.filter(input => input.checked).slice(0, capacity());
    let previouslyShown = new Set();
    function render(message = '') {
        if (message) window.soopiToast?.(message, 'info');
        const limit = capacity();
        const samples = selection;
        tray.replaceChildren(); tray.dataset.size = String(limit);
        form.dataset.sampleReady = String(samples.length === limit);
        for (let i = 0; i < limit; i++) {
            const slot = document.createElement('div'); slot.className = 'sample-slot';
            const sample = samples[i];
            const vial = document.createElement('span'); vial.className = 'sample-vial'; vial.setAttribute('aria-hidden', 'true');
            const productImage = sample?.dataset.sampleImg;
            const img = document.createElement('img'); img.alt = ''; img.src = productImage || form.dataset.vialSrc; img.width = 140; img.height = 360;
            const label = document.createElement('span'); label.className = 'sample-vial-label';
            const brand = document.createElement('b'); brand.textContent = 'SOOPI';
            const number = document.createElement('small'); number.textContent = String(i + 1).padStart(2, '0');
            const volume = document.createElement('span'); volume.textContent = '5 ML';
            label.append(brand, number, volume); vial.append(img);
            if (productImage) {
                vial.classList.add('has-product-image');
                const badge = document.createElement('span'); badge.className = 'sample-preview-volume'; badge.textContent = (window.soopiT || (text => text))("Mẫu 5 ml");
                vial.append(badge);
                img.addEventListener('error', () => {
                    vial.classList.remove('has-product-image');
                    badge.remove(); vial.append(label); img.src = form.dataset.vialSrc;
                }, { once: true });
            } else {
                vial.append(label);
            }
            const name = document.createElement('span'); name.className = 'sample-slot-name';
            name.textContent = sample?.dataset.sampleName || (window.soopiT || (text => text))("Mùi hương ") + String(i + 1).padStart(2, '0');
            name.title = name.textContent;
            slot.append(vial, name);
            if (sample) {
                slot.classList.add('is-filled');
                if (!previouslyShown.has(sample.value)) slot.classList.add('is-arriving');
                const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = (window.soopiT || (text => text))("×"); remove.setAttribute('aria-label', (window.soopiT || (text => text))("Bỏ ") + sample.dataset.sampleName);
                remove.addEventListener('click', () => {
                    selection = selection.filter(item => item !== sample);
                    render();
                    sample.focus({ preventScroll: true });
                });
                (productImage ? vial : slot).append(remove);
            }
            tray.append(slot);
        }
        previouslyShown = new Set(samples.map(input => input.value));
        picks.forEach(input => {
            const index = samples.indexOf(input);
            input.checked = index !== -1;
            const card = input.closest('[data-sample-card]');
            card.classList.toggle('is-selected', index !== -1);
            card.querySelector('[data-sample-action]').textContent = input.disabled ? input.dataset.sampleUnavailableReason : index !== -1 ? (window.soopiT || (text => text))("Đã chọn vào hộp") : (window.soopiT || (text => text))("Thêm vào hộp");
            card.querySelector('[data-sample-order]').textContent = index !== -1 ? String(index + 1).padStart(2, '0') : input.disabled ? '—' : '+';
            card.querySelector('.sample-choice-mark').textContent = index !== -1 ? '✓' : '+';
        });
        form.querySelector('[data-sample-price]').textContent = (limit === 5 ? 299000 : 199000).toLocaleString('vi-VN') + '₫';
        form.querySelector('[data-sample-count]').textContent = `${samples.length}/${limit}`;
        form.querySelector('[data-sample-remaining]').textContent = samples.length === limit ? (window.soopiT || (text => text))("Sẵn sàng để khám phá") : available.length ? `${(window.soopiT || (text => text))("Thêm")} ${limit - samples.length} ${(window.soopiT || (text => text))("mùi hương")}` : (window.soopiT || (text => text))("Đang chờ bổ sung");
        form.querySelector('[data-sample-progress]').style.width = `${samples.length / limit * 100}%`;
        status.textContent = message || (available.length ? `${samples.length}/${limit} ${(window.soopiT || (text => text))("mẫu đã chọn")}` : (window.soopiT || (text => text))("Chưa có mẫu để thêm vào hộp"));
        form.querySelector('.sample-complete > span').textContent = samples.length === limit ? `${(window.soopiT || (text => text))("Xem lại hộp")} ${limit} ${(window.soopiT || (text => text))("mẫu")}` : samples.length ? `${(window.soopiT || (text => text))("Tiếp tục với")} ${samples.length} ${(window.soopiT || (text => text))("mẫu đã chọn")}` : (window.soopiT || (text => text))("Xem toàn bộ mẫu hương");

    }
    picks.forEach(input => input.addEventListener('change', () => {
        if (input.disabled) return;
        if (input.checked && selection.length >= capacity()) {
            input.checked = false;
            render(capacity() === 3 ? (window.soopiT || (text => text))("Hộp đã đủ 3 mẫu. Bỏ một mẫu hoặc chọn hộp 5.") : (window.soopiT || (text => text))("Hộp đã đủ 5 mẫu. Bỏ một mẫu để thay bằng mùi hương khác."));
            return;
        }
        selection = input.checked ? [...selection, input] : selection.filter(item => item !== input);
        render();
    }));
    sizes.forEach(size => size.addEventListener('change', () => {
        const overflow = selection.length > capacity();
        selection = selection.slice(0, capacity());
        render(overflow ? (window.soopiT || (text => text))("Đã giữ 3 mẫu bạn chọn đầu tiên cho hộp nhỏ.") : '');
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
