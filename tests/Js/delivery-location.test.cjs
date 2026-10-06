const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('resources/js/delivery-location.js', 'utf8').replace(/^import .*;\s*/, '');
const tick = () => new Promise(resolve => setImmediate(resolve));

function checkout(existing = '') {
    const messages = {
        accuracy: 'Accuracy :meters', filled: 'Filled: :fields.', remaining: 'Complete: :fields.',
        noneFilled: 'Nothing matched', houseAndStreet: 'house and street',
        province: 'province', district: 'district', ward: 'ward',
        stateApplying: 'Applying', statePartial: 'Partial', stateFilled: 'Filled',
        stateIdle: 'Idle', stateReady: 'Ready', stateError: 'Error',
        buttonIdle: 'Locate', buttonRetry: 'Retry', ready: 'Review first',
    };
    let focused, calls = 0, application, cancelled = 0;
    class Element {
        constructor(id = '') { this.id = id; this.value = ''; this.dataset = {}; this.listeners = {}; this.hidden = true; }
        addEventListener(name, fn) { this.listeners[name] = fn; }
        fire(name) { return this.listeners[name]?.(); }
        setAttribute() {}
        removeAttribute() {}
        focus() { focused = this; }
        scrollIntoView() { this.scrolled = true; }
        get selectedOptions() { return [{ textContent: this.value }]; }
    }
    const nodes = new Map();
    const node = selector => { if (!nodes.has(selector)) nodes.set(selector, new Element(selector)); return nodes.get(selector); };
    const address = node('#address'); address.value = existing;
    const fields = ['province_select', 'district_select', 'ward_select'].map(id => { const el = node('#'+id); el.id = id; return el; });
    const root = node('[data-delivery-location]');
    root.dataset.endpoint = '/locations/current-address';
    root.querySelector = node;
    root.querySelectorAll = selector => selector === '[data-location-step]' ? [] : [node('[data-location-dismiss]'), node('[data-location-manual]')];
    root.closest = () => ({querySelector: () => ({value: 'test-csrf'})});
    node('[data-location-messages]').textContent = JSON.stringify(messages);
    const document = {
        readyState: 'complete', querySelector: node, querySelectorAll: () => fields, getElementById: id => node('#'+id),
        dispatchEvent(event) {
            if (event.type === 'soopi:delivery-location') application = event.detail;
            if (event.type === 'soopi:delivery-location-cancel') cancelled++;
        },
    };
    const result = {label: 'Sample area', street: 'Sample street', selection: {province:'P', district:'D', ward:'W'}, matched:true};
    vm.runInNewContext(source, {
        document, AbortController, setTimeout, clearTimeout,
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options?.detail; } },
        window: {isSecureContext: true, addEventListener() {}, matchMedia: () => ({matches:true})},
        navigator: {geolocation: {getCurrentPosition(ok) { calls++; ok({coords:{latitude:21,longitude:105,accuracy:20}}); }}},
        fetch: async () => ({ok:true,json:async () => result}),
    });
    return {
        root, node, address, fields, result, locate: () => node('[data-locate]').fire('click'),
        apply: () => node('[data-location-apply]').fire('click'),
        get calls() { return calls; }, get application() { return application; }, get cancelled() { return cancelled; },
        get focused() { return focused; },
        complete(values = ['P','D','W']) { fields.forEach((el, i) => el.value = values[i]); application.complete(); },
    };
}

test('empty checkout autofills only after a location request and waits for the shipping cascade', async () => {
    const page = checkout();
    assert.equal(page.calls, 0);
    const work = page.locate(); await tick();
    assert.equal(page.root.dataset.phase, 'applying');
    assert.equal(page.node('[data-location-applied-summary]').hidden, true);
    assert.equal(page.address.value, 'Sample street');
    page.complete(); await work;
    assert.equal(page.root.dataset.phase, 'applied');
    assert.match(page.node('[data-location-applied-summary]').textContent, /P · D · W/);
    assert.equal(page.focused, page.address);
});

test('an existing address is preserved until the explicit confirmation', async () => {
    const page = checkout('12 Existing road');
    await page.locate();
    assert.equal(page.address.value, '12 Existing road');
    assert.equal(page.application, undefined);
    const work = page.apply(); await tick(); page.complete(); await work;
    assert.equal(page.address.value, 'Sample street');
});

test('partial area results report missing fields and focus the next available field', async () => {
    const page = checkout();
    page.result.street = ''; page.result.selection = {province:'P',district:'',ward:''};
    const work = page.locate(); await tick(); page.complete(['P','','']); await work;
    assert.equal(page.address.value, '');
    assert.match(page.node('[data-location-applied-summary]').textContent, /district, ward, house and street/);
    assert.equal(page.focused, page.fields[1]);
    assert.equal(page.node('[data-location-state]').textContent, 'Partial');
});

test('failed shipping lookup does not claim successful autofill', async () => {
    const page = checkout();
    const work = page.locate(); await tick(); page.complete(['','','']); await work;
    assert.equal(page.root.dataset.phase, 'error');
    assert.match(page.node('[data-location-applied-summary]').textContent, /^Nothing matched/);
    assert.equal(page.node('[data-location-status]').dataset.toastSource, 'info');
});

test('editing while applying cancels stale completion and preserves the new text', async () => {
    const page = checkout();
    const work = page.locate(); await tick();
    page.address.value = 'New user input'; page.address.fire('input');
    assert.equal(page.cancelled, 1);
    page.complete(); await work;
    assert.equal(page.address.value, 'New user input');
    assert.equal(page.root.dataset.phase, 'idle');
    assert.equal(page.node('[data-location-applied-summary]').hidden, true);
});
