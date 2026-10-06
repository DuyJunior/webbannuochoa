const otpForms = [...document.querySelectorAll('[data-otp-submit]')];
otpForms.forEach(form => {
    const button = form.querySelector('button[type="submit"]');
    const label = button.querySelector('[data-otp-button-label]');
    const original = label.textContent;
    form.addEventListener('submit', event => {
        if (form.getAttribute('aria-busy') === 'true') { event.preventDefault(); return; }
        if (!form.checkValidity()) return;
        form.setAttribute('aria-busy', 'true');
        button.disabled = true;
        label.textContent = form.dataset.busyLabel;
    });
    window.addEventListener('pageshow', () => {
        form.removeAttribute('aria-busy');
        button.disabled = false;
        label.textContent = original;
    });
});

document.querySelectorAll('[data-otp-resend]').forEach(form => {
    const button = form.querySelector('button');
    const countdown = form.querySelector('[data-otp-countdown]');
    const until = Date.now() + Number(form.dataset.retryAfter || 0) * 1000;
    const update = () => {
        const seconds = Math.max(0, Math.ceil((until - Date.now()) / 1000));
        button.disabled = seconds > 0 || form.getAttribute('aria-busy') === 'true';
        countdown.textContent = seconds ? ` (${seconds}s)` : '';
        return seconds;
    };
    window.addEventListener('pageshow', update);
    if (update()) {
        const timer = setInterval(() => { if (!update()) clearInterval(timer); }, 1000);
    }
});

const otpInput = document.getElementById('verification-code');
if (otpInput) {
    otpInput.addEventListener('input', () => { otpInput.value = otpInput.value.replace(/[^0-9]/g, '').slice(0, 6); });
    otpInput.addEventListener('paste', event => {
        const digits = event.clipboardData?.getData('text').replace(/[^0-9]/g, '');
        if (digits?.length === 6) {
            event.preventDefault();
            otpInput.value = digits;
            otpInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
}
