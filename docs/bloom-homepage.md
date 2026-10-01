# Soopi — Hoa hương (The Bloom Edit)

The homepage implements the approved white/blush pleated-flower direction. Existing catalog data, search, filtering, comparison, video dialogs, account and livestream routes remain in use. No database migration or third-party animation service is required.

## Implementation

- `resources/views/partials/home-bloom.blade.php`: hero, genuine product link where present, motion controls and mood choices.
- `resources/css/home-bloom.css`: Bloom hero styling, CSS perspective, mood lighting and shared storefront foundations, responsive layout and reduced-motion support.
- `resources/css/store-atelier.css`: shared typography, forms, buttons, product details, account, checkout, discovery, journal, livestream and full homepage sections. Both theme styles are loaded by `layouts.store`; admin uses its separate layout.
- `resources/css/home-couture.css`: the homepage's larger floral hero, arched product gallery, collection navigation, explicit volume-selection links, dark video section and staggered collections. This layer is loaded only by the homepage view, including filtered catalog results. It adds no JavaScript or third-party requests.
- `resources/css/home-reference.css`: the later user-selected composition (`codex-clipboard-3f1fce22-fc01-40d9-8bde-cd15a78d8dfe.png`): ivory silk hero, three interactive scene cards and petal mood swatches. At the user's subsequent request, the original shared header with a full search field, account/cart actions and separate navigation row is restored. Homepage shopping continues below the reference composition.
- `resources/js/home-bloom.js`: artwork opening transition, limited perspective tilt/drag, modest scroll parallax, mood selection and optional local motion preference.
- `public/images/bloom/flower-open.webp` and `flower-closed.webp`: local alpha artwork, about 456 KB combined. No remote image requests for the hero.

The sculpture is rendered artwork with CSS 3D perspective, not a 360-degree mesh. Opening is a transition between matched closed/open artwork. There is no physical cloth simulation or scroll hijacking. The bottle label is excluded from most of the color overlay so changing atmosphere does not recolor the product. Mood controls change the visual atmosphere; the separate scent finder remains the route for product recommendations.

The first scene link returns to the hero and replays opening when motion is enabled. The second returns to the hero and changes the perspective slightly; the focused sculpture also accepts arrow keys and Escape. The third navigates to the real product catalog. All three retain useful anchor destinations without JavaScript. Reduced-motion preferences continue to take precedence over interactive animation.

Animation is disabled for reduced-motion settings and defaults off for data-saving connections. The open artwork and shopping links work without JavaScript. Animation work stops while the hero is offscreen or the tab is hidden; no perpetual rendering loop is used. The storefront social links are grouped into a native details control.

## Assets and provenance

Created with the built-in image generation tool, based on the user-approved concept `exec-e2469950-321a-4788-b6f4-3455395f19dc.png`. These are AI-created decorative editorial assets, not Unsplash photographs or official product photography. Product cards continue using the store's real catalog imagery and prices.

Open-artwork prompt:

> Create a PRODUCTION WEBSITE ASSET, extracted/recreated from the top hero flower scene in reference. ONLY the large sculptural OPEN pale blush pink pleated silk five-petal flower WITH the clear Miss Dior perfume bottle silver bow standing centrally inside on tiny concealed ivory base. NO text other than realistic bottle label. NO website, headings, panels, background, floor, buttons, captions, logo, no frame. GENUINE TRANSPARENT alpha background, clean edges around the entire sculpture. Square image 1536x1536 style framing, all petals fit with 5% clear margin, object fills 90%. Preserve exact couture pale blush silk texture, fine pleated ridges, small burgundy underside on ONE petal, photorealistic glass pink liquid perfume bottle, clear faceted cap silver bow, diffuse natural studio light from upper left. Main product is vertically centered, flower spread gracefully outward, dramatic sculptural depth, front curled petal low foreground, taller rear petals behind bottle. Three-quarter frontal elevated viewing angle as reference. Product must be recognizable, no large shadow outside object, no checkerboard painted in image. This is ONE high-resolution cutout for a working luxury perfume storefront, not a UI mockup.

Closed-artwork prompt:

