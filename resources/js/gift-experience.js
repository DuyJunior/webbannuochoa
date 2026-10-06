import '../css/gift-experience.css';

const share = document.querySelector('[data-gift-share]');
if (share) {
    const status = share.querySelector('[data-share-status]');
    const url = share.dataset.url;
    import('qrcode').then(async ({ default: QRCode }) => {
        const canvas = share.querySelector('[data-gift-qr]');
        await QRCode.toCanvas(canvas, url, { width: 260, margin: 4, errorCorrectionLevel: 'M', color: { dark: '#432b38', light: '#ffffff' } });
        const download = share.querySelector('[data-download-qr]');
        if (download) {
            download.href = await QRCode.toDataURL(url, { width: 1000, margin: 4, errorCorrectionLevel: 'M' });
            download.hidden = false;
        }
        const print = share.querySelector('[data-print-gift]');
        if (print) print.hidden = false;
    }).catch(() => { status.dataset.toastSource = 'error'; status.textContent = share.dataset.qrError; });
    share.querySelector('[data-copy-gift]')?.addEventListener('click', async () => {
        try { await navigator.clipboard.writeText(url); status.dataset.toastSource = 'success'; status.textContent = share.dataset.copied; }
        catch { share.querySelector('[data-gift-url]').select(); status.dataset.toastSource = 'error'; status.textContent = share.dataset.copyError; }
    });
    share.querySelector('[data-print-gift]')?.addEventListener('click', () => window.print());
}

const recorderPanel = document.querySelector('[data-gift-recorder]');
if (recorderPanel) {
    const start = recorderPanel.querySelector('[data-record-start]');
    const stop = recorderPanel.querySelector('[data-record-stop]');
    const status = recorderPanel.querySelector('[data-record-status]');
    const preview = recorderPanel.querySelector('[data-record-preview]');
    const fileInput = document.querySelector('[data-gift-audio]');
    const save = document.querySelector('[data-gift-save]');
    let recorder, stream, timer, previewUrl, stopped = false;
    const release = () => { clearTimeout(timer); stream?.getTracks().forEach(track => track.stop()); };
    const displayFile = file => {
        if (previewUrl) URL.revokeObjectURL(previewUrl);
        preview.hidden = !file;
        if (file) { previewUrl = URL.createObjectURL(file); preview.src = previewUrl; }
        else preview.removeAttribute('src');
    };
    const finish = () => { if (recorder?.state === 'recording') recorder.stop(); };
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) start.hidden = true;
    start.addEventListener('click', async () => {
        start.disabled = true;
        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            if (stopped) { release(); return; }
            const mimeType = ['audio/webm;codecs=opus', 'audio/mp4', 'audio/ogg;codecs=opus'].find(type => MediaRecorder.isTypeSupported(type));
            recorder = new MediaRecorder(stream, mimeType ? { mimeType } : undefined);
            const chunks = [];
            let bytes = 0;
            recorder.addEventListener('dataavailable', event => { if(event.data.size) { chunks.push(event.data); bytes += event.data.size; if(bytes > 10 * 1024 * 1024) finish(); } });
            recorder.addEventListener('stop', () => {
                release(); start.disabled = false; stop.hidden = true; save.disabled = false; fileInput.disabled = false;
                if (stopped) return;
                if (bytes > 10 * 1024 * 1024 || !bytes) { status.textContent = recorderPanel.dataset.large; window.soopiToast?.(status.textContent, 'error'); return; }
                const type = recorder.mimeType.split(';')[0];
                const extension = type.includes('mp4') ? 'm4a' : type.includes('ogg') ? 'ogg' : 'webm';
                const file = new File(chunks, `soopi-voice.${extension}`, { type });
                const transfer = new DataTransfer(); transfer.items.add(file); fileInput.files = transfer.files;
                displayFile(file); status.textContent = recorderPanel.dataset.ready; window.soopiToast?.(status.textContent, 'success');
            });
            recorder.addEventListener('error', () => { release(); start.disabled = false; stop.hidden = true; save.disabled = false; fileInput.disabled = false; status.textContent = recorderPanel.dataset.denied; window.soopiToast?.(status.textContent, 'error'); });
            recorder.start(1000); stop.hidden = false; save.disabled = true; fileInput.disabled = true;
            status.textContent = recorderPanel.dataset.recording;
            timer = setTimeout(finish, 120000);
        } catch { release(); start.disabled = false; status.textContent = recorderPanel.dataset.denied; window.soopiToast?.(status.textContent, 'error'); }
    });
    stop.addEventListener('click', finish);
    fileInput.addEventListener('change', () => displayFile(fileInput.files[0]));
    window.addEventListener('pagehide', () => { stopped = true; finish(); release(); if(previewUrl) URL.revokeObjectURL(previewUrl); });
    window.addEventListener('pageshow', () => { stopped = false; start.disabled = false; stop.hidden = true; save.disabled = false; fileInput.disabled = false; displayFile(fileInput.files[0]); });
}

document.querySelectorAll('[data-gift-form]').forEach(form => {
    const save = form.querySelector('[data-gift-save]');
    const original = save.innerHTML;
    form.addEventListener('submit', event => {
        if(form.dataset.saving === 'true' || save.disabled) { event.preventDefault(); return; }
        form.dataset.saving = 'true'; save.disabled = true; save.textContent = save.dataset.busy;
    });
    window.addEventListener('pageshow', () => { delete form.dataset.saving; save.disabled = false; save.innerHTML = original; });
});
