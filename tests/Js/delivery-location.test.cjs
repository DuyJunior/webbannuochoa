const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const matcherSource = fs.readFileSync('resources/js/delivery-regions.js', 'utf8').replace('export function', 'function');
const source = matcherSource + '\n' + fs.readFileSync('resources/js/delivery-location.js', 'utf8').replace(/^import .*;\s*/gm, '');
const tick = () => new Promise(resolve => setImmediate(resolve));

function checkout(existing = '') {
    const messages = {
        accuracy: 'Accuracy :meters', filled: 'Filled: :fields.', remaining: 'Complete: :fields.',
        noneFilled: 'Nothing matched', houseAndStreet: 'house and street', checkStreetAndHouse: 'house number; check suggested street',
        province: 'province', district: 'district', ward: 'ward',
        stateApplying: 'Applying', statePartial: 'Partial', stateFilled: 'Filled',
        stateIdle: 'Idle', stateReady: 'Ready', stateError: 'Error',
        buttonIdle: 'Locate', buttonRetry: 'Retry', ready: 'Review first',
        mapPosition: 'Device location :meters m',
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
    const result = {label: 'Sample area', street: 'Sample street', regions: {province:['P'], district:['D'], ward:['W']}};
    vm.runInNewContext(source, {
        document, AbortController, URLSearchParams, setTimeout, clearTimeout,
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
    assert.match(page.node('[data-location-applied-summary]').textContent, /Sample street/);
    assert.match(page.node('[data-location-applied-summary]').textContent, /check suggested street/);
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

test('an already selected matching province does not block district and ward autofill', async () => {
    const page = checkout();
    page.fields[0].value = 'P';
    const work = page.locate(); await tick();
    assert.equal(page.root.dataset.phase, 'applying');
    assert.equal(page.application.regions.district[0], 'D');
    assert.equal(page.application.regions.ward[0], 'W');
    page.complete(); await work;
    assert.equal(page.root.dataset.phase, 'applied');
});

test('matching province and district allow filling the empty ward', async () => {
    const page = checkout();
    page.fields[0].value = 'P'; page.fields[1].value = 'D';
    const work = page.locate(); await tick();
    assert.equal(page.application.regions.ward[0], 'W');
    page.complete(); await work;
    assert.equal(page.fields[2].value, 'W');
});

test('conflicting or unmatched existing regions require confirmation', async () => {
    for (const selection of [
        {province:['OTHER'], district:['D'], ward:['W']},
        {province:['P'], district:[], ward:[]},
    ]) {
        const page = checkout();
        page.fields[0].value = 'P'; page.fields[1].value = 'D';
        page.result.regions = selection;
        await page.locate();
        assert.equal(page.application, undefined);
        assert.equal(page.fields[0].value, 'P');
        assert.equal(page.fields[1].value, 'D');
    }
});

test('partial area results report missing fields and focus the next available field', async () => {
    const page = checkout();
    page.result.street = ''; page.result.regions = {province:['P'],district:[],ward:[]};
    const work = page.locate(); await tick(); page.complete(['P','','']); await work;
    assert.equal(page.address.value, '');
    assert.match(page.node('[data-location-applied-summary]').textContent, /district, ward, house and street/);
    assert.equal(page.focused, page.fields[1]);
    assert.equal(page.node('[data-location-state]').textContent, 'Partial');
    assert.equal(page.node('[data-location-status]').dataset.toastSource, 'info');
});

const matchRegion = vm.runInNewContext(matcherSource + '\nmatchDeliveryRegion');
test('region matching handles Vietnamese accents and administrative prefixes', () => {
    assert.equal(matchRegion([{id:1, Name:'Phường Điện Biên'}], 'Name', ['dien bien']).id, 1);
    assert.equal(matchRegion([{id:2, Name:'Hà Nội'}], 'Name', ['Thành phố Hà Nội']).id, 2);
});
test('canonical names win over overlapping legacy aliases', () => {
    const rows = [{id:1, Name:'Hà Nội 02', NameExtension:['Hà Nội']}, {id:2, Name:'Hà Nội'}];
    assert.equal(matchRegion(rows, 'Name', ['Hà Nội']).id, 2);
});
test('duplicate canonical or alias names never choose an arbitrary shipping area', () => {
    assert.equal(matchRegion([{Name:'Quận Ba Đình'}, {Name:'Ba Đình'}], 'Name', ['Ba Đình']), null);
    assert.equal(matchRegion([
        {Name:'Hà Nội 02', NameExtension:['Hà Nội']},
        {Name:'Hà Nội 03', NameExtension:['Hà Nội']},
    ], 'Name', ['Hà Nội']), null);
});
test('a unique alias matches only within the supplied parent region', () => {
    const rows = [{id:1, Name:'Tên hiện tại', NameExtension:['Tên trước đây']}];
    assert.equal(matchRegion(rows, 'Name', ['Tên trước đây']).id, 1);
    assert.equal(matchRegion(rows, 'Name', ['Phường ở quận khác']), null);
    assert.equal(matchRegion(rows, 'Name', []), null);
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
