document.querySelectorAll('[data-otp-resend]').forEach(form => {
    const button = form.querySelector('button');
    const countdown = form.querySelector('[data-otp-countdown]');
    const until = Date.now() + Number(form.dataset.retryAfter || 0) * 1000;
    const update = () => {
        const seconds = Math.max(0, Math.ceil((until - Date.now()) / 1000));
        button.disabled = seconds > 0;
        countdown.textContent = seconds ? ` (${seconds}s)` : '';
        return seconds;
    };
    if (update()) {
        const timer = setInterval(() => { if (!update()) clearInterval(timer); }, 1000);
    }
});
