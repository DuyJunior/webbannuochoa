import '../css/delivery-location.css';
import { matchDeliveryRegion } from './delivery-regions.js';

// Shared with the checkout's existing delivery dropdown cascade.
window.soopiMatchDeliveryRegion = matchDeliveryRegion;

function setupDeliveryLocation() {
    const root = document.querySelector('[data-delivery-location]');
    if (!root) return;
    const messages = JSON.parse(root.querySelector('[data-location-messages]').textContent);
    const locate = root.querySelector('[data-locate]');
    const status = root.querySelector('[data-location-status]');
    const result = root.querySelector('[data-location-result]');
    const address = document.getElementById('address');
    const fields = [...document.querySelectorAll('#province_select, #district_select, #ward_select')];
    const applyButton = root.querySelector('[data-location-apply]');
    const appliedSummary = document.querySelector('[data-location-applied-summary]');
    let mapReady;
    const getMap = () => mapReady ??= import('./delivery-map.js').then(module => module.createDeliveryMap(root, messages));
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            if (entries.some(entry => entry.isIntersecting)) {
                observer.disconnect();
                getMap().catch(() => {});
            }
        });
        observer.observe(root);
    }
    let version = 0;
    let controller;
    let suggestion;
    let pending = false;
    let applying = false;

    function phase(value, detail) {
        root.dataset.phase = value;
        const labels = {
            idle: ['buttonIdle', 'stateIdle'], locating: ['buttonLocating', 'stateLocating'],
            searching: ['buttonSearching', 'stateSearching'], ready: ['buttonRetry', 'stateReady'],
            error: ['buttonRetry', 'stateError'], applied: ['buttonRetry', 'stateApplied'],
            applying: ['buttonApplying', 'stateApplying'],
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
        if (applying) document.dispatchEvent(new CustomEvent('soopi:delivery-location-cancel'));
        applying = false;
        suggestion = null;
        pending = false;
        result.hidden = true;
        applyButton.hidden = false;
        if (appliedSummary) appliedSummary.hidden = true;
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
            // The map display does not block address lookup or shipping calculations.
            getMap().then(map => map.showPosition(position)).catch(() => {});
            phase('searching');
            status.dataset.toastSource = 'info';
            status.textContent = messages.searching;
            controller = new AbortController();
            // Only the map lookup runs here. Shipping dropdowns load separately.
            const timeout = setTimeout(() => controller?.abort(), 35000);
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
            if (typeof data.label !== 'string' || !data.regions
                || !['province', 'district', 'ward'].every(key => Array.isArray(data.regions[key]))) {
                throw new Error('Invalid suggestion');
            }
            suggestion = data;
            root.querySelector('[data-location-address]').textContent = data.label;
            root.querySelector('[data-location-accuracy]').textContent = messages.accuracy
                .replace(':meters', String(Math.ceil(position.coords.accuracy)));
            root.querySelector('[data-location-match]').textContent = !data.street ? messages.areaOnly : messages.check;
            result.hidden = false;
            phase('ready');
            status.dataset.toastSource = 'info';
            status.textContent = messages.ready;
            // Complete empty child regions when selected parents already match.
            // A conflicting or unmatched existing choice still needs confirmation.
            const regionKeys = ['province', 'district', 'ward'];
            const compatible = fields.every((field, index) => !field.value
                || matchDeliveryRegion([{ name: field.selectedOptions[0]?.textContent }], 'name', data.regions[regionKeys[index]]));
            if (!address.value.trim() && compatible && data.regions.province.length) {
                await applySuggestion();
            }
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

    async function applySuggestion() {
        if (!suggestion) return;
        const chosen = suggestion;
        cancel();
        const applyVersion = version;
        applying = true;
        pending = true;
        locate.disabled = true;
        root.setAttribute('aria-busy', 'true');
        phase('applying');
        status.dataset.toastSource = 'info';
        status.textContent = messages.stateApplying;
        // Clear unmatched fields as well: an old region must not be paired with a new street.
        address.value = chosen.street || '';
        await new Promise(resolve => {
            document.dispatchEvent(new CustomEvent('soopi:delivery-location', {
                detail: { regions: chosen.regions, complete: resolve },
            }));
        });
        if (applyVersion !== version) return;
        applying = false;
        pending = false;
        locate.disabled = false;
        root.removeAttribute('aria-busy');
        const missing = fields.filter(field => !field.value);
        const filled = fields.filter(field => field.value).map(field => field.selectedOptions[0].textContent);
        const hasRegion = filled.length > 0;
        const names = { province_select: messages.province, district_select: messages.district, ward_select: messages.ward };
        if (address.value) filled.push(address.value);
        const remaining = [...missing.map(field => names[field.id]), address.value ? messages.checkStreetAndHouse : messages.houseAndStreet];
        const feedback = (!hasRegion ? messages.noneFilled + ' ' : '')
            + (filled.length ? messages.filled.replace(':fields', filled.join(' · ')) : '')
            + ' ' + messages.remaining.replace(':fields', remaining.join(', '));
        if (appliedSummary) {
            appliedSummary.textContent = feedback;
            appliedSummary.hidden = false;
        }
        phase(hasRegion ? 'applied' : 'error', hasRegion
            ? (missing.length ? messages.statePartial : messages.stateFilled) : messages.noneFilled);
        status.dataset.toastSource = hasRegion && !missing.length ? 'success' : 'info';
        status.textContent = feedback;
        const target = missing.find(field => !field.disabled) || address;
        target.focus({ preventScroll: true });
        (appliedSummary || target).scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }
    applyButton.addEventListener('click', applySuggestion);
    root.querySelectorAll('[data-location-dismiss], [data-location-manual]').forEach(button => button.addEventListener('click', () => {
        cancel();
        status.dataset.toastSource = 'info';
        status.textContent = messages.manual;
        address.focus({ preventScroll: true });
        address.scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }));
    [address, ...fields].forEach(field => {
        field.addEventListener(field === address ? 'input' : 'change', () => {
            if (appliedSummary) appliedSummary.hidden = true;
            if (root.dataset.phase === 'applied') phase('idle');
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
