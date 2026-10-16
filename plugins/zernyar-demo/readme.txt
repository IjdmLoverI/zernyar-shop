=== Zernyar Demo ===
Tags: woocommerce, demo
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Demo content for the Zernyar coffee store and settings for free hosting.

== Description ==

Everything lives in Tools → Zernyar demo.

* **Import demo content**: 19 products (12 coffees with weight and grind variations, drip bags, accessories and a gift set), their attributes and categories, the About, Delivery, Contacts and Terms pages and the menu. The import runs in parts of 15 seconds, so it finishes on hosts with a short request time limit. Running it again updates the demo items instead of duplicating them.
* **Scheduled tasks during page visits**: for hosts that block requests from the site to itself.

With WP-CLI: `wp zernyar seed`.

The catalog is in `data/catalog.json`. The coffee, drip bag and gift set images are drawn by `scripts/render-packs.mjs` from that file; the accessory photos are from Unsplash.

== Credits ==

Accessory photos from [Unsplash](https://unsplash.com) under the Unsplash License:

* Ceramic dripper: Devin Avery
* Hand grinder: Ashkan Forouzani
* Gooseneck kettle: Charlie Firth

== Changelog ==

= 0.2.0 =
* Demo catalog, pages and menu, imported from the admin or with WP-CLI.

= 0.1.0 =
* Setting to run scheduled tasks during page visits.
