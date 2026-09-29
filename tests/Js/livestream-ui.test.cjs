const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const script = (name) => fs.readFileSync(path.join(__dirname, '../../public/js', name), 'utf8');
const element = () => ({
    dataset: {}, disabled: false, hidden: false, textContent: '', srcObject: null,
    listeners: {},
    classList: { add() {}, remove() {}, toggle() {} },
    addEventListener(name, callback) { this.listeners[name] = callback; },
});

test('studio begins only after media connects and stops after admin ends it', async () => {
    const ids = ['live-studio', 'live-studio-video', 'live-studio-placeholder', 'live-studio-status',
        'live-studio-pill', 'live-studio-start', 'live-studio-mic', 'live-studio-camera', 'live-studio-end'];
    const elements = Object.fromEntries(ids.map((id) => [id, element()]));
    const root = elements['live-studio'];
    Object.assign(root.dataset, {
        tokenUrl: '/token', beginUrl: '/begin', heartbeatUrl: '/heartbeat', finishUrl: '/finish',
        configured: '1', ended: '0',
    });
    const calls = [];
    const roomEvents = {};
    const intervals = new Map();
    let intervalId = 0;
    let disconnected = false;
    let heartbeatStatus = 200;
    class Room {
        localParticipant = {
            isMicrophoneEnabled: true, isCameraEnabled: true,
            enableCameraAndMicrophone: async () => calls.push('media'),
            getTrackPublication: () => ({ track: { attach: () => calls.push('preview') } }),
            setMicrophoneEnabled: async () => {}, setCameraEnabled: async () => {},
        };
        on(name, handler) { roomEvents[name] = handler; }
        async connect() { calls.push('connect'); }
        async disconnect() { disconnected = true; }
    }
    const context = {
        window: { LivekitClient: {
            Room, Track: { Source: { Camera: 'camera' } },
            RoomEvent: { Disconnected: 'disconnected', Reconnecting: 'reconnecting', Reconnected: 'reconnected' },
        } },
        document: {
            getElementById: (id) => elements[id],
            querySelector: () => ({ content: 'csrf' }),
        },
        navigator: { mediaDevices: { getUserMedia() {} } },
        fetch: async (url) => {
            calls.push(url);
            const status = url === '/heartbeat' ? heartbeatStatus : 200;
            return { ok: status === 200, status, json: async () => ({
                server_url: 'wss://test', participant_token: 'token', message: 'Buổi phát đã kết thúc',
            }) };
        },
        setInterval: (callback) => { intervals.set(++intervalId, callback); return intervalId; },
        clearInterval: (id) => intervals.delete(id),
        confirm: () => true,
    };
    vm.runInNewContext(script('livestream-studio.js'), context);
    await elements['live-studio-start'].listeners.click();
    assert.deepEqual(calls.slice(0, 5), ['/token', 'connect', 'media', 'preview', '/begin']);
    assert.equal(elements['live-studio-end'].disabled, false);
    assert.equal(intervals.size, 1);

    heartbeatStatus = 409;
    await [...intervals.values()][0]();
    assert.equal(disconnected, true);
    assert.equal(elements['live-studio-start'].disabled, false);
    assert.equal(elements['live-studio-end'].disabled, true);
    assert.equal(intervals.size, 0);
});

test('viewer attaches existing video without waiting for a new track event', async () => {
    const ids = ['live-viewer', 'live-viewer-join', 'live-viewer-message', 'live-viewer-video', 'live-viewer-audio'];
    const elements = Object.fromEntries(ids.map((id) => [id, element()]));
    elements['live-viewer'].dataset.tokenUrl = '/viewer-token';
    elements['live-viewer-video'].hidden = true;
    elements['live-viewer-audio'].play = async () => {};
    const track = { kind: 'video', attach: () => {}, detach: () => {} };
    const events = {};
    class Room {
        remoteParticipants = new Map([['presenter', {
            trackPublications: new Map([['camera', { track }]]),
        }]]);
        on(name, handler) { events[name] = handler; }
        async connect() {}
        async disconnect() {}
    }
    const context = {
        window: { LivekitClient: {
            Room, Track: { Kind: { Video: 'video', Audio: 'audio' } },
            RoomEvent: { TrackSubscribed: 'subscribed', TrackUnsubscribed: 'unsubscribed',
                Reconnecting: 'reconnecting', Reconnected: 'reconnected', Disconnected: 'disconnected' },
        }, addEventListener() {} },
        document: { getElementById: (id) => elements[id], querySelector: () => ({ content: 'csrf' }), dispatchEvent() {} },
        Event: class Event { constructor(type) { this.type = type; } },
        sessionStorage: { getItem: () => null },
        fetch: async () => ({ ok: true, json: async () => ({ server_url: 'wss://test', participant_token: 'token' }) }),
    };
    vm.runInNewContext(script('livestream-viewer.js'), context);
    await elements['live-viewer-join'].listeners.click();
    assert.equal(elements['live-viewer-video'].hidden, false);
    assert.equal(elements['live-viewer-join'].hidden, true);
    events.unsubscribed(track);
    assert.equal(elements['live-viewer-video'].hidden, true);
    assert.match(elements['live-viewer-message'].textContent, /kết nối lại camera/);
});
