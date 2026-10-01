import test from 'node:test';
import assert from 'node:assert/strict';
import { createPetalWorld, advancePetals, movePetalPointer, releasePetalPointer, gustPetals } from '../../resources/js/petal-physics.js';

function world() {
    let seed = 1234;
    return createPetalWorld(12, { random: () => ((seed = Math.imul(seed, 1664525) + 1013904223 >>> 0) / 4294967296) });
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
