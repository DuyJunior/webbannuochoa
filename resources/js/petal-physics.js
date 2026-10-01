// Lightweight aerodynamic approximation in scene units. Fixed timesteps make
// inertia independent of display refresh rate; this is not a cloth solver.
const STEP = 1 / 60;
const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

export function createPetalWorld(count, { random = Math.random, header = false, sourceRadius = 1.3 } = {}) {
    const world = { random, header, sourceRadius, time: 0, accumulator: 0, halfWidth: 4,
        halfHeight: header ? 1.65 : 3, pointer: { x: 0, y: 0, vx: 0, vy: 0, active: false }, petals: [] };
    for (let i = 0; i < count; i++) {
        const petal = {}; respawn(world, petal);
        petal.age = random() * 6 + .8;
        world.petals.push(petal);
    }
    return world;
}

function respawn(world, p) {
    const r = world.random, angle = r() * Math.PI * 2;
    const radius = world.header ? .65 : world.sourceRadius + r() * .6;
    Object.assign(p, {
        x: Math.cos(angle) * radius, y: Math.sin(angle) * radius + (world.header ? .6 : .35),
        z: (r() - .5) * 1.5, vx: Math.cos(angle) * .22 + .12, vy: .12 + r() * .25, vz: 0,
        rx: r() * 2, ry: r() * 3, rz: angle, wx: (r() - .5) * .6, wy: (r() - .5) * .8, wz: (r() - .5) * .7,
        phase: r() * Math.PI * 2, flutter: 1.8 + r() * 1.3, drag: .65 + r() * .55,
        age: 0, lifetime: 15 + r() * 13, opacity: 0,
    });
}

export function movePetalPointer(world, x, y, seconds) {
    const pointer = world.pointer, dt = clamp(seconds, 1 / 120, .1);
    pointer.vx = pointer.active ? clamp((x - pointer.x) / dt, -9, 9) : 0;
    pointer.vy = pointer.active ? clamp((y - pointer.y) / dt, -9, 9) : 0;
    pointer.x = x; pointer.y = y; pointer.active = true;
}

export function releasePetalPointer(world) {
    world.pointer.active = false;
    world.pointer.vx = world.pointer.vy = 0;
    // Petal velocities deliberately remain intact after the hand leaves.
}

export function gustPetals(world, x, y) {
    for (const p of world.petals) {
        const dx = p.x - x, dy = p.y - y, distance = Math.hypot(dx, dy);
        const influence = Math.max(0, 1 - distance / 2.4);
        p.vx = clamp(p.vx + dx / Math.max(distance, .15) * influence * 1.8, -3.5, 3.5);
        p.vy = clamp(p.vy + (dy / Math.max(distance, .15) + .4) * influence * 1.8, -3.5, 3.5);
        p.wz = clamp(p.wz + influence * (dx >= 0 ? 1 : -1) * 1.5, -3, 3);
    }
}

export function advancePetals(world, elapsed) {
    // Do not simulate minutes of missed frames after a background-tab pause.
    if (!Number.isFinite(elapsed) || elapsed <= 0) return;
    world.accumulator += Math.min(elapsed, .1);
    while (world.accumulator + 1e-9 >= STEP) {
        world.accumulator -= STEP;
        world.time += STEP;
        const t = world.time, pointer = world.pointer;
        const decay = Math.exp(-STEP * 4);
        pointer.vx *= decay; pointer.vy *= decay;
        for (const p of world.petals) {
            p.age += STEP;
            const flutter = Math.sin(t * p.flutter + p.phase);
            const airX = .25 + .32 * Math.sin(t * .38 + p.y * .65) + .15 * Math.cos(p.z + t * .7);
            const airY = .19 * Math.sin(t * .55 + p.x * .7 + p.phase);
            let ax = (airX - p.vx) * p.drag;
            let ay = (airY - p.vy) * p.drag - .24 + flutter * .16;
            let torque = 0;
            if (pointer.active) {
                const dx = p.x - pointer.x, dy = p.y - pointer.y;
                const distance = Math.hypot(dx, dy), influence = Math.max(0, 1 - distance / 1.3) ** 2;
                ax += (dx / Math.max(distance, .12) * 3.4 + pointer.vx * 1.4) * influence;
                ay += (dy / Math.max(distance, .12) * 3.4 + pointer.vy * 1.4) * influence;
                torque = (pointer.vx - pointer.vy) * influence * .65;
            }
            p.vx = clamp(p.vx + ax * STEP, -3.5, 3.5);
            p.vy = clamp(p.vy + ay * STEP, -3.5, 3.5);
            p.vz += (Math.sin(t * .6 + p.phase) * .12 - p.vz * .9 - p.z * .1) * STEP;
            p.x += p.vx * STEP; p.y += p.vy * STEP; p.z += p.vz * STEP;
            p.wx += (flutter * .45 - p.wx * .8) * STEP;
            p.wy += (Math.cos(t * .9 + p.phase) * .5 + p.vx * .2 - p.wy * .65) * STEP;
            p.wz += (flutter * .35 + torque - p.wz * .6) * STEP;
            p.rx += p.wx * STEP; p.ry += p.wy * STEP; p.rz += p.wz * STEP;
            const edge = Math.min(world.halfWidth + .25 - Math.abs(p.x), world.halfHeight + .3 - Math.abs(p.y));
            p.opacity = clamp(Math.min(p.age / 1.2, (p.lifetime - p.age) / 1.4, edge / .6), 0, 1);
            if (p.age > p.lifetime || edge < -.45) respawn(world, p);
        }
    }
}
