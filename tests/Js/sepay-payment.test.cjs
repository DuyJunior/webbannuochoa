const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const view = fs.readFileSync('resources/views/user/payment/sepay.blade.php', 'utf8');
const messages = {waiting: 'waiting', expired: 'expired', retry: 'retry', unavailable: 'unavailable'};
const source = view.match(/<script>([\s\S]*?)<\/script>/)[1].replace('@json($statusMessages)', JSON.stringify(messages));
function page(responses, expires = Date.now() + 600000) {
    const nodes = {'[data-payment-status]': {}, '[data-payment-countdown]': {}, '[data-payment-qr]': {hidden: false}};
    const scheduled = [];
    const requests = [];
    const redirects = [];
    const root = {dataset: {expiresAt: new Date(expires).toISOString(), statusUrl: '/user/orders/1/payment-status'}, querySelector: selector => nodes[selector]};
    const document = {hidden: false, querySelector: () => root};
    vm.runInNewContext(source, {
        document, Date, AbortSignal, setInterval: () => 1, clearInterval() {}, clearTimeout() {},
        setTimeout: callback => { scheduled.push(callback); return 2; },
        window: {addEventListener() {}, location: {assign: url => redirects.push(url)}},
        fetch: async (url, options) => { requests.push({url, options}); const result = responses.shift(); if (result instanceof Error) throw result; return result; },
    });
    return {nodes, scheduled, requests, redirects, document, async poll() { await scheduled.shift()(); }};
}
const response = (data, status = 200) => ({ok: status === 200, status, json: async () => data});

test('only a confirmed paid status redirects to the order', async () => {
    const p = page([response({status: 'pending', can_pay: true}), response({status: 'paid', order_url: '/orders/1'})]);
    await p.poll();
    assert.equal(p.redirects.length, 0);
    assert.equal(p.nodes['[data-payment-status]'].textContent, 'waiting');
    await p.poll();
    assert.deepEqual(p.redirects, ['/orders/1']);
    assert.equal(p.scheduled.length, 0);
    assert.equal(p.requests[0].options.cache, 'no-store');
});

test('network failure retries and expiry hides the QR without claiming payment', async () => {
    const p = page([new Error('offline'), response({status: 'pending', can_pay: false})]);
    await p.poll();
    assert.equal(p.nodes['[data-payment-status]'].textContent, 'retry');
    await p.poll();
    assert.equal(p.nodes['[data-payment-qr]'].hidden, true);
    assert.equal(p.nodes['[data-payment-status]'].textContent, 'expired');
    assert.equal(p.redirects.length, 0);
    assert.equal(p.scheduled.length, 0);
});

test('expired QR is hidden immediately and loss of authorization stops polling', async () => {
    const p = page([response({}, 401)], Date.now() - 1000);
    assert.equal(p.nodes['[data-payment-qr]'].hidden, true);
    assert.equal(p.nodes['[data-payment-countdown]'].textContent, '0:00');
    await p.poll();
    assert.equal(p.nodes['[data-payment-status]'].textContent, 'unavailable');
    assert.equal(p.scheduled.length, 0);
});
