import { BufferGeometry, Float32BufferAttribute, Points, ShaderMaterial, AdditiveBlending } from 'three';

// One draw call, no texture downloads: depth layers of soft, slowly drifting mist.
export function createCinemaMist(scene, coarsePointer) {
    const count = coarsePointer ? 48 : 92;
    const positions = [], seeds = [];
    for (let i = 0; i < count; i++) {
        positions.push((Math.random() - .5) * 2, (Math.random() - .5) * 6.5, (Math.random() - .5) * 3);
        seeds.push(Math.random(), Math.random(), Math.random());
    }
    const geometry = new BufferGeometry();
    geometry.setAttribute('position', new Float32BufferAttribute(positions, 3));
    geometry.setAttribute('aSeed', new Float32BufferAttribute(seeds, 3));
    const material = new ShaderMaterial({
        transparent: true, depthWrite: false, depthTest: false, blending: AdditiveBlending,
        uniforms: { uTime: { value: 0 }, uWidth: { value: 4 }, uPixelRatio: { value: 1 } },
        vertexShader: `
            attribute vec3 aSeed;
            uniform float uTime, uWidth, uPixelRatio;
            varying float vAlpha, vSoft;
            void main() {
                vec3 p = position;
                p.x = p.x * uWidth + sin(uTime * .09 + aSeed.x * 6.283) * .24;
                p.y = mod(p.y + 3.5 + uTime * (.022 + aSeed.y * .035), 7.) - 3.5;
                p.z += sin(uTime * .13 + aSeed.z * 6.283) * .15;
                gl_Position = projectionMatrix * modelViewMatrix * vec4(p, 1.);
                vSoft = step(.8, aSeed.z);
                gl_PointSize = mix(2. + aSeed.x * 3., 14. + aSeed.x * 22., vSoft) * uPixelRatio;
                float edge = 1. - smoothstep(2.6, 3.5, abs(p.y));
                float breathing = .65 + .35 * sin(uTime * .35 + aSeed.y * 6.283);
                vAlpha = mix(.44, .14, vSoft) * edge * breathing;
            }
        `,
        fragmentShader: `
            varying float vAlpha, vSoft;
            void main() {
                float d = length(gl_PointCoord - vec2(.5)) * 2.;
                if (d > 1.) discard;
                float glow = pow(max(0., 1. - d * d), mix(1.6, 2.8, vSoft));
                gl_FragColor = vec4(1., .89, .77, glow * vAlpha);
            }
        `,
    });
    const points = new Points(geometry, material);
    points.frustumCulled = false;
    points.renderOrder = 3;
    scene.add(points);
    return {
        update(time) { material.uniforms.uTime.value = time; },
        resize(halfWidth, ratio) { material.uniforms.uWidth.value = halfWidth; material.uniforms.uPixelRatio.value = ratio; },
        dispose() { scene.remove(points); geometry.dispose(); material.dispose(); },
    };
}
