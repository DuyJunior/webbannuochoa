import test from 'node:test';
import assert from 'node:assert/strict';
import { createPetalWorld, advancePetals, movePetalPointer, releasePetalPointer, gustPetals, resizePetalWorld } from '../../resources/js/petal-physics.js';

function world(options = {}, count = 12) {
    let seed = 1234;
    return createPetalWorld(count, { random: () => ((seed = Math.imul(seed, 1664525) + 1013904223 >>> 0) / 4294967296), ...options });
}
test('flight is independent of 30 versus 60 fps rendering', () => {
    const a = world(), b = world();
    for (let i = 0; i < 600; i++) advancePetals(a, 1 / 60);
    for (let i = 0; i < 300; i++) advancePetals(b, 1 / 30);
    assert.deepEqual(a.petals, b.petals);
});
test('pointer force changes flight and leaving preserves inertia', () => {
    const a = world(), b = world(), p = a.petals[0];
    movePetalPointer(a, p.x - .2, p.y, .016);
    for (let i = 0; i < 12; i++) { advancePetals(a, 1 / 60); advancePetals(b, 1 / 60); }
    assert.ok(Math.abs(p.vx - b.petals[0].vx) > .05);
    const velocity = p.vx, x = p.x;
    releasePetalPointer(a); assert.equal(p.vx, velocity);
    advancePetals(a, 1 / 60); assert.notEqual(p.x, x);
    assert.ok(Math.abs(p.vx - velocity) < .1);
});
test('long-running simulation recycles safely and input bursts stay bounded', () => {
    const a = world(), p = a.petals[0];
    for (let i = 0; i < 500; i++) gustPetals(a, p.x - .1, p.y);
    assert.ok(Math.abs(p.vx) <= 3.5 && Math.abs(p.vy) <= 3.5 && Math.abs(p.wz) <= 3);
    for (let i = 0; i < 5400; i++) advancePetals(a, 1 / 30);
    for (const p of a.petals) {
        for (const value of Object.values(p)) assert.ok(Number.isFinite(value));
        assert.ok(p.opacity >= 0 && p.opacity <= 1);
        assert.ok(Math.abs(p.x) < a.halfWidth + 1 && Math.abs(p.y) < a.halfHeight + 1);
    }
});
test('tab suspension cannot produce a huge simulation jump', () => {
    const a = world(); advancePetals(a, 300);
    assert.ok(a.time <= .101);
    const previous = a.time; advancePetals(a, NaN); advancePetals(a, -1);
    assert.equal(a.time, previous);
});

test('falling starts sparsely staggered on the bloom side and resizes immediately with the camera', () => {
    const a = world({ falling: true }, 5);
    const original = a.petals.map(p => ({ x: p.x, y: p.y }));
    assert.equal(a.petals.length, 5);
    assert.ok(original[0].y - original.at(-1).y > 3.5);
    for (let i = 1; i < original.length; i++) assert.ok(original[i - 1].y > original[i].y);
    resizePetalWorld(a, 2.1, 3);
    for (const [index, p] of a.petals.entries()) {
        assert.equal(p.x, original[index].x * 2.1 / 4);
        assert.equal(p.y, original[index].y);
        assert.ok(p.x >= a.halfWidth * .2 && p.x <= a.halfWidth * .84);
        assert.ok(p.y > -a.halfHeight && p.y < a.halfHeight);
    }
    advancePetals(a, 1 / 60);
    assert.ok(a.petals.every(p => p.opacity > .8), 'staggered petals should already be visible');
});

test('falling petals descend slowly with terminal speed instead of shooting outward', () => {
    const a = world({ falling: true }, 5);
    const start = a.petals.map(p => ({ x: p.x, y: p.y }));
    for (let i = 0; i < 120; i++) advancePetals(a, 1 / 60);
    for (const [index, p] of a.petals.entries()) {
        assert.ok(p.fallSpeed >= .28 && p.fallSpeed <= .43);
        assert.ok(p.vy >= -.45 && p.vy <= -.26);
        assert.ok(start[index].y - p.y > .5 && start[index].y - p.y < .9);
        assert.ok(Math.abs(start[index].x - p.x) < .25);
        assert.ok(Math.abs(p.wx) < .15 && Math.abs(p.wy) < .15 && Math.abs(p.wz) < .15);
    }
});