> Edit this production cutout into its CLOSED flower-bud state for an opening animation. Same square framing, same camera, same lighting, same fine pale blush pleated silk with burgundy underside, same central Miss Dior bottle. Petals curve upward and inward to wrap around and hide bottle body, only clear glass cap and silver bow visible peeking above the folded bud center. A refined tall elegant closed silk flower bud, about 65% canvas width and 85% canvas height, centered, a few petals curling at tips, soft photoreal fabric. Keep entire bud inside frame with clear transparent margin. Genuine TRANSPARENT background. No text, no website, no scenery, no surface, no additional objects. This is a matched closed state of the same flower, not a different flower.

## Build and review

### Reference-composition assets

Three further decorative assets were created with the built-in image generation tool and converted to local WebP (about 180 KB combined). They are AI-created illustrations, not official product photos. Catalog cards continue using their existing product records and images.

- `public/images/bloom/ivory-silk.webp` (1800 × 1200): hero and scene background.
- `public/images/bloom/collection-reveal.webp` (850 × 850): illustrative third scene card, linking to the complete store collection rather than claiming every illustrated bottle is available.
- `public/images/bloom/petal-rose.webp` (420 × 280, alpha preserved): single petal; velvet and sage moods use CSS color filters.

Final background prompt:

> Production website background texture for a luxury perfume homepage. Wide horizontal 3:2 image of luminous ivory-white silk gently draped across a horizontal surface, shallow elegant soft diagonal folds, subtle pearl highlights, very soft pale blush reflected light, bright diffuse daylight. Extremely understated, mostly smooth white negative space in upper left two thirds for dark text. Gentle fabric folds concentrated along lower edge and right side. Macro photoreal material. No flower, no perfume bottles, no objects, no text, no logo, no border, no interface. Must be usable as a full-bleed quiet website background behind a separate pink flower sculpture.

Final collection prompt (user screenshot supplied as visual reference):

> Create ONLY a square production editorial photograph asset for the THIRD card 'Mở bộ sưu tập' in this reference web design, not a webpage. A beautifully composed close view under a large curled pale blush pleated silk flower petal hanging across the upper half of the frame. On a short matte ivory limestone platform below, a small clear square pink Miss Dior Blooming Bouquet bottle with silver bow left, round Chanel Chance Eau Tendre pink bottle middle, clear Byredo Blanche bottle with black dome cap right. Finely pleated pink silk petals and softly draped ivory silk foreground. Delicate diffuse daylight, ivory white, soft rose and deep burgundy accents, realistic glass. Camera low front three-quarter view, bottles fully visible, graceful depth and luxury French perfume editorial mood, matching the image reference. No interface, captions, overlay text, framing, panels or watermarks. Product names only on bottle labels. This is decorative editorial artwork, not a product listing photo.

Final petal prompt:

> A single sculptural pale blush pink pleated silk flower petal, isolated on a genuinely transparent background. Elegant wide fan-shaped petal with many very fine radial pleated ridges, curled bottom tip pointing to bottom left, rounded scalloped open upper edge fanning toward upper right. Viewed three-quarter frontal with depth, like couture folded silk, 3D photoreal studio material, subtle soft shading and translucent edges. Landscape 3:2 framing, petal centered fills 85%, entire petal fits. One petal only, no whole flower, no stem, no leaf veins, no bottle, no text, no logo, no frame, no floor, no cast background shadow. This is a production mood-swatch cutout for a luxury perfume website.

Flower-composition Chrome review captures: `storage/app/backups/atelier-review/reference-desktop.png` and `reference-mobile.png` (before the requested header restoration). Verified search submission, scene replay, perspective interaction with pointer and keyboard, and mood changes. `header-restored.png` shows the restored header; search submission, category dropdown and mobile widths were checked again.

The shared silk background `public/images/bloom/silk-atelier.webp` is a locally served, 1400-pixel WebP generated using the built-in image tool. It supplies secondary-page headings, authentication artwork and the common footer invitation. Its prompt:

