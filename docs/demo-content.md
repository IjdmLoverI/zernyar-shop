# Demo content

`plugins/zernyar-demo` fills an empty store with the Zernyar catalog. Everything comes from
`data/catalog.json` and `data/pages.php`.

## What is imported

- **Attributes:** roast, origin and processing (public, so the product cards can link to them),
  plus weight and grind, which make the variations.
- **Categories:** Coffee with three children (for espresso, for filter, for the cezve), drip bags,
  accessories and gift sets.
- **19 products:** 12 coffees with 8 variations each (250 g or 1 kg × four grinds), two drip bag
  boxes, a gift set and four accessories. Featured products appear on the home page.
- **Pages:** About, Delivery and payment, Contacts and Terms; the WooCommerce pages get Ukrainian
  titles.
- **Menu:** one navigation menu, used in the header and the footer.
- **Shipping:** a zone for Ukraine with the Nova Poshta method (tariff, 80 ₴ until a city is
  picked, free from 1500 ₴), Kyiv as the sender city, and a required phone at checkout.
- **Payments:** WayForPay in test mode first, cash on delivery second, and the Terms page as
  the terms and privacy page of the checkout.
- **Settings:** store title and city, cash on delivery, weight units, and
  `woocommerce_attribute_lookup_direct_updates`, so the filters see new products at once instead
  of waiting for a background job that free hosting may never run.

## English version

The store is Ukrainian; TranslatePress adds English under `/en/`. The import switches it on: Ukrainian
as the default language without a prefix, English as the second one, and no floating switcher,
because the theme has its own **UA / EN** links in the header (`blocks/language-switcher`).

Three kinds of text, three ways:

- **Theme and plugin texts** are English in the code and Ukrainian in the `.po` files.
  TranslatePress switches the locale on `/en/`, so they need no work.
- **Demo content in the database** (products, categories, attributes, pages, menu, payment and
  shipping titles) is translated in `data/translations-en.json`. The import writes it into the
  TranslatePress dictionary as reviewed translations. The keys are the texts as TranslatePress
  stores them, entities included (`&#8217;`, the breadcrumb `&#47;&nbsp;`), because it matches
  text nodes of the page.
- **Texts that never reach the page as text.** The product filters rebuild their labels from JSON,
  and the Checkout block joins attribute names and values into one line. On `/en/`,
  `Zernyar_Demo_English` swaps those names through WooCommerce filters (`get_term`,
  `woocommerce_attribute_label`, variation names, gateway and shipping titles), using the same
  file.

Nova Poshta city and branch names stay Ukrainian: the API has no English names.

## Running it

```bash
docker compose run --rm cli wp zernyar seed
```

On a host without WP-CLI, use **Tools → Zernyar demo → Import demo content**. The import runs in
parts of 15 seconds and keeps its place in an option, and the page submits the next part by
itself. Free hosts stop PHP after 30–60 seconds, and the first import there takes longer because
WordPress makes the thumbnails.

Every step looks things up by slug or SKU first, so running the import again updates the demo
items and never duplicates them.

## Product images

Coffee pouches, drip bag boxes and the gift set are not photos. `scripts/render-packs.mjs` draws
them from `catalog.json` in Chromium, with the theme's fonts and each coffee's colour, and saves
WebP files to `plugins/zernyar-demo/assets/products`:

```bash
npm install
npm run render:packs                  # all of them
npm run render:packs -- kenya-nyeri   # one
```

It needs ffmpeg (set `FFMPEG` if it is not on the PATH). `PW_CHANNEL=chrome` uses the installed
Chrome instead of Playwright's Chromium.

The three accessory photos are from Unsplash; the credits are in the plugin's `readme.txt`.