test('falling pointer and repeated gusts retain a downward, gently tumbling trajectory', () => {
    const a = world({ falling: true }, 3);
    for (let frame = 0; frame < 300; frame++) {
        const p = a.petals[0];
        movePetalPointer(a, p.x + (frame % 2 ? -.12 : .12), p.y - .15, 1 / 120);
        for (let gust = 0; gust < 4; gust++) gustPetals(a, p.x - .1, p.y - .1);
        const previousAge = p.age, previousY = p.y;
        advancePetals(a, 1 / 60);
        assert.ok(p.vy <= -.22 && p.vy >= -.48);
        assert.ok(Math.abs(p.vx) <= .25 && Math.abs(p.wz) <= .22);
        if (p.age > previousAge) assert.ok(p.y < previousY);
    }
    const p = a.petals[0], velocity = p.vy;
    releasePetalPointer(a);
    assert.equal(p.vy, velocity);
});

test('falling recycles from the top right with a fade and never enters the text area', () => {
    const a = world({ falling: true }, 5);
    resizePetalWorld(a, 2.1, 3);
    let resets = 0;
    for (let frame = 0; frame < 7200; frame++) {
        const ages = a.petals.map(p => p.age);
        advancePetals(a, 1 / 60);
        assert.equal(a.petals.length, 5);
        for (const [index, p] of a.petals.entries()) {
            assert.ok(Object.values(p).every(Number.isFinite));
            assert.ok(p.x > 0 && p.x < a.halfWidth);
            assert.ok(p.y > -a.halfHeight - .55 && p.y <= a.halfHeight + .24);
            assert.ok(p.opacity >= 0 && p.opacity <= 1);
            if (p.age < ages[index]) {
                resets++;
                assert.equal(p.opacity, 0);
                assert.equal(p.y, a.halfHeight + .24);
                assert.ok(p.x >= a.halfWidth * .2 && p.x <= a.halfWidth * .84);
            }
        }
    }
    assert.ok(resets >= 20, 'long-running scene must recycle its fixed small pool');
});

test('falling trajectories and recycling remain identical at 30 and 60 fps', () => {
    const a = world({ falling: true }, 5), b = world({ falling: true }, 5);
    resizePetalWorld(a, 2.6, 3);
    resizePetalWorld(b, 2.6, 3);
    for (let i = 0; i < 3600; i++) advancePetals(a, 1 / 60);
    for (let i = 0; i < 1800; i++) advancePetals(b, 1 / 30);
    assert.deepEqual(a.petals, b.petals);
});

test('default hero and header retain their existing launch and interaction behavior', () => {
    for (const header of [false, true]) {
        const a = world({ header }), b = world({ header, falling: false });
        assert.ok(a.petals.every(p => p.vy > 0 && !('fallSpeed' in p)));
        const positions = a.petals.map(p => p.x);
        resizePetalWorld(a, 2.1, header ? 1.65 : 3);
        resizePetalWorld(b, 2.1, header ? 1.65 : 3);
        assert.deepEqual(a.petals.map(p => p.x), positions, 'default particles are not repositioned by the optional falling resize');
        for (const scene of [a, b]) {
            gustPetals(scene, 0, 0);
            movePetalPointer(scene, .4, .6, .016);
            for (let i = 0; i < 60; i++) advancePetals(scene, 1 / 60);
        }
        assert.deepEqual(a.petals, b.petals);
    }
});

test('falling resize ignores invalid camera dimensions and tab suspension stays bounded', () => {
    const a = world({ falling: true }, 3);
    const positions = a.petals.map(p => [p.x, p.y]);
    for (const dimensions of [[0, 3], [NaN, 3], [2, Infinity], [-2, 3]]) resizePetalWorld(a, ...dimensions);
    assert.deepEqual(a.petals.map(p => [p.x, p.y]), positions);
    assert.equal(a.halfWidth, 4);
    advancePetals(a, 300);
    assert.ok(a.time <= .101);
    assert.equal(a.petals.length, 3);
});
