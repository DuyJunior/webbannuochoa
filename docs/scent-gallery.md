# Soopi — approved crystal shopping gallery

Implemented from the user's approved September 30 mockup. The original header, Bloom hero, and lower homepage sections remain intact. The shopping section is now a live Blade component, not a flattened screenshot.

## Files and behavior

- `resources/views/partials/scent-gallery.blade.php`: heading, single toolbar, featured composition, collection disclosure and empty state.
- `resources/views/partials/scent-product.blade.php`: actual product name, price, volume, product link, authenticated wishlist form and comparison button.
- `resources/css/scent-gallery.css`: component-scoped desktop/mobile layout, imported by `home-couture.css`.
- `resources/js/home-gallery.js`: one-degree pointer perspective and lighting, reduced-motion/touch fallback; no animation loop while idle. Sorting menu closes with Escape or an outside click.
- `config/scent-gallery.php`: exact catalog slug to editorial artwork mapping and preferred display order. Unmapped products retain their administrator-selected image. Full product detail pages keep their existing photography.
- `HomeController`: partitions the existing active homepage selection without duplicates. The preferred feature is Miss Dior, with the first active result as fallback. Filtered requests do not insert or reorder featured products. The remaining homepage products are accessible through a native disclosure, including when JavaScript is disabled.

No price, inventory or video records were changed. The mockup's decorative 360-degree label is intentionally omitted: the implemented visual effect is pointer perspective, not a true multi-angle 3D product model.

## Generated image assets

Created with the built-in image generation tool using the user's approved screenshot as the reference. These are editorial compositions; no prices or interface text are baked into them. Original generated PNGs remain in the default generated-images directory. The following compressed production WebPs are in the repository:

| Asset | Size | Generated source |
|---|---:|---|
| `public/images/gallery/miss-dior.webp` | 119,780 bytes | `exec-4d91506f-fa5a-4d19-a4bc-84160f8a1a4b.png` |
| `public/images/gallery/sauvage.webp` | 24,662 bytes | `exec-1fcb98b1-e71a-46e1-9233-bd2479b4dfdc.png` |
| `public/images/gallery/chanel.webp` | 30,572 bytes | `exec-43c2d9ed-b4eb-4603-83f7-f38ec231812e.png` |
| `public/images/gallery/delina.webp` | 32,152 bytes | `exec-8f91c8b6-fc32-49eb-9744-304b9c848afc.png` |
| `public/images/gallery/rose-prick.webp` | 27,316 bytes | `exec-2f3dcc69-b89a-480d-b9e2-cd3690bd6167.png` |

Shared generation prompt:

> Extract and reconstruct a standalone production product editorial photograph from the provided approved website mockup. Input image role: artistic/product reference ONLY. Create [subject below]. Match the approved mockup EXACTLY in lighting, tactile glass and silk materials, product color and bottle identity. Luminous extremely pale pearl ivory studio background #faf7f5, barely perceptible silky shadow and soft white window light caustics on the floor, sophisticated photoreal beauty campaign. Background at image edges must blend to near solid pearl white with no abrupt lines. This is ONE product asset, NOT a screenshot, NOT a website layout. Remove ALL interface text, prices, icons, hearts, dividers, buttons, numbers, 360 labels, headers and other products. Only the physical bottle's authentic brand label stays on the bottle. No new words elsewhere. Entire composition in frame. No border, no watermark. Highest quality physical glass reflections, realistic packaging, refined luxury. Do not change cap or perfume model.

Subject specifications:

- **Miss Dior:** the LARGE LEFT Miss Dior installation: the square clear blush Miss Dior Blooming Bouquet bottle with silver bow and crystal cap, on a sculptural thick transparent optical-glass ribbon pedestal, one large sculptural pleated pink silk petal behind, fine platinum elliptical orbit behind pedestal. Vertical 4:5 composition. Entire bottle, cap, silk petal and glass base fully visible. Bottle fills central 50% width and 55% height, pedestal lower 20%, no big empty border.
- **Sauvage:** ONLY the upper-right-grid Dior Sauvage black cylindrical bottle and its low clear round glass plinth, a single pale translucent sculptural petal behind base. Square composition. One black Dior Sauvage bottle, accurate silhouette and label, cap attached, entire product and pedestal visible, bottle fills central 40% width and 72% height.
- **Chanel:** ONLY the upper-right-grid Chanel Chance Eau Tendre round pink bottle on its low clear blush glass disc plinth, pale blush silk petal behind base. Square composition. One round pink CHANCE CHANEL EAU TENDRE bottle with clear squared cap, entire product and pedestal visible. Bottle fills central 60% width and 70% height.
- **Delina:** ONLY the lower-right-grid Parfums de Marly Delina Exclusif dusty pink embossed tall bottle, silver spherical cap and tiny pink tassel, on low clear optical-glass disc with a small translucent pleated petal behind. Square composition. One Delina Exclusif bottle, accurate shape, entire product and glass base visible, bottle fills central 43% width and 74% height.
- **Rose Prick:** ONLY the lower-right-grid Tom Ford Rose Prick rectangular dusty pink bottle with broad black rectangular cap and black front label on low clear glass disc, one restrained blush petal behind base. Square composition. One Tom Ford Rose Prick bottle, entire product and pedestal visible, bottle fills central 45% width and 70% height.

## Verification

`npm run build` succeeds. `php artisan test --compact`: 229 passed, 1,636 assertions. `ScentGalleryTest` covers live prices, uniqueness, active-only products, filtering/sorting isolation, single-product and empty-catalog fallback.

Local browser review scripts in ignored `storage/app/` check widths 320, 390, 768, 1024, 1440 and 1920, native disclosure, combined filters/sorting, comparison, product volume selection, artwork loading, guest wishlist navigation, reduced motion, keyboard Escape and no-JavaScript navigation. The mobile comparison bar is raised above floating chat controls.

Actual rendered screenshots: `storage/app/backups/atelier-review/gallery-final-desktop.png` and `gallery-mobile.png`. Deploy the source, config and `public/images/gallery/` together, rebuild Vite assets and refresh Laravel config/view caches. This work has not been pushed or deployed to soopi.site.
