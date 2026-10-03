// Lightweight aerodynamic approximation in scene units. Fixed timesteps make
// inertia independent of display refresh rate; this is not a cloth solver.
const STEP = 1 / 60;
const clamp = (value, min, max) => Math.max(min, Math.min(max, value));

export function createPetalWorld(count, { random = Math.random, header = false, falling = false, sourceRadius = 1.3 } = {}) {
    const world = { random, header, falling, sourceRadius, time: 0, accumulator: 0, halfWidth: 4,
        halfHeight: header ? 1.65 : 3, pointer: { x: 0, y: 0, vx: 0, vy: 0, active: false }, petals: [] };
    for (let i = 0; i < count; i++) {
        const petal = {}; respawn(world, petal);
        if (falling) {
            // Populate the full fall at first paint, rather than release a row of petals together.
            const progress = (i + .2 + random() * .6) / Math.max(count, 1);
            petal.y = world.halfHeight - .3 - progress * (world.halfHeight * 2 - .7);
            petal.age = 1.2 + random() * .8;
        } else {
            petal.age = random() * 6 + .8;
        }
        world.petals.push(petal);
    }
    return world;
}

function respawn(world, p) {
    if (world.falling) {
        respawnFalling(world, p);
        return;
    }
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

function respawnFalling(world, p) {
    const r = world.random, lane = .2 + r() * .64, fallSpeed = .28 + r() * .15;
    Object.assign(p, {
        x: world.halfWidth * lane, y: world.halfHeight + .24,
        z: (r() - .5) * .9, vx: (r() - .5) * .06, vy: -fallSpeed, vz: 0,
        rx: r() * 2, ry: r() * 3, rz: (r() - .5) * Math.PI,
        wx: (r() - .5) * .17, wy: (r() - .5) * .24, wz: (r() - .5) * .18,
        phase: r() * Math.PI * 2, flutter: .75 + r() * .4, drag: 1.2 + r() * .35,
        driftLane: lane, fallSpeed, age: 0,
        lifetime: (world.halfHeight * 2 + 1) / fallSpeed + 5, opacity: 0,
    });
}

/** Resize falling lanes with the camera, including the first narrow-screen layout. */
export function resizePetalWorld(world, halfWidth, halfHeight = world.halfHeight) {
    if (!Number.isFinite(halfWidth) || halfWidth <= 0 || !Number.isFinite(halfHeight) || halfHeight <= 0) return;
    if (world.falling) {
        for (const p of world.petals) {
            p.x *= halfWidth / world.halfWidth;
            p.y *= halfHeight / world.halfHeight;
            p.lifetime = Math.max(p.age + 3, (halfHeight * 2 + 1) / p.fallSpeed + 5);
        }
    }
    world.halfWidth = halfWidth;
    world.halfHeight = halfHeight;
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
        if (world.falling) {
            // A hand stirs the air; it never launches the lightweight petals upward.
            p.vx = clamp(p.vx + dx / Math.max(distance, .15) * influence * .12, -.25, .25);
            p.vy = clamp(p.vy + dy / Math.max(distance, .15) * influence * .035, -.48, -.22);
            p.wz = clamp(p.wz + influence * (dx >= 0 ? 1 : -1) * .065, -.22, .22);
            continue;
        }
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
            if (world.falling) {
                advanceFalling(world, p);
                continue;
            }
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

function advanceFalling(world, p) {
    const t = world.time, pointer = world.pointer;
    p.age += STEP;
    const flutter = Math.sin(t * p.flutter + p.phase);
    const airX = .08 * Math.sin(t * .32 + p.phase) + .024 * flutter;
    let ax = (airX - p.vx) * p.drag + (p.driftLane * world.halfWidth - p.x) * .12;
    let ay = (-p.fallSpeed + flutter * .018 - p.vy) * 1.8;
    let torque = 0;
    if (pointer.active) {
        const dx = p.x - pointer.x, dy = p.y - pointer.y;
        const distance = Math.hypot(dx, dy), influence = Math.max(0, 1 - distance / .9) ** 2;
        ax += (dx / Math.max(distance, .12) * .16 + pointer.vx * .018) * influence;
        ay += (dy / Math.max(distance, .12) * .045 + pointer.vy * .005) * influence;
        torque = (pointer.vx - pointer.vy) * influence * .008;
    }
    p.vx = clamp(p.vx + ax * STEP, -.25, .25);
    p.vy = clamp(p.vy + ay * STEP, -.48, -.22);
    p.vz += (Math.sin(t * .4 + p.phase) * .035 - p.vz * .8 - p.z * .08) * STEP;
    p.x += p.vx * STEP; p.y += p.vy * STEP; p.z += p.vz * STEP;
    p.wx += (flutter * .065 - p.wx * .65) * STEP;
    p.wy += (Math.cos(t * .45 + p.phase) * .08 - p.wy * .7) * STEP;
    p.wz += (flutter * .055 + torque - p.wz * .75) * STEP;
    p.rx += p.wx * STEP; p.ry += p.wy * STEP; p.rz += p.wz * STEP;
    const verticalEdge = world.halfHeight + .3 - Math.abs(p.y);
    const sideEdge = Math.min(p.x - world.halfWidth * .06, world.halfWidth * .98 - p.x);
    p.opacity = clamp(Math.min(p.age / 1.25, (p.lifetime - p.age) / 1.4,
        verticalEdge / .5, sideEdge / Math.min(.35, world.halfWidth * .12)), 0, 1);
    if (p.age > p.lifetime || verticalEdge < -.2 || sideEdge < -.08) respawnFalling(world, p);
}
