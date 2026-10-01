import { CanvasTexture, Mesh, PlaneGeometry, ShaderMaterial, Texture, Vector2, Vector4, SRGBColorSpace } from 'three';
import { createPetalWindModel, advancePetalWind, pushPetalWind } from './petal-wind-model.js';
import { createPetalMask, paletteFor, PETAL_COLOR_GLSL } from './petal-palette.js';

// Source-space regions follow the individual petals of cinema-rose, with roots
// near the flower centre. Moving only their tips leaves the bottle untouched.
const REGIONS = [
    [.285, .23, .13, .27, .40, .60],
    [.11, .42, .15, .24, .30, .75],
    [.22, .63, .16, .22, .38, .86],
    [.34, .865, .20, .105, .49, .84],
    [.67, .22, .13, .24, .62, .61],
    [.79, .44, .14, .30, .67, .80],
    [.92, .51, .095, .18, .75, .73],
    [.75, .79, .20, .14, .54, .85],
];

export function createPetalWind(scene, host) {
    const image = host.parentElement.querySelector('.bloom-open');
    if (!image) return null;
    const model = createPetalWindModel();
    const paletteTarget = new Vector4(...paletteFor(host.closest('[data-bloom]')?.dataset.mood));
    const paletteMask = new CanvasTexture(createPetalMask());
    const uniforms = {
        uPhoto: { value: null }, uTime: { value: 0 },
        uPetalMask: { value: paletteMask }, uPalette: { value: paletteTarget.clone() },
        uCover: { value: new Vector2(1, 1) }, uOffset: { value: new Vector2() },
        uRegions: { value: REGIONS.map(p => new Vector4(...p.slice(0, 4))) },
        uRoots: { value: REGIONS.map(p => new Vector2(p[4], p[5])) },
        uBends: { value: REGIONS.map(() => new Vector2()) },
    };
    const material = new ShaderMaterial({
        uniforms, depthTest: false, depthWrite: false, toneMapped: false,
        vertexShader: `
            varying vec2 vUv;
            void main() {
                vUv = uv;
                gl_Position = vec4(position.xy, 1.0, 1.0);
            }
        `,
        fragmentShader: `
            uniform sampler2D uPhoto;
            uniform sampler2D uPetalMask;
            uniform vec4 uPalette;
            uniform float uTime;
            uniform vec2 uCover, uOffset;
            uniform vec4 uRegions[8];
            uniform vec2 uRoots[8], uBends[8];
            varying vec2 vUv;
            ${PETAL_COLOR_GLSL}

            void main() {
                vec2 q = vec2(vUv.x, 1.0 - vUv.y) * uCover + uOffset;
                float outside = max(max(.355 - q.x, q.x - .645), max(.105 - q.y, q.y - .82));
                float movable = smoothstep(0.0, .026, outside);
                vec2 bend = vec2(0.0);
                float totalWeight = 0.0;
                for (int i = 0; i < 8; i++) {
                    vec2 local = (q - uRegions[i].xy) / uRegions[i].zw;
                    float weight = 1.0 - smoothstep(.25, 1.1, length(local));
                    float flex = pow(clamp(length(q - uRoots[i]) / .34, 0.0, 1.0), 1.4);
                    // Fine, phase-delayed edge flutter rides on the spring bend.
                    float flutter = sin(uTime * 2.15 - local.y * 3.0 + float(i) * 1.73);
                    vec2 motion = uBends[i] + vec2(flutter * .0009, flutter * .0006);
                    bend += motion * weight * flex;
                    totalWeight += weight;
                }
                bend /= max(1.0, totalWeight);
                float edge = smoothstep(0.0, .035, min(min(q.x, 1.0 - q.x), min(q.y, 1.0 - q.y)));
                vec2 source = q - bend * movable * edge;
                gl_FragColor = texture2D(uPhoto, vec2(source.x, 1.0 - source.y));
                float mask = texture2D(uPetalMask, vec2(source.x, 1.0 - source.y)).a;
                gl_FragColor.rgb = petalColor(gl_FragColor.rgb, uPalette, mask);
                #include <colorspace_fragment>
            }
        `,
    });
    const geometry = new PlaneGeometry(2, 2);
    const mesh = new Mesh(geometry, material);
    mesh.frustumCulled = false;
    mesh.renderOrder = -10;
    mesh.visible = false;
    scene.add(mesh);
    let texture = null, disposed = false, loadedSource = '', pendingSource = '', loadVersion = 0;

    function resize() {
        if (!image.naturalWidth || !host.clientHeight) return;
        const imageAspect = image.naturalWidth / image.naturalHeight;
        const viewAspect = host.clientWidth / host.clientHeight;
        const sx = Math.min(1, viewAspect / imageAspect);
        const sy = Math.min(1, imageAspect / viewAspect);
        const position = getComputedStyle(image).objectPosition.split(' ').map(parseFloat);
        uniforms.uCover.value.set(sx, sy);
        uniforms.uOffset.value.set((1 - sx) * (position[0] || 0) / 100, (1 - sy) * (position[1] || 0) / 100);
    }

    async function load() {
        if (disposed || !image.complete || !image.naturalWidth) return;
        const source = image.currentSrc || image.src;
        if (loadedSource === source) { resize(); return; }
        if (pendingSource === source) return;
        pendingSource = source;
        const version = ++loadVersion;
        // A DOM image reports CSS dimensions, which can under-allocate a GPU
        // texture. Snapshot its decoded intrinsic pixels, without downloading again.
        let bitmap;
        try {
            bitmap = await createImageBitmap(image, {
                imageOrientation: 'flipY', premultiplyAlpha: 'none', colorSpaceConversion: 'none',
            });
        } catch {
            if (version === loadVersion) pendingSource = '';
            return; // Keep the original HTML photograph if decoding is unavailable.
        }
        if (disposed || version !== loadVersion || source !== (image.currentSrc || image.src)) {
            if (version === loadVersion) pendingSource = '';
            bitmap.close(); return;
        }
        const previous = texture;
        texture = new Texture(bitmap);
        texture.flipY = false; // ImageBitmap already carries its upload orientation.
        texture.colorSpace = SRGBColorSpace;
        texture.needsUpdate = true;
        uniforms.uPhoto.value = texture;
        loadedSource = source;
        pendingSource = '';
        resize();
        mesh.visible = true;
        host.dataset.petalWind = 'ready';
        previous?.dispose();
        previous?.image.close();
    }
    image.addEventListener('load', load);
    load();

    return {
        resize,
        update(elapsed) {
            uniforms.uPalette.value.lerp(paletteTarget, 1 - Math.exp(-Math.min(elapsed, .1) * 6));
            advancePetalWind(model, elapsed);
            uniforms.uTime.value = model.time;
            model.petals.forEach((petal, i) => uniforms.uBends.value[i].set(petal.x, petal.y));
        },
        gust(strength = 1) { pushPetalWind(model, strength); },
        setMood(mood, immediate = false) {
            paletteTarget.set(...paletteFor(mood));
            if (immediate) uniforms.uPalette.value.copy(paletteTarget);
        },
        dispose() {
            disposed = true; loadVersion++;
            image.removeEventListener('load', load);
            scene.remove(mesh); geometry.dispose(); material.dispose(); paletteMask.dispose(); texture?.dispose();
            texture?.image.close();
            delete host.dataset.petalWind;
        },
    };
}
