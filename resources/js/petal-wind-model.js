// Bends are in source-image UV units. The fixed step keeps the breeze consistent
// on slow phones and high-refresh displays, without jumping after a hidden tab.
const STEP = 1 / 60;
const clamp = (value, low, high) => Math.max(low, Math.min(high, value));

export function createPetalWindModel() {
    return {
        time: 0, accumulator: 0, gust: 0,
        petals: Array.from({ length: 8 }, (_, i) => ({
            x: 0, y: 0, vx: 0, vy: 0,
            phase: i * 1.73, stiffness: 10 + (i % 3) * 2.4,
            flexibility: [1, .85, 1.1, .8, .95, 1.15, 1.3, 1][i],
        })),
    };
}

export function pushPetalWind(model, strength = 1) {
    if (Number.isFinite(strength)) model.gust = clamp(model.gust + strength, -2, 2);
}

export function advancePetalWind(model, elapsed) {
    if (!Number.isFinite(elapsed) || elapsed <= 0) return;
    model.accumulator += Math.min(elapsed, .1);
    while (model.accumulator + 1e-9 >= STEP) {
        model.accumulator = Math.max(0, model.accumulator - STEP);
        model.time += STEP;
        model.gust *= Math.exp(-1.15 * STEP);
        const breeze = Math.sin(model.time * .87) * .65 + Math.sin(model.time * .37 + 1.1) * .35;
        for (const petal of model.petals) {
            const lag = Math.sin(model.time * 1.12 - petal.phase) * .38;
            const targetX = (breeze * .014 + lag * .009 + model.gust * .012) * petal.flexibility;
            const targetY = (Math.sin(model.time * .79 - petal.phase * .65) * .008
                + Math.sin(model.time * 1.83 + petal.phase) * .002 + model.gust * .004) * petal.flexibility;
            const damping = 2 * Math.sqrt(petal.stiffness) * .72;
            petal.vx += ((targetX - petal.x) * petal.stiffness - petal.vx * damping) * STEP;
            petal.vy += ((targetY - petal.y) * petal.stiffness - petal.vy * damping) * STEP;
            petal.x = clamp(petal.x + petal.vx * STEP, -.045, .045);
            petal.y = clamp(petal.y + petal.vy * STEP, -.03, .03);
        }
    }
}

// Same source-space mask used by the shader: the glass, bow and label are rigid.
export function bottleProtection(x, y) {
    const outside = Math.max(.355 - x, x - .645, .105 - y, y - .82);
    const t = clamp(outside / .026, 0, 1);
    return 1 - t * t * (3 - 2 * t);
}
