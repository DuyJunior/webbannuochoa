# Soopi Moments — 2026-10-01

Replaces the lower homepage collection cards, gift banner, repeated offer banner
and quiz/sample banners with three functional experiences. The header, flower
hero, product gallery and visible videos remain intact.

- Three moment selectors change the featured scene, copy, product and finder
  link. Curated products are matched by slug against active homepage products;
  unavailable products do not receive a purchase link. All scenes and links
  remain available without JavaScript.
- Gift finder submits recipient, occasion and maximum price to the existing
  scent finder. The server validates and applies the price ceiling before
  ranking recommendations. The note preview is local only, explicitly labelled
  as not included with an order.
- Sample tray supports three/five samples at the existing box prices. Native
  checkbox selections are passed through GET to the existing discovery builder.
  The server validates inputs, ignores unknown/inactive products and bounds the
  selection to the package size. No cart/checkout action is automatic.
- Existing local artwork was reused; no new image service/network dependency.
- Responsive layout and reduced-motion support are scoped to the new sections.

## Sampling atelier update

`partials/home-sampling.blade.php` now pairs a plum sample tray with a responsive
four/two-column catalog. Native 3/5-size radios and checkboxes remain functional
without JavaScript; the enhanced tray preserves the order in which samples were
chosen and passes that order into the builder. Product details use the existing
quick-view drawer. Sample availability uses `DiscoveryBoxService::remaining`,
including 5ml inventory and samples already allocated in the customer's cart.
Unavailable entries remain visible with an explanation; catalog inventory is
never synthesized. The full builder and cart validate availability again.

`atelier-typewriter.js` enhances the sampling heading once on viewport entry.
The full phrase is present in the initial HTML and accessible to screen readers;
Vietnamese grapheme clusters are revealed decoratively without changing the
heading's dimensions. Reduced motion and the site motion switch show static text.
`home-sampling.css` controls the tray, selection depth and responsive layout.

Files: `partials/home-moments.blade.php`, `home-moments.css`,
`home-moments.js`, `StoreExperienceController`, finder and discovery-box views.

Validation: production build passed; 14 existing related feature tests and two
new HomeAtelier tests passed. Chrome checked scene switching, note preview,
sample limits, package pricing and transfer of five selected samples to the
builder, plus overflow at 1440/1024/768/390/320 px. No JavaScript errors observed.
Screenshots are local QA files under `storage/app/backups/journal-review/`.
No production deploy or Git push performed.
