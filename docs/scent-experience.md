# Mood discovery and product details

- `config/storefront.php` holds the owner's confirmed seven-day return window and Zalo contact. The FAQ retains the conditions; product, drawer and cart link directly to them.
- `config/fragrance-editorial.php` records exact perfume variants, brand sources and the verification date. `FragranceEditorialService` guards name, brand, concentration and slug together. Only brand-published pyramids use top/heart/base tabs; key notes stay unranked. Unknown variants never receive generated notes.
- `MoodCollectionService` selects up to three verified, active perfumes per mood with a purchasable size. Its displayed price comes from `CartQuoteService`. Mood and occasion text is editorial advice, not a guaranteed sensory result.
- The home recommendations observe the flower's committed `data-mood`; rapid clicks cannot advance the products ahead of the completed colour change. Switching does not navigate or force a scroll.
- `product-quick-view.js` uses delegated links so AJAX gallery filtering keeps working. The native dialog preserves scroll and focus, supports keyboard and error recovery, and uses the existing authenticated cart POST. Prices and stock remain server-authoritative.
- Without JavaScript, the product links remain ordinary links and every published fragrance layer remains readable. Motion respects the site's switch and reduced-motion preferences.

Verification: `php artisan test --filter="ScentExperienceTest|ProductQuickViewTest|FragranceEditorialServiceTest|StorefrontThemeTest"` and `npm run build`. Browser checks cover 320, 390, 900/901 and 1440px, mood switching, keyboard tabs, quick-view variants/stock, failed requests, cart POST payload, login fallback and AJAX filtering.
