import {
    AmbientLight, BufferGeometry, Color, DirectionalLight, DoubleSide,
    Float32BufferAttribute, Group, Mesh, MeshPhysicalMaterial,
    OrthographicCamera, Scene, SRGBColorSpace, WebGLRenderer,
} from 'three';
import { createPetalWorld, advancePetals, movePetalPointer, releasePetalPointer, gustPetals } from './petal-physics.js';
import { createCinemaMist } from './cinema-mist.js';
import { createPetalWind } from './petal-wind.js';

// A curved, ribbed silk surface, not a video or a rotating flat image.
function petalGeometry() {
    const positions = [], indices = [], rows = 32, columns = 48;
    for (let row = 0; row <= rows; row++) {
        const v = row / rows;
        const width = Math.pow(Math.sin(Math.PI * v), .7) * .67 + .015;
        for (let column = 0; column <= columns; column++) {
            const u = column / columns * 2 - 1;
            positions.push(u * width, (v - .5) * 1.9,
                .48 * u * u + .32 * Math.sin(v * Math.PI * 1.5) + .023 * Math.cos(u * 28) * Math.sin(v * Math.PI));
            if (row < rows && column < columns) {
                const a = row * (columns + 1) + column, b = a + columns + 1;
                indices.push(a, b, a + 1, b, b + 1, a + 1);
            }
        }
    }
    const geometry = new BufferGeometry();
    geometry.setAttribute('position', new Float32BufferAttribute(positions, 3));
    geometry.setIndex(indices); geometry.computeVertexNormals();
    return geometry;
}

