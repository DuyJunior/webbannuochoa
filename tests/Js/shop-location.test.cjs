const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function boot() {
    const fields = {};
    function element(value = '') { return { value, textContent: '', listeners: {},
        addEventListener(event, callback) { this.listeners[event] = callback; } }; }
    for (const key of ['[name=latitude]', '[name=longitude]', '[name=name]', '[data-location-preview]', '[data-editor-status]', '[data-pin-map]', '[data-enable-pin]', '[data-preview-name]', '[data-apply-coordinates]', '[data-coordinate-pair]']) fields[key] = element();
    const lat = fields['[name=latitude]'], lng = fields['[name=longitude]'];
    lat.value = '21.0205'; lng.value = '105.76393';
    lat.checkValidity = () => Number.isFinite(Number(lat.value)) && Math.abs(Number(lat.value)) <= 85;
    lng.checkValidity = () => Number.isFinite(Number(lng.value)) && Math.abs(Number(lng.value)) <= 180;
    const editor = { querySelector: key => fields[key] };
    const source = fs.readFileSync('resources/js/admin-shop-location.js', 'utf8').replace(/^import .*;\r?\n/, '');
    vm.runInNewContext(source, { document: { querySelector: () => editor }, setTimeout, clearTimeout });
    return fields;
}

test('pasted coordinates update Google preview without submitting the form', () => {
    const f = boot();
    f['[data-coordinate-pair]'].value = ' -10.2, 105.81 ';
    f['[data-apply-coordinates]'].listeners.click();
    assert.equal(f['[name=latitude]'].value, '-10.2');
    assert.equal(f['[name=longitude]'].value, '105.81');
    assert.match(f['[data-location-preview]'].src, /q=-10.2,105.81/);
    assert.match(f['[data-editor-status]'].textContent, /Lưu vị trí/);
});

test('invalid coordinates leave the previous pin unchanged', () => {
    for (const bad of ['91,105', '21,181', 'NaN,105', 'https://maps.google.com', '', '21,105,<script>']) {
        const f = boot(); const previous = f['[data-location-preview]'].src;
        f['[data-coordinate-pair]'].value = bad;
        f['[data-apply-coordinates]'].listeners.click();
        assert.equal(f['[data-location-preview]'].src, previous);
        assert.equal(f['[name=latitude]'].value, '21.0205');
    }
});

test('name preview uses text, and manual coordinates refresh the map', () => {
    const f = boot();
    f['[name=name]'].value = '<script>bad()</script>';
    f['[name=name]'].listeners.input();
    assert.equal(f['[data-preview-name]'].textContent, '<script>bad()</script>');
    f['[name=latitude]'].value = '0'; f['[name=longitude]'].value = '0';
    f['[name=latitude]'].listeners.change();
    assert.match(f['[data-location-preview]'].src, /q=0,0/);
});
