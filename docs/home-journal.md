# Soopi Journal Olfactif

Implemented 2026-10-01 from the approved editorial mockup. The existing header,
flower hero and scent gallery remain unchanged by this change.

## Content and behaviour

- `resources/views/partials/home-journal.blade.php` replaces the old four-card
  video block and separate live banner with one editorial composition.
- Three illustrated stories link to existing published articles: application,
  fragrance notes, and storage. Missing/unpublished articles fall back to the
  public journal index. These are reading links, not simulated video controls.
- Real active home/all videos are displayed by default before the live strip, with
  their original thumbnails, titles and sources. They use the existing modal.
  Associated product prices use the current sale price where present; inactive
  products are not linked. No database records were changed.
- The live strip links to the existing live page. Its live indicator and CTA
  reflect the server broadcast state and update through the existing poller.
  Hidden tabs skip polling; concurrent refreshes are prevented.
- Hover zoom is restrained and disabled for reduced motion and the existing
  motion-off toggle. Images are lazy-loaded WebP, with declared dimensions.

## Files

- `resources/css/home-journal.css`, imported by `home-couture.css`
- `resources/views/partials/home-journal.blade.php`, included by `home.blade.php`
- `app/Http/Controllers/HomeController.php`: published articles and active videos
- `public/js/livestream-home.js`: synchronizes the journal CTA and status
- `tests/Feature/HomeJournalTest.php`: publication/placement/price/live safeguards

## Images

Generated with the built-in image generation tool, then resized and compressed
with Sharp. Illustrative perfume bottles are unbranded, not catalog product
photographs. Original PNGs remain in the Codex generated-images directory.

Workspace output directory: `C:/xampp/htdocs/lar_vidu1/public/images/journal/`.

| Asset | WebP bytes | Original filename |
|---|---:|---|
| ritual.webp | 93058 | exec-d9990fe4-a498-4945-98c4-21b4a5a639cb.png |
| notes.webp | 57418 | exec-68797ac4-32c3-4363-8cc1-7ae97923ae8b.png |
| detail.webp | 41856 | exec-09dad819-ec8a-4b08-af5d-df2e12de05f2.png |
| live.webp | 83748 | exec-2e74ac14-29d7-44ed-a02b-f9dfb77b41d9.png |

Source directory:
`C:/Users/Admin/.codex/generated_images/01a0cd23-235d-77d3-925f-98f5a80a3580/`.

### Prompt set

Shared prefix for all four calls:

> Photoreal luxury perfume editorial photography for SOOPI website. Warm pearl-white and pale blush silk, dark plum accents, sculptural crystal glass, soft daylight with glass caustics, tactile silk, elegant quiet premium art direction. No typography, text, letters, logo, watermarks or UI. Horizontal photograph.

Append the corresponding subject:

- **ritual**: Wide 3:2 crop. Closeup of adult woman's elegant hands, one hand spraying a plain clear square perfume bottle filled with pale pink liquid toward the other wrist, fine mist backlit. Delicate gold bracelet. Pale pink silk foreground, blurred crystal sculpture behind. Realistic skin, anatomically correct hands. Composition fills whole frame, beautiful cinematic light, not a bottle catalog.
- **notes**: Square still life. A plain unlabeled clear pale-pink perfume bottle next to one fresh blush rose and a cut bergamot citrus with leaves, clear sculptural glass plinth, sunlit pale pink silk. Elegant luminous restrained palette, macro texture, no hands.
- **detail**: Square macro editorial photograph of a plain clear pink perfume bottle's silver cylindrical cap and bottle shoulder resting on blush satin folds. Closeup, silver reflections, physical glass detail, cap tilted slightly with interesting composition. Unlabeled, no lettering.
- **live**: Wide 3:2 still life. Plain unlabeled clear glass pink perfume bottle in front of a large translucent sculptural crystal loop, one pink silk petal, deep aubergine shadow on left fading to pearl blush right, luxurious backlit glass caustics, cinematic editorial set. Not dark overall.

## Validation

- Vite production build passed.
- 14 related feature tests passed (163 assertions), including the three new
  publication, media and live-state regression tests. Tests use isolated SQLite.
- Chrome at 1920, 1440, 1024, 768, 390 and 320 px: no horizontal document or
  story-card overflow; four new images load.
- Article/index/live links return successful responses.
- Keyboard video opening, correct embed source, Escape cleanup and focus return
  passed. External TikTok response was isolated for this UI check; this does not
  certify third-party playback availability.
- Reduced-motion hover and no-JavaScript article navigation passed. After the
  visibility revision, video cards are directly visible without JavaScript or
  an expand action; Chrome checks at 1440, 1024, 768, 390 and 320 px passed.
- Simulated on-air/off-air/network-error responses correctly update or preserve
  the live strip. No real broadcast was started.
- `git diff --check` passed.

Review screenshots: `storage/app/backups/journal-review/desktop.png` and
`storage/app/backups/journal-review/mobile.png` (local, ignored QA artifacts).

Changes are local only; no Git push or production deployment was performed.
