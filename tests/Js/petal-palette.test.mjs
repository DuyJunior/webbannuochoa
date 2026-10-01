import { test } from 'node:test';
import assert from 'node:assert/strict';
import { dyePetalPixels } from '../../resources/js/petal-palette.js';

test('glass and background pixels outside the petal mask stay bit-identical', () => {
    for (const mood of ['rose', 'velvet', 'sage']) {
        const pixels = new Uint8ClampedArray([228, 154, 134, 255, 250, 244, 237, 255]);
        const original = pixels.slice();
        dyePetalPixels(pixels, new Uint8ClampedArray(8), mood);
        assert.deepEqual(pixels, original);
    }
});
test('returning to rose retains every original photo channel', () => {
    const pixels = new Uint8ClampedArray([212, 130, 118, 255, 255, 246, 239, 255]);
    const original = pixels.slice();
    dyePetalPixels(pixels, new Uint8ClampedArray(8).fill(255), 'rose');
    assert.deepEqual(pixels, original);
});
test('wine and sage are distinct, preserve alpha and retain bright highlights', () => {
    const mask = new Uint8ClampedArray(8).fill(255);
    for (const [mood, dominant] of [['velvet', 0], ['sage', 1]]) {
        const pixels = new Uint8ClampedArray([220, 180, 175, 255, 255, 255, 255, 255]);
        dyePetalPixels(pixels, mask, mood);
        assert.ok(pixels[dominant] > pixels[1 - dominant]);
        assert.equal(pixels[3], 255);
        assert.equal(pixels[7], 255);
        assert.ok(Math.min(...pixels.slice(4, 7)) > 210);
    }
});
test('chunked fallback processing matches a whole-image pass', () => {
    const original = new Uint8ClampedArray([100, 70, 65, 255, 188, 125, 130, 255, 250, 226, 223, 255]);
    const mask = new Uint8ClampedArray([255, 255, 255, 80, 255, 255, 255, 255, 255, 255, 255, 20]);
    const whole = original.slice(), chunks = original.slice();
    dyePetalPixels(whole, mask, 'sage');
    dyePetalPixels(chunks, mask, 'sage', 0, 4);
    dyePetalPixels(chunks, mask, 'sage', 4, 12);
    assert.deepEqual(chunks, whole);
});
