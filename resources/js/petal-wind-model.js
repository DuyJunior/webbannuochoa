// Bends are in source-image UV units. The fixed step keeps the breeze consistent
// on slow phones and high-refresh displays, without jumping after a hidden tab.
const STEP = 1 / 60;
export const PETAL_SWAY_SPEED = 1.25;
const clamp = (value, low, high) => Math.max(low, Math.min(high, value));

export function createPetalWindModel() {
    return {
        time: 0, accumulator: 0, gust: 0, gustEnvelope: 0,
        petals: Array.from({ length: 8 }, (_, i) => ({
            x: 0, y: 0, vx: 0, vy: 0, curl: 0, vcurl: 0,
            phase: i * 1.73, stiffness: [5.2, 6.1, 6.6, 7.4, 5.7, 6.9, 7.8, 6.2][i],
            // The source image orders the left petals first, then the right ones.
            delay: [.35, .08, .24, .45, .92, 1.1, 1.35, 1.02][i],
            curlDirection: [1, -1, 1, -1, -1, 1, -1, 1][i],
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
        model.gust *= Math.exp(-.85 * STEP);
        // Input remains an immediate bounded impulse; its visible force rises softly.
        model.gustEnvelope += (model.gust - model.gustEnvelope) * (1 - Math.exp(-2.6 * STEP));
        for (const petal of model.petals) {
            const t = (model.time - petal.delay) * PETAL_SWAY_SPEED;
            const breeze = Math.sin(t * .48) * .68 + Math.sin(t * .21 + 1.1) * .32;
            const drift = Math.sin(t * .27 + petal.phase);
            const targetX = (breeze * .011 + drift * .0012 + model.gustEnvelope * .011) * petal.flexibility;
            const targetY = (breeze * .003 + drift * .001 + model.gustEnvelope * .0025) * petal.flexibility;
            const targetCurl = (breeze * .007 + drift * .001 + model.gustEnvelope * .007)
                * petal.flexibility * petal.curlDirection;
            const damping = 2 * Math.sqrt(petal.stiffness) * .9;
            petal.vx += ((targetX - petal.x) * petal.stiffness - petal.vx * damping) * STEP;
            petal.vy += ((targetY - petal.y) * petal.stiffness - petal.vy * damping) * STEP;
            const curlStiffness = petal.stiffness * .85;
            petal.vcurl += ((targetCurl - petal.curl) * curlStiffness
                - petal.vcurl * 2 * Math.sqrt(curlStiffness) * .93) * STEP;
            petal.x = clamp(petal.x + petal.vx * STEP, -.045, .045);
            petal.y = clamp(petal.y + petal.vy * STEP, -.03, .03);
            petal.curl = clamp(petal.curl + petal.vcurl * STEP, -.02, .02);
        }
    }
}

// Same source-space mask used by the shader: the glass, bow and label are rigid.
export function bottleProtection(x, y) {
    const outside = Math.max(.355 - x, x - .645, .105 - y, y - .82);
    const t = clamp(outside / .026, 0, 1);
    return 1 - t * t * (3 - 2 * t);
}
