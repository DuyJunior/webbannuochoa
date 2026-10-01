import test from 'node:test';
import assert from 'node:assert/strict';
import {
    createPetalWindModel,
    advancePetalWind,
    pushPetalWind,
    bottleProtection,
} from '../../resources/js/petal-wind-model.js';

test('attached petals follow the same path at 30 and 60 fps', () => {
    const slow = createPetalWindModel(), fast = createPetalWindModel();
    pushPetalWind(slow); pushPetalWind(fast);
    for (let frame = 0; frame < 300; frame++) advancePetalWind(slow, 1 / 30);
    for (let frame = 0; frame < 600; frame++) advancePetalWind(fast, 1 / 60);
    assert.deepEqual(slow.petals, fast.petals);
    assert.equal(slow.time, fast.time);
    assert.equal(slow.gust, fast.gust);
});

test('the natural breeze bends attached petals without pointer input', () => {
    const model = createPetalWindModel();
    const initial = model.petals.map(({ x, y }) => ({ x, y }));
    for (let frame = 0; frame < 180; frame++) advancePetalWind(model, 1 / 60);
    assert.ok(model.petals.some((petal, index) =>
        Math.hypot(petal.x - initial[index].x, petal.y - initial[index].y) > 1e-6));
});

test('repeated gusts saturate safely, decay and retain stable long-running motion', () => {
    const model = createPetalWindModel(), calm = createPetalWindModel();
    for (let click = 0; click < 100; click++) pushPetalWind(model, 1);
    const peak = model.gust;
    assert.equal(peak, 2);
    for (let click = 0; click < 100; click++) pushPetalWind(model, 1);
    assert.equal(model.gust, peak, 'further input must not increase a saturated gust');

    let gustChangedBend = false;
    for (let frame = 0; frame < 10800; frame++) {
        advancePetalWind(model, 1 / 60);
        advancePetalWind(calm, 1 / 60);
        if (frame < 120 && model.petals.some((petal, index) =>
            Math.hypot(petal.x - calm.petals[index].x, petal.y - calm.petals[index].y) > 1e-6)) {
            gustChangedBend = true;
        }
        for (const petal of model.petals) {
            for (const key of ['x', 'y', 'vx', 'vy']) {
                assert.ok(Number.isFinite(petal[key]) && Math.abs(petal[key]) < 1,
                    `${key} must remain a small finite deformation`);
            }
            assert.ok(Math.abs(petal.x) <= .045 && Math.abs(petal.y) <= .03,
                'bending must stay within the safe image deformation envelope');
        }
    }
    assert.ok(gustChangedBend, 'a gust must actually change the petal motion');
    assert.ok(Math.abs(model.gust) < peak * .001, 'the impulse must dissipate');
});

test('wind input handles signed bursts and ignores invalid values', () => {
    const model = createPetalWindModel();
    pushPetalWind(model, -100); assert.equal(model.gust, -2);
    for (const strength of [NaN, Infinity, -Infinity]) pushPetalWind(model, strength);
    assert.equal(model.gust, -2);
    pushPetalWind(model, 100); assert.equal(model.gust, 2);
});

test('a suspended tab cannot jump the petal simulation by minutes', () => {
    const resumed = createPetalWindModel(), bounded = createPetalWindModel();
    advancePetalWind(resumed, 300);
    advancePetalWind(bounded, .1);
    assert.deepEqual(resumed.petals, bounded.petals);
    assert.equal(resumed.time, bounded.time);
    assert.ok(resumed.time <= .101);
    const before = structuredClone(resumed);
    for (const elapsed of [0, -1, NaN, Infinity]) advancePetalWind(resumed, elapsed);
    assert.deepEqual(resumed, before);
});

test('bottle and silver bow stay fully pinned while the outer petals can move', () => {
    for (const x of [.355, .5, .645]) {
        for (const y of [.105, .45, .82]) {
            assert.equal(bottleProtection(x, y), 1, `bottle point ${x},${y} is pinned`);
        }
    }
    for (const [x, y] of [[0, 0], [1, 1], [.1, .45], [.9, .45], [.5, .95]]) {
        assert.equal(bottleProtection(x, y), 0);
    }
    const feather = bottleProtection(.355 - .013, .45);
    assert.ok(feather > 0 && feather < 1, 'a soft border avoids a visible cutout seam');
});
