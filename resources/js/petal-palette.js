// Shared by the animated shader and the still-photo fallback. Values are linear RGB.
export const PETAL_PALETTES = {
    rose: [1, 1, 1, 0],
    velvet: [.54, .075, .16, .94],
    sage: [.55, .76, .50, .94],
};
export const paletteFor = mood => PETAL_PALETTES[mood] || PETAL_PALETTES.rose;

// Source-space silhouette of cinema-rose. The clear glass, silver bow and cap
// are cut out separately so a strong tint cannot leave a rectangular halo.
export function createPetalMask(width = 1024, height = 576) {
    const canvas = document.createElement('canvas');
    canvas.width = width; canvas.height = height;
    const ctx = canvas.getContext('2d');
    ctx.scale(width, height);
    ctx.fillStyle = '#fff';
    ctx.filter = `blur(${width * .004}px)`;
    ctx.fill(new Path2D(`M .020 .615
        C .036 .558 .095 .483 .139 .490 L .176 .665
        C .153 .571 .145 .366 .060 .282
        C .050 .246 .097 .175 .143 .195 C .186 .218 .226 .285 .245 .360
        C .224 .250 .232 .124 .268 .092 C .296 .080 .346 .060 .346 .019
        C .372 .008 .391 .111 .388 .158 L .430 .380 L .554 .380
        C .578 .282 .620 .106 .693 .104 C .708 .095 .713 .064 .721 .053
        C .747 .061 .735 .111 .747 .123 L .778 .132 L .760 .166
        L .820 .169 L .785 .230 L .755 .342
        C .795 .354 .832 .333 .851 .335 C .901 .337 .921 .386 .936 .432
        L .949 .443 C .954 .470 .950 .507 .940 .520
        C .906 .500 .885 .479 .853 .499 C .832 .565 .806 .619 .786 .651
        C .830 .663 .861 .690 .883 .712 L .931 .824
        C .826 .864 .756 .902 .684 .901 C .578 .941 .475 .923 .390 .913
        C .281 .907 .187 .843 .133 .751 C .082 .714 .044 .674 .020 .615 Z`));
    ctx.globalCompositeOperation = 'destination-out';
    for (const [x, y, w, h, radius] of [
        [.423, .119, .146, .171, .013],
        [.380, .239, .220, .125, .016],
    ]) {
        ctx.beginPath(); ctx.roundRect(x, y, w, h, radius); ctx.fill();
        ctx.filter = 'none'; ctx.fill();
        ctx.filter = `blur(${width * .004}px)`;
    }
    // The foreground petal overlaps the lower-left glass; trace that diagonal
    // instead of cutting a rectangular pink patch out of the petal in front.
    const body = new Path2D(`M .365 .371 C .421 .349 .583 .348 .620 .371
        L .630 .399 L .627 .756 L .512 .807
        C .480 .778 .457 .707 .419 .660 L .367 .596 L .357 .423 Z`);
    ctx.fill(body); ctx.filter = 'none'; ctx.fill(body);
    return canvas;
}

export const PETAL_COLOR_GLSL = `
    vec3 petalColor(vec3 photo, vec4 palette, float mask) {
        float light = dot(photo, vec3(.2126, .7152, .0722));
        float highlight = smoothstep(.5, .94, light) * .7;
        vec3 dyed = mix(light * palette.rgb, photo, highlight);
        return mix(photo, dyed, mask * palette.a);
    }
`;
const linear = Array.from({ length: 256 }, (_, n) => {
    const v = n / 255;
    return v <= .04045 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4;
});
const encoded = v => Math.round(255 * (v <= .0031308 ? 12.92 * v : 1.055 * v ** (1 / 2.4) - .055));
export function dyePetalPixels(pixels, mask, mood, start = 0, end = pixels.length) {
    const palette = paletteFor(mood);
    if (!palette[3]) return;
    for (let i = start; i < end; i += 4) {
        const weight = mask[i + 3] / 255 * palette[3];
        if (!weight) continue;
        const r = linear[pixels[i]], g = linear[pixels[i + 1]], b = linear[pixels[i + 2]];
        const light = r * .2126 + g * .7152 + b * .0722;
        const h = Math.max(0, Math.min(1, (light - .5) / .44));
        const highlight = h * h * (3 - 2 * h) * .7;
        const blend = weight * (1 - highlight);
        pixels[i] = encoded(r + (light * palette[0] - r) * blend);
        pixels[i + 1] = encoded(g + (light * palette[1] - g) * blend);
        pixels[i + 2] = encoded(b + (light * palette[2] - b) * blend);
    }
}
