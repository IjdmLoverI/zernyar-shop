# Theme

`themes/zernyar` is a block theme. It has no PHP templates: every page is block markup in
`templates/` and `parts/`, and the repeated sections are patterns in `patterns/`.

## Design tokens

`theme.json` holds the palette, the two font families, the type scale, the spacing scale, one
shadow and the corner radii (`settings.custom.radius`). Blocks and `assets/css/theme.css` use
them through the CSS variables WordPress generates, such as `--wp--preset--color--accent`.

| Token | Colour | Use |
| --- | --- | --- |
| `base` | `#F7F2EB` | page background |
| `base-2` | `#EFE6DA` | sections, notes |
| `surface` | `#FFFDF9` | cards |
| `contrast` | `#1F1712` | text, footer |
| `contrast-2` | `#5E5048` | secondary text (6.9:1 on `base`) |
| `accent` | `#A8481F` | buttons and links (5.2:1 on `base`, 5.8:1 with white text) |

Lora (headings) and Manrope (text) are stored in `assets/fonts` as variable WOFF2 files, split
into Cyrillic, Cyrillic Extended (it has the hryvnia sign ₴) and Latin subsets. `functions.php`
preloads the two files the first screen needs in the current language.

## Templates

| Template | What it shows |
| --- | --- |
| `front-page.html` | hero, benefits, featured coffee, brew guide, roastery story |
| `archive-product.html` | breadcrumbs, title, Product Filters on the left, Product Collection on the right |
| `single-product.html` | gallery, price, variation form, delivery note, details, related coffee |
| `page-cart.html`, `page-checkout.html` | the Cart and Checkout blocks; checkout has its own header without the menu |
| `page.html`, `index.html`, `404.html` | content pages, the blog fallback and the not-found page |

The catalog filters are built in `patterns/catalog.php`. Product Filters needs the ID of each
attribute, which differs between sites, so the pattern looks the IDs up by slug and leaves out
the filters for attributes that do not exist yet.

The product card (`patterns/product-card.php`) is shared by the home page, the catalog and the
related products. WordPress inlines patterns into templates before rendering, so the card's
blocks still get the product from the Product Collection around them.

## Translations

Strings in the theme are English and wrapped in `__()`; `languages/uk.po` translates them, so
the same templates can show either language without a second copy. `bash scripts/i18n.sh`
refreshes the template and compiles the `.mo` files.

## Development mode

`docker-compose.yml` sets `WP_DEVELOPMENT_MODE` to `theme`, so WordPress does not cache the list
of patterns. On a real site the cache is reset when the theme version changes, which is why a
release bumps `Version` in `style.css`.
