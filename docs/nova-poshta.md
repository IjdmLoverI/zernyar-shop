# Nova Poshta

The module adds a shipping method and a picker to the Checkout block. The shopper types a city,
picks a branch, a parcel locker or a pickup point, and the order gets that branch as its shipping
address.

```
Checkout block ── GET /wp-json/ua-commerce-kit/v1/np/cities, /np/warehouses ──► Nova Poshta API (cached a day)
      │
      └─ extensionCartUpdate({ namespace: 'ua-commerce-kit-np', data }) ──► session ──► shipping rate for the city
                                                                              │
Place order ── Store API checkout ── woocommerce_store_api_checkout_update_order_from_request
                                          checks the branch and the phone, writes them into the order
```

## API key

City and branch lookups and `getDocumentPrice` work without a key, so the demo has none. A key
from the Nova Poshta business account goes into **WooCommerce → Settings → UA Commerce Kit →
Nova Poshta** or the `UACK_NP_API_KEY` constant; the module sends it with every call.

## Shipping method

Add **Nova Poshta** to a shipping zone. Each instance has:

| Setting | |
| --- | --- |
| Title | shown at checkout |
| Cost | the Nova Poshta tariff for the picked city, or a fixed cost |
| Fixed cost | also used with the tariff until a city is picked or when the API fails |
| Free from | order total from which delivery costs nothing |

The tariff is `InternetDocument.getDocumentPrice` from the sender city (a store setting) to the
picked city, branch to branch, for the weight of the cart (0.3 kg for a product without a weight)
and its value. `Cost::calculate()` holds the rules.

WooCommerce caches shipping rates per package. The module adds the picked city to each package
(`woocommerce_cart_shipping_packages`), so the rate is recalculated when the city changes.

## Checkout

`assets/src/checkout` is built into `build/checkout.js` with `npm run build`. It fills the
`ExperimentalOrderShippingPackages` slot of the Checkout block and shows two comboboxes, city and
branch, only while the Nova Poshta rate is selected.

- The comboboxes follow the ARIA combobox pattern: arrow keys, Enter, Escape, results announced in a
  live region.
- A branch search of digits looks for that branch number first; anything else searches the
  address.
- Lookups go to the store's own REST routes, not to Nova Poshta from the browser: the answers are
  cached for a day, the routes allow 120 searches per visitor in ten minutes, and free hosting
  only lets same-site requests through anyway.
- Until a branch is picked, the field registers a hidden validation error in
  `wc/store/validation`, so Place order shows it and moves focus there instead of sending the
  order.

For Ukraine the street, postcode, region and company fields are hidden through
`woocommerce_get_country_locale`; the branch replaces them. A setting turns this off for stores
that also deliver to an address.

## What the server checks

The browser sends references; the server looks the branch up by its reference and does not trust
the names it was sent.

| Request | Refused when | Code |
| --- | --- | --- |
| `cart/extensions` | the branch is in another city | `uack_np_wrong_city` |
| checkout | Nova Poshta is the method and no branch is picked | `uack_np_no_branch` |
| checkout | the branch moved to another city since it was picked | `uack_np_wrong_city` |
| checkout | the phone is not a Ukrainian number | `uack_np_phone` |

The phone is normalized to `+380XXXXXXXXX` (`Phone::normalize()`), because Nova Poshta sends the
parcel code to it. When the API cannot be reached at checkout, the order goes through with the
branch saved in the session.

## The order

`OrderDetails::save()` writes the branch twice:

- into the `_uack_np` meta (city and branch references, number, type, postcode), for a waybill
  later;
- into the shipping address: the branch description as the first line, the city and the postcode.

Emails, the thank-you page and My account then show the branch without template changes. The
order screen shows a Nova Poshta box with the branch reference under the shipping address.

The classic checkout shortcode is not supported.