> Production web background asset for SOOPI luxury perfume store. Extremely delicate couture pale blush pink pleated silk forming a graceful abstract five-petal bloom on the RIGHT THIRD of a wide 3:2 horizontal image. Mostly luminous pearl ivory empty negative space LEFT TWO THIRDS, bottom graceful draped silk diagonally tapering away. Macro fine tactile silk pleat detail, translucent thin edges, subtle shadows, sophisticated warm-white soft studio light, palette white and pale dusty pink only. NO perfume bottle, product, text, logos, UI, frame, people, props, literal leaves, gold or bold colors. One airy monochrome editorial photograph-like sculptural silk backdrop that can be used behind dark text on page headings, login art, gift section and footer. Full bleed, high resolution.

`StorefrontThemeTest` checks 25 public and authenticated storefront views with isolated test data, including intact checkout and account form fields. Optional `SOOPI_EXPORT_REVIEW=1` exports test HTML into ignored storage for visual review; it does not add a production preview route. The desktop/mobile review at 1440 and 390 pixels checks document overflow. Additional real-page browser checks cover home animation, filtering, comparison, product options, the quiz, discovery box, FAQ and contact controls. No live order, payment or customer account is created by browser review.

Run `npm run build` when deploying. Include the new source files and `public/images/bloom` in the deployment. Production does not automatically synchronize with the local development server.

Chrome review checks: widths 320, 375, 390, 768, 1024 and 1440; mood selection; perspective response; opening replay; persisted motion setting; filtering; comparison; reduced motion; no-JavaScript fallback; loaded images and contact menu. Review captures are local under `storage/app/backups/bloom-review`.

Latest homepage review: 226 tests / 1610 assertions passed. The added collection tabs and each product's "Chọn dung tích" link were checked in Chrome, along with loaded lazy images throughout the page. Review captures are in `storage/app/backups/atelier-review/couture-shopping-1440.png`, `couture-shopping-390.png` and `home-complete.png`. To capture the full page reliably, scroll with instant behavior and allow lazy images/intersection reveals to load before taking the screenshot.

### Pearl Atelier, September 30

Replaced the lower-homepage couture stylesheet with a consistent pearl/blush surface system, aligned collection cards, clearer product pricing and buying controls, a light video gallery, coordinated live/gifting/offer/experience sections and a compact horizontal recently-viewed layout. Removed obsolete inline homepage styles. The restored header and approved flower hero are preserved. Styles remain scoped to the homepage.

`home-gallery.js`, imported by `home-bloom.js`, supplies bounded one-degree pointer tilt and image highlights, only on fine pointers. It resets on pointer exit, blur, motion preference changes and the existing effect toggle. It has no idle animation loop. Content and links work without it.

`public/images/bloom/gift-atelier.webp` is decorative AI-generated campaign art, not a product listing photo. It was generated with the built-in image tool, then converted to a 1440×1080 WebP (79,916 bytes). Source: `exec-5e6806ad-4576-477e-8f3e-77e01ee0375c.png`. Prompt:

> Production website editorial asset for a futuristic luxury perfume boutique, landscape 4:3. An exquisite clear unbranded sculptural perfume bottle containing very pale blush liquid, a heavy frosted glass cap, standing on a low translucent glass plinth. Beside it a pristine pearl-white rigid gift box with a thin dusty-rose silk ribbon, and one delicate sculptural pleated blush silk petal hovering behind the bottle. Luminous pearl white studio with soft blush reflection, subtle champagne-chrome edges, polished glass highlights and grounded realistic shadows. Composition objects in right two thirds, ample negative space left, carefully restrained and architectural, modern luxury cosmetics campaign. No gold/yellow, no flowers bouquet, no busy background, no text or logo, no UI, no border, no watermark. Photoreal material quality, quiet depth, physically plausible reflections, one bottle and one box only.

`storage/app/pearl-review.cjs` checks actual homepage rendering at 1440, 1024, 768, 390 and 320 pixels; lazy image loading, no horizontal overflow, comparison controls, recently viewed products, pointer depth/reduced motion and keyboard opening of the video dialog. Captures: `storage/app/backups/atelier-review/pearl-*.png`. It does not create orders or change catalog/video data. Uploaded video thumbnails are deliberately retained as entered by the store administrator.
