import '../css/delivery-location.css';

function setupDeliveryLocation() {
    const root = document.querySelector('[data-delivery-location]');
    if (!root) return;
    const messages = JSON.parse(root.querySelector('[data-location-messages]').textContent);
    const locate = root.querySelector('[data-locate]');
    const status = root.querySelector('[data-location-status]');
    const result = root.querySelector('[data-location-result]');
    const address = document.getElementById('address');
    let version = 0;
    let controller;
    let suggestion;
    let pending = false;

    function phase(value, detail) {
        root.dataset.phase = value;
        const labels = {
            idle: ['buttonIdle', 'stateIdle'], locating: ['buttonLocating', 'stateLocating'],
            searching: ['buttonSearching', 'stateSearching'], ready: ['buttonRetry', 'stateReady'],
            error: ['buttonRetry', 'stateError'], applied: ['buttonRetry', 'stateApplied'],
        };
        const [button, state] = labels[value];
        root.querySelector('[data-locate-label]').textContent = messages[button];
        root.querySelector('[data-location-state]').textContent = detail || messages[state];
        root.querySelectorAll('[data-location-step]').forEach(step => {
            if (step.dataset.locationStep === value) step.setAttribute('aria-current', 'step');
            else step.removeAttribute('aria-current');
        });
    }

    function cancel() {
        version += 1;
        controller?.abort();
        suggestion = null;
        pending = false;
        result.hidden = true;
        locate.disabled = false;
        root.removeAttribute('aria-busy');
        phase('idle');
    }

    locate.addEventListener('click', async () => {
        cancel();
        if (!window.isSecureContext || !navigator.geolocation) {
            phase('error');
            status.dataset.toastSource = 'error';
            status.textContent = messages.unsupported;
            return;
        }
        const requestVersion = version;
        const active = () => requestVersion === version;
        pending = true;
        locate.disabled = true;
        root.setAttribute('aria-busy', 'true');
        phase('locating');
        status.dataset.toastSource = 'info';
        status.textContent = messages.locating;
        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true, timeout: 12000, maximumAge: 0,
                });
            });
            if (!active()) return;
            phase('searching');
            status.dataset.toastSource = 'info';
            status.textContent = messages.searching;
            controller = new AbortController();
            const timeout = setTimeout(() => controller?.abort(), 65000);
            let response;
            try {
                response = await fetch(root.dataset.endpoint, {
                    method: 'POST', credentials: 'same-origin', signal: controller.signal,
                    headers: {
                        Accept: 'application/json', 'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': root.closest('form').querySelector('[name="_token"]').value,
                    },
                    body: JSON.stringify({ latitude: position.coords.latitude, longitude: position.coords.longitude }),
                });
            } finally {
                clearTimeout(timeout);
            }
            if (!active()) return;
            if (!response.ok) {
                const failure = await response.json().catch(() => ({}));
                if (!active()) return;
                phase('error', messages.stateLookupError);
                status.dataset.toastSource = 'error';
                const codes = { connection: 'connection', busy: 'busy', not_found: 'notFound', unavailable: 'serviceUnavailable' };
                status.textContent = [401, 419].includes(response.status) ? messages.expired
                    : response.status === 429 ? messages.limited : messages[codes[failure.code]] || messages.failed;
                return;
            }
            const data = await response.json();
            if (!active()) return;
            if (typeof data.label !== 'string' || !data.selection) throw new Error('Invalid suggestion');
            suggestion = data;
            root.querySelector('[data-location-address]').textContent = data.label;
            root.querySelector('[data-location-accuracy]').textContent = messages.accuracy
                .replace(':meters', String(Math.ceil(position.coords.accuracy)));
            root.querySelector('[data-location-match]').textContent = !data.street ? messages.areaOnly
                : data.matched === false ? messages.partial : messages.check;
            result.hidden = false;
            phase('ready');
            status.dataset.toastSource = 'info';
            status.textContent = messages.ready;
        } catch (error) {
            if (!active()) return;
            const lookupFailed = root.dataset.phase === 'searching';
            phase('error', lookupFailed ? messages.stateLookupError : undefined);
            status.dataset.toastSource = 'error';
            status.textContent = lookupFailed ? messages.connection
                : error.code === 1 ? messages.denied : error.code === 3 ? messages.timeout
                : error.code === 2 ? messages.unavailable : messages.failed;
        } finally {
            if (active()) {
                pending = false;
                locate.disabled = false;
                root.removeAttribute('aria-busy');
            }
        }
    });

    root.querySelector('[data-location-apply]').addEventListener('click', () => {
        if (!suggestion) return;
        const chosen = suggestion;
        cancel();
        // Clear unmatched fields as well: an old region must not be paired with a new street.
        address.value = chosen.street || '';
        document.dispatchEvent(new CustomEvent('soopi:delivery-location', { detail: chosen.selection }));
        phase('applied');
        status.dataset.toastSource = 'success';
        status.textContent = messages.applied;
        address.focus({ preventScroll: true });
    });
    root.querySelectorAll('[data-location-dismiss], [data-location-manual]').forEach(button => button.addEventListener('click', () => {
        cancel();
        status.dataset.toastSource = 'info';
        status.textContent = messages.manual;
        address.focus({ preventScroll: true });
        address.scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }));
    [address, ...document.querySelectorAll('#province_select, #district_select, #ward_select')].forEach(field => {
        field.addEventListener(field === address ? 'input' : 'change', () => {
            if (!pending && !suggestion) return;
            cancel();
            status.dataset.toastSource = 'info';
            status.textContent = messages.changed;
        });
    });
    window.addEventListener('pagehide', cancel);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', setupDeliveryLocation);
else setupDeliveryLocation();
