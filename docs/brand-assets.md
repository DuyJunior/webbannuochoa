# SOOPI petal-S logo

Approved by the user on 2026-10-03 from the supplied SOOPI logo board. The full horizontal signature keeps the folded-petal S, SOOPI wordmark and PERFUME STUDIO descriptor. Packaging panels are not part of the web logo.

## Assets

- `public/images/brand/soopi-petal-logo.png`: transparent high-resolution master.
- `public/images/brand/soopi-petal-logo.webp`: lossless 660px web derivative for shared logo partial.
- `public/images/brand/soopi-petal-symbol.png`: square icon master.
- `public/images/brand/soopi-petal-mark.svg`: transparent standalone emblem; an SVG viewport crops the emblem from the approved signature without redrawing its curves. Used by the shared `brand-mark` partial, studio badges and print surfaces. The old `ha-thu-mark.svg` URL now serves this same mark for compatibility.
- `public/images/brand/soopi-petal-favicon-48.png`, `soopi-petal-favicon-192.png`, `soopi-petal-touch-180.png`: resized icons.
- `public/favicon.ico`: 48px icon for browser fallback.

The same transparent signature is used on light and dark surfaces; the light variant is rendered through CSS brightness/invert, so the silhouette cannot drift. Keep its natural aspect ratio and avoid independent text/font substitutions.

## Generation provenance

Mode: built-in image_gen (not CLI). Source: user attachment `codex-clipboard-04857d14-6ffc-48aa-9adb-e23294a36ad1.png`.

### Logo extraction prompt

Use case: background-extraction.
Input image: edit target, approved SOOPI brand presentation.
Create the production website logo asset from ONLY the large flat logo in the upper half of this image. Extract its entire horizontal lockup: folded petal S emblem on left, SOOPI in the exact elegant high contrast serif on right, and PERFUME STUDIO tracking below. Keep the exact petal silhouette, its negative-space S curves, relative proportions, lettering, kerning, wordmark and deep aubergine-plum color. Do not redesign.
Output: clean flat plum logo on genuinely transparent background, crisp antialiased edges, high resolution wide canvas around 1536 by 560, tightly framed with only a small consistent safety margin of roughly 3 percent. No shadows, no paper texture, no cream rectangle. Exclude all three packaging mockup panels from the bottom, exclude any borders or presentation decoration. Text exactly "SOOPI" and "PERFUME STUDIO".

### Icon prompt

Use case: precise-object-edit.
Asset: website favicon/app icon for SOOPI.
Input: approved logo design; preserve the exact folded petal S emblem from upper left of reference.
Isolate just that emblem, same curved negative-space S and same sharp tips, same deep aubergine-plum solid color. NO letters or wordmark. Center upright on a clean solid warm ivory #fffaf5 square background, even spacing, emblem height 84 percent of canvas. Crisp flat graphic, no texture, no shadows, no embossing, no packaging, no decorative elements, no border. Match the approved silhouette exactly. High resolution square 1024x1024.