export function createPetalScene(host) {
    const canvas = document.createElement('canvas');
    canvas.setAttribute('aria-hidden', 'true');
    const context = canvas.getContext('webgl2', { alpha: true, antialias: true, powerPreference: 'low-power' });
    if (!context) throw new Error('WebGL2 unavailable');
    const renderer = new WebGLRenderer({ canvas, context, alpha: true, antialias: true });
    renderer.outputColorSpace = SRGBColorSpace;
    renderer.setClearColor(0xffffff, 0);
    renderer.setPixelRatio(Math.min(devicePixelRatio || 1, matchMedia('(pointer: coarse)').matches ? 1 : 1.5));
    const hero = host.dataset.petalScene === 'hero';
    const scene = new Scene(), camera = new OrthographicCamera(-3, 3, 3, -3, .1, 30);
    camera.position.z = 10;
    scene.add(new AmbientLight(0xfff4f1, 2.1));
    const key = new DirectionalLight(0xffffff, 3); key.position.set(-3, 5, 6); scene.add(key);
    const rim = new DirectionalLight(0xd79cae, 1.5); rim.position.set(4, -1, 3); scene.add(rim);
    const group = new Group(); scene.add(group);
    const cinematic = hero && !!host.closest('.cinema-hero');
    const mist = cinematic ? createCinemaMist(scene, matchMedia('(pointer: coarse)').matches) : null;
    const wind = cinematic ? createPetalWind(scene, host) : null;
    const geometry = petalGeometry();
    const material = new MeshPhysicalMaterial({ color: 0xe8becb, metalness: .03, roughness: .5,
        clearcoat: .25, clearcoatRoughness: .45, sheen: .9, sheenColor: new Color(0xfff1ee),
        side: DoubleSide, transparent: true, depthWrite: false });
    const coarse = matchMedia('(pointer: coarse)').matches;
    const world = createPetalWorld(hero ? (cinematic ? 0 : (coarse ? 8 : 12)) : 3,
        { header: !hero, sourceRadius: cinematic ? 2 : 1.3 });
    const petals = [];
    for (let i = 0; i < world.petals.length; i++) {
        const mesh = new Mesh(geometry, material.clone());
        const scale = hero ? (cinematic ? .1 + (i % 4) * .025 : .16 + (i % 4) * .038) : .4 + i * .1;
        mesh.scale.setScalar(scale);
        mesh.material.opacity = 0;
        group.add(mesh); petals.push(mesh);
    }
    host.append(canvas);
    let active = false, lost = false, destroyed = false, last = 0, lastPointer = 0, lastPointerX = 0;
    function resize() {
        if (destroyed) return;
        const width = host.clientWidth, height = host.clientHeight;
        if (!width || !height) return;
        const halfHeight = hero ? 3 : 1.65, ratio = width / height;
        camera.left = -halfHeight * ratio; camera.right = halfHeight * ratio;
        camera.top = halfHeight; camera.bottom = -halfHeight; camera.updateProjectionMatrix();
        world.halfWidth = halfHeight * ratio; world.halfHeight = halfHeight;
        renderer.setSize(width, height, false);
        mist?.resize(world.halfWidth, renderer.getPixelRatio());
        wind?.resize();
        if (!active && !lost) renderer.render(scene, camera);
    }
    function tick(now) {
        if (now - last < 1000 / 30) return;
        const elapsed = (now - last) / 1000;
        advancePetals(world, elapsed); wind?.update(elapsed); last = now;
        mist?.update(world.time);
        petals.forEach((mesh, i) => {
            const p = world.petals[i];
            mesh.position.set(p.x, p.y, p.z);
            mesh.rotation.set(p.rx, p.ry, p.rz);
            const focusFade = cinematic ? Math.min(1, Math.max(Math.abs(p.x) / 1.15, Math.abs(p.y) / 2.3) ** 2) : 1;
            mesh.material.opacity = p.opacity * focusFade * (cinematic ? .58 : .88);
        });
        renderer.render(scene, camera);
    }
    const surface = hero ? host.parentElement : host.closest('header');
    const coordinates = event => {
        const bounds = host.getBoundingClientRect();
        return { x: ((event.clientX - bounds.left) / bounds.width * 2 - 1) * world.halfWidth,
            y: (1 - (event.clientY - bounds.top) / bounds.height * 2) * world.halfHeight };
    };
    const pointer = event => {
        if (!active || event.pointerType === 'touch') return;
        const { x, y } = coordinates(event), now = performance.now();
        if (lastPointer) wind?.gust(Math.max(-.16, Math.min(.16, (x - lastPointerX) * .2)));
        lastPointerX = x;
        movePetalPointer(world, x, y, (now - lastPointer) / 1000); lastPointer = now;
    };
    const reset = () => { releasePetalPointer(world); lastPointer = 0; };
    const gust = event => {
        if (!active || !hero || event.target.closest('a, button, input') || (event.button !== 0 && event.pointerType !== 'touch')) return;
        const { x, y } = coordinates(event); gustPetals(world, x, y);
        wind?.gust(1.1);
    };
    surface.addEventListener('pointermove', pointer, { passive: true });
    surface.addEventListener('pointerleave', reset);
    surface.addEventListener('pointerdown', gust, { passive: true });
    const replayGust = () => { if (active) { gustPetals(world, 0, -.8); wind?.gust(1.5); } };
    surface.addEventListener('cinema:gust', replayGust);
    const keyGust = event => {
        if (active && cinematic && event.target === surface && ['ArrowLeft', 'ArrowRight'].includes(event.key)) {
            wind?.gust(event.key === 'ArrowLeft' ? -.8 : .8);
        }
    };
    surface.addEventListener('keydown', keyGust);
    const resizer = new ResizeObserver(resize); resizer.observe(host);
    const mood = new MutationObserver(() => {
        const colors = { rose: 0xe8becb, velvet: 0x854258, sage: 0xbac5ad };
        const selected = document.querySelector('[data-bloom]')?.dataset.mood;
        material.color.setHex(colors[selected] || colors.rose);
        petals.forEach(mesh => mesh.material.color.copy(material.color));
        wind?.setMood(selected, !active);
        if (!active && !lost && !destroyed) renderer.render(scene, camera);
    });
    const bloom = document.querySelector('[data-bloom]');
    if (bloom) mood.observe(bloom, { attributes: true, attributeFilter: ['data-mood'] });
    const refresh = () => {
        renderer.setAnimationLoop(active && !lost && !destroyed ? tick : null);
        host.dataset.renderState = lost ? 'fallback' : active ? 'running' : 'paused';
    };
    const contextLost = event => { event.preventDefault(); lost = true; canvas.style.visibility = 'hidden'; refresh(); };
    const contextRestored = () => { lost = false; canvas.style.visibility = ''; resize(); refresh(); };
    canvas.addEventListener('webglcontextlost', contextLost);
    canvas.addEventListener('webglcontextrestored', contextRestored);
    resize();
    return {
        setActive(value) { active = value; last = performance.now(); if (!value) reset(); refresh(); },
        dispose() {
            destroyed = true; renderer.setAnimationLoop(null); resizer.disconnect(); mood.disconnect();
            surface.removeEventListener('pointermove', pointer); surface.removeEventListener('pointerleave', reset);
            surface.removeEventListener('pointerdown', gust);
            surface.removeEventListener('cinema:gust', replayGust);
            surface.removeEventListener('keydown', keyGust);
            canvas.removeEventListener('webglcontextlost', contextLost); canvas.removeEventListener('webglcontextrestored', contextRestored);
            wind?.dispose(); mist?.dispose(); geometry.dispose(); material.dispose(); petals.forEach(mesh => mesh.material.dispose());
            renderer.dispose(); renderer.forceContextLoss(); canvas.remove();
        },
    };
}
