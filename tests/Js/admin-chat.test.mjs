import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/admin-chat.js', import.meta.url), 'utf8');

// A small DOM boundary double: HTML parsing is forbidden, and request completion
// remains under each test's control to reproduce real out-of-order responses.
class Element {
    constructor(tag = 'div') {
        this.tagName = tag;
        this.children = [];
        this.dataset = {};
        this.style = {};
        this.attributes = {};
        this.listeners = new Map();
        this.value = '';
        this.className = '';
        this.scrollTop = 0;
        this.scrollHeight = 1000;
        this.clientHeight = 200;
        this.classList = { toggle: (name, active) => {
            const classes = new Set(this.className.split(' ').filter(Boolean));
            if (active) classes.add(name); else classes.delete(name);
            this.className = [...classes].join(' ');
        } };
    }
    set innerHTML(_) { throw new Error('Chat must never parse customer data as HTML'); }
    set textContent(value) { this.text = String(value); this.children = []; }
    get textContent() { return (this.text || '') + this.children.map(child => child.textContent).join(''); }
    get childNodes() { return this.children; }
    append(...nodes) { for (const node of nodes) this.children.push(...(node.tagName === '#fragment' ? node.children : [node])); }
    replaceChildren(...nodes) { this.text = ''; this.children = []; this.append(...nodes); }
    setAttribute(key, value) { this.attributes[key] = value; }
    addEventListener(type, listener) { this.listeners.set(type, listener); }
    dispatch(type, event = {}) { return this.listeners.get(type)?.(event); }
    querySelectorAll() { return this.children.filter(child => child.tagName === 'button' && child.dataset.userId); }
    focus() {}
}

function chat() {
    const ids = ['admin-chat-box', 'chat-toggle', 'chat-popup', 'chat-close', 'user-list', 'chat-messages', 'chat-input', 'send-btn', 'chat-search-input', 'btn-clear-search', 'chat-tab-recent', 'chat-tab-all', 'chat-active-header', 'active-user-name', 'admin-chat-error'];
    const nodes = Object.fromEntries(ids.map(id => [id, new Element()]));
    nodes['admin-chat-box'].dataset = { adminId: '99', usersUrl: '/admin/chat/users', messagesUrl: '/admin/chat/messages/__USER__', sendUrl: '/admin/chat/send' };
    nodes['admin-chat-box'].querySelector = selector => nodes[selector.slice(1)];
    const requests = [];
    const polls = [];
    const window = { location: { href: 'https://store.example/admin/dashboard' }, addEventListener() {} };
    vm.runInNewContext(source, {
        window,
        document: { hidden: false, getElementById: id => nodes[id], querySelector: selector => selector === 'meta[name="csrf-token"]' ? { content: 'test-token' } : null, createElement: tag => new Element(tag), createDocumentFragment: () => new Element('#fragment') },
        fetch: (url, options) => new Promise(resolve => requests.push({ url: String(url), options, resolve })),
        URL, AbortController, setTimeout: () => 1, clearTimeout() {}, setInterval: callback => { polls.push(callback); return 1; }, clearInterval() {},
    });
    const finish = async (request, data, status = 200) => {
        request.resolve({ ok: status >= 200 && status < 300, status, json: async () => data });
        for (let index = 0; index < 8; index++) await Promise.resolve();
    };
    const type = text => { nodes['chat-input'].value = text; nodes['chat-input'].dispatch('input'); };
    return { window, nodes, requests, polls, finish, type };
}

test('customer names and message bodies remain literal text', async () => {
    const app = chat();
    const name = '<img src=x onerror=alert(1)>';
    app.window.openChatWithUser(1, name);
    await app.finish(app.requests[0], [{ id: 1, name, email: 'customer@example.test' }]);
    await app.finish(app.requests[1], [{ id: 1, sender_id: 1, sender: { name, role: 'user' }, content: '<script>attack()</script>', created_at: '2026-10-01 12:00:00' }]);
    assert.equal(app.nodes['user-list'].children[0].tagName, 'button');
    assert.equal(app.nodes['user-list'].children[0].textContent, name);
    assert.equal(app.nodes['active-user-name'].textContent, name);
    assert.ok(app.nodes['chat-messages'].textContent.includes('<script>attack()</script>'));
});

test('an HTTP failure preserves the unsent draft and exposes an inline error', async () => {
    const app = chat();
    app.window.openChatWithUser(1, 'Lan');
    app.type('Please keep this draft');
    const sending = app.nodes['send-btn'].dispatch('click');
    const request = app.requests.find(item => item.options.method === 'POST');
    await app.finish(request, { message: 'Server error' }, 500);
    await sending;
    assert.equal(app.nodes['chat-input'].value, 'Please keep this draft');
    assert.equal(app.nodes['admin-chat-error'].hidden, false);
    assert.equal(app.nodes['send-btn'].disabled, false);
});

test('a delayed send completion never clears a different customer draft', async () => {
    const app = chat();
    app.window.openChatWithUser(1, 'Lan');
    app.type('Message for Lan');
    const sending = app.nodes['send-btn'].dispatch('click');
    const request = app.requests.find(item => item.options.method === 'POST');
    app.window.selectUser(2, 'Minh');
    app.type('Draft for Minh');
    await app.finish(request, { id: 20 });
    await sending;
    assert.equal(JSON.parse(request.options.body).user_id, 1);
    assert.equal(app.nodes['chat-input'].value, 'Draft for Minh');
    app.window.selectUser(1, 'Lan');
    assert.equal(app.nodes['chat-input'].value, '');
});

test('stale conversation responses cannot replace the selected customer history', async () => {
    const app = chat();
    app.window.openChatWithUser(1, 'Lan');
    const oldRequest = app.requests[1];
    app.window.selectUser(2, 'Minh');
    const currentRequest = app.requests.at(-1);
    await app.finish(currentRequest, [{ id: 2, content: 'Minh conversation' }]);
    await app.finish(oldRequest, [{ id: 1, content: 'Lan conversation' }]);
    assert.ok(app.nodes['chat-messages'].textContent.includes('Minh conversation'));
    assert.ok(!app.nodes['chat-messages'].textContent.includes('Lan conversation'));
    assert.equal(oldRequest.options.signal.aborted, true);
});

test('polling preserves the scroll position while reading older messages', async () => {
    const app = chat();
    app.window.openChatWithUser(1, 'Lan');
    await app.finish(app.requests[1], [{ id: 1, content: 'First message' }]);
    app.nodes['chat-messages'].scrollTop = 150;
    app.polls[0]();
    await app.finish(app.requests.at(-1), [{ id: 1, content: 'First message' }, { id: 2, content: 'New message' }]);
    assert.equal(app.nodes['chat-messages'].scrollTop, 150);
});

test('Enter is suppressed during IME composition and duplicate sends are blocked', async () => {
    const app = chat();
    app.window.openChatWithUser(1, 'Lan');
    app.type('Xin chào');
    let prevented = false;
    app.nodes['chat-input'].dispatch('keydown', { key: 'Enter', isComposing: true, preventDefault: () => { prevented = true; } });
    assert.equal(app.requests.filter(item => item.options.method === 'POST').length, 0);
    app.nodes['chat-input'].dispatch('keydown', { key: 'Enter', isComposing: false, preventDefault: () => { prevented = true; } });
    app.nodes['send-btn'].dispatch('click');
    assert.equal(prevented, true);
    assert.equal(app.requests.filter(item => item.options.method === 'POST').length, 1);
});
