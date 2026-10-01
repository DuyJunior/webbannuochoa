# Soopi storefront motion

The shared storefront header uses a compact Soopi wordmark, centred navigation, consistent 44px controls and a quiet ivory background. The decorative pink corner and header petal canvas have been removed. Admin navigation is unaffected. Account links, role checks, logout CSRF, cart quantities and native search submission remain server-rendered.

## Files

- `resources/views/partials/store-header.blade.php`: shared header, collection panel, search and account controls.
- `resources/css/store-navigation.css`: final storefront stylesheet, responsive navigation and motion fallback.
- `resources/js/store-navigation.js`: mutually exclusive panels, Escape/focus restoration and pointer interaction.
- `resources/js/store-header.js`: existing hide-on-down/show-on-up behavior also closes navigation panels.
- `resources/js/store-motion.js`: visibility, preference and lazy-loading coordinator.
- `resources/js/three-petals.js`: actual Three.js WebGL2 geometry, lighting and rendering.
- `resources/js/petal-physics.js`: fixed 60 Hz simulation of gravity, air drag, spatially varying wind, flutter and angular inertia. Rendering remains capped at 30 fps.
- `resources/js/petal-wind-model.js`: damped spring bends and gusts for the large attached petals, with a rigid bottle mask.
- `resources/js/petal-wind.js`: source-space petal deformation shader and responsive image texture lifecycle.

## Rendering and accessibility

Three.js is installed through npm and pinned by the lockfile. Its approximately 135 KB gzip chunk loads dynamically only when the homepage flower is visible and motion is permitted. Interior pages have no decorative WebGL canvas; the global motion preference still controls their CSS interactions. Product photography stays ordinary accessible HTML. The campaign flower uses localized image deformation, not a 360-degree bottle model.

Rendering is capped at 30 fps, pixel ratio at 1.5 on fine pointers and 1 on coarse pointers. Animation pauses offscreen, when the tab is hidden and when motion is disabled. Normal page departure disposes geometry, material, renderer and context; bfcache pages retain a paused scene. Context loss hides the canvas and restoration restarts only active scenes.

The collection-panel motion switch shares `soopi.bloom.motion` with the existing hero control. Reduced-motion and Save-Data settings override that preference. WebGL unavailability leaves photography, search and navigation functional. Canvas decorations are hidden from assistive technology and cannot intercept pointer events. Closed panels are inert. There is no automatic video, sound, scroll hijacking or forced pointer capture in the Three.js effect.

## Build and validation

Run `npm ci`, `npm run build` and `php artisan view:cache` during deployment. `public/build` is ignored by Git and must be generated on the deployment host. No database migration or environment variable is required.

Validated locally in Chrome: homepage and product page, menu/search at 1440/800/390/320 pixels, search submission, Escape, shared motion controls, reduced motion, no-WebGL fallback, header scroll behavior and offscreen pause. Existing Laravel suite: 234 tests / 1671 assertions passed. Browser screenshots and the local test harness are in ignored `storage/app/motion-review` and `storage/app/three-header-review.cjs`.

Reference: https://threejs.org/docs/pages/WebGLRenderer.html

## Flying petals

The reusable flight renderer supports procedural curved petals, pointer velocity, repulsion and air drag. Non-cinematic flower variants use 12 petals on desktop or 8 on coarse-pointer devices. The current header does not instantiate it, and the cinematic hero uses attached-petal bending instead of detached floating meshes. These are aerodynamic approximations, not cloth simulations.

`node --test tests/Js/petal-physics.test.mjs` checks frame-rate independence, pointer force and inertia, long-run stability, input bursts and background-tab time clamping. Browser checks also cover visible flight after mouse/touch input and the hero CTA remaining clickable.

## Cinematic campaign hero

### Petal colour choices

The three controls below the hero now change its attached petals in place: rose,
wine and sage. They are native pressed-state buttons, keep the current URL, and
include an explicit link-like button to scroll back to the artwork. A live status
announces loading and the selected colour. No automatic scroll is triggered by
changing colour.

`petal-palette.js` shares linear-RGB colour values and highlight preservation
between the Three.js wind shader and a Canvas2D still fallback. Its source-space
silhouette follows `cinema-rose` and separately excludes the glass, cap and bow;
update that mask if the campaign photograph is replaced. The still overlay leaves
unmasked pixels transparent, so the original responsive photograph renders the
bottle and background unchanged. Colour surfaces are created on demand, cached
for the current responsive image, and rebuilt when its source changes.

Reduced motion, Save-Data, disabled effects and unavailable WebGL still support
colour selection without loading the Three.js renderer. Animated colour changes
use the existing capped render loop. Run `node --test tests/Js/petal-palette.test.mjs
tests/Js/petal-wind.test.mjs` for colour-mask and wind-model checks.

The approved concept is now used in the homepage hero, as responsive local WebP variants `public/images/bloom/cinema-rose-960.webp` and `cinema-rose-1680.webp`. Original artwork was created with the built-in image generator from the user's centered crystal-bottle, silver-bow, translucent blush-petal and sunset-mist concept. The source remains in the generation archive; runtime has no dependency on that archive.

`home-cinema.css` and `home-cinema.js` provide the responsive campaign layout, light/mist and explicit replay. The photograph no longer zooms or pans. Eight source-space petal regions bend independently under a varying breeze and damped spring inertia; roots have less movement than tips. Mouse movement, a touch, arrow keys and replay produce bounded gusts. The glass, silver bow and label are pinned by a soft-edged exclusion mask. Three.js also adds one particle draw call for 48/92 soft mist motes. Existing motion controls, viewport suspension, fallback and GPU cleanup apply to both effects.

The fullscreen photo texture follows the HTML image's `object-fit: cover` and `object-position`, including mobile crops. It uses an intrinsically sized, vertically flipped `ImageBitmap` of the already loaded responsive image, avoiding GPU allocation errors from CSS-sized DOM image elements. The HTML image stays beneath the canvas for fallback. Bitmaps remain available for context restoration and close on replacement/disposal; stale asynchronous decode results are discarded. Region coordinates are specific to `cinema-rose`; changing the artwork requires remapping the petal regions and bottle protection.

This is a responsive 2.5D web treatment of the approved still, not a rendered 8K film or a full 3D orbit around the photographed bottle. Desktop overlays readable copy on the left; mobile puts text above the photograph so the central bottle remains visible. The previous still-image bloom crossfade is bypassed for this hero; replay now triggers a light wash and a petal gust.

`node --test tests/Js/petal-physics.test.mjs tests/Js/petal-wind.test.mjs` covers both motion models (10 tests). Local browser checks in `storage/app/petal-wind-review.cjs` compare actual rendered frames for visible large-petal movement and an unchanged bottle label, then exercise pointer gusts, WebGL context loss/restoration, motion controls, touch/CTA, no-WebGL and Save-Data fallback.

## Refined masthead

Desktop height is 88px; the mobile masthead is 74px and switches at 900px. Balanced CSS grid columns keep the main navigation centred independently of the wordmark and search controls. The search label collapses below 1250px. SVG icons share stroke weight and target sizes, and the cart quantity sits alongside the bag rather than floating over the edge. Active page links use `aria-current`. Existing menu focus, account-role handling, mobile panels and hide-on-scroll behavior remain in place.

Validated the new header at 1920/1440/1280/1250/1080/901/900/800/390/320px, including search submission, menu opening, scroll suspension and the homepage WebGL effect. Authenticated account/cart checks pass at 1440/901/900/390/320px; `StorefrontThemeTest` passes with 78 assertions.
