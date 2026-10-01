# Soopi interior pages

Customer pages use `soopi-interior` on the shared store layout and load
`resources/css/store-interiors.css`. Homepage and admin styling remain separate.
The visual system uses pearl backgrounds, plum typography, rose accents, fine
borders, consistent controls, and restrained hover movement. Reduced-motion
preferences disable transitions.

- Product details reuse exact-slug artwork from `config/scent-gallery.php`.
  Buttons explicitly distinguish editorial artwork from the existing catalog
  photograph. The original product image and purchasing controls remain intact.
- Journal index, article cover, and home story thumbnails use the same artwork
  for the three matching slugs in `config/journal-art.php`. Other articles keep
  their saved images. Article text remains escaped; paragraph breaks and an
  estimated reading time improve readability.
- Finder and journal share `partials/interior-heading.blade.php`.
- Quiz, discovery box, compare, livestream, account, cart, checkout and order
  pages inherit the same control and surface styling without changing endpoints.

Validation: Vite build, existing Laravel tests, browser checks of product image
switching, volume/price, quantity, engraving field, finder, quiz, sample selection
and FAQ. Public pages checked at 1440, 768, 390 and 320px. Authenticated layouts
reviewed using HTML rendered by `StorefrontThemeTest` with temporary SQLite data,
without submitting purchases or modifying real customer records.
