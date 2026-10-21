# Testing

| Layer | Tool | Where | What it covers |
| --- | --- | --- | --- |
| Unit | PHPUnit + Brain Monkey | `plugins/ua-commerce-kit/tests/unit` | WayForPay signatures and answers, Nova Poshta parsing and delivery cost, phone numbers, Telegram text, Site Health probes |
| Static | PHPCS (WPCS 3), PHPStan level 8, ESLint, Stylelint, Plugin Check | `composer lint`, `composer analyse`, `npm run lint:js`, `npm run lint:css`, `scripts/plugin-check.sh` | Coding standards, types, escaping and sanitizing |
| End to end | Playwright + axe | `tests/e2e` | The store in a browser, from the catalog to a paid order |

How to run the first two is in [Development](development.md#checks).

## End-to-end tests

The tests run against the Docker store, so set it up first:

```bash
npm ci
npm run build
bash scripts/setup.sh
npx playwright install chromium
npm run test:e2e
```

To use an installed Chrome instead of downloading Playwright's Chromium, set `PW_CHANNEL=chrome`. `E2E_BASE_URL` points the tests at another address.

| File | Scenarios |
| --- | --- |
| `catalog.spec.mjs` | Home page, the roast filter, a variation price, adding to the cart |
| `nova-poshta.spec.mjs` | Cash on delivery to a branch with the order, the phone and the Telegram message checked; a parcel locker found by its number; the combobox from the keyboard; place order without a branch; the server refusing a missing branch, a branch from another city and a foreign phone |
| `wayforpay.spec.mjs` | The signature of the payment form; return from WayForPay with an approved, declined and pending payment; the service callback, tampered and signed |
| `english.spec.mjs` | The language switcher, English filters, an English checkout that opens WayForPay in English |
| `accessibility.spec.mjs` | axe (WCAG 2.1 A and AA, serious and critical issues) on the home page, catalog, a product, the delivery page, the English home page and the checkout with the Nova Poshta picker. WooCommerce's closed mini-cart drawer is left out: its markup comes from core |

### Fixtures instead of the real APIs

`docker/mu-plugins/e2e-fixtures.php` answers Nova Poshta, WayForPay and Telegram from fixtures through `pre_http_request`. It works only when:

- the site runs with `WP_ENVIRONMENT_TYPE=local`, and
- the request carries an `X-Uack-E2E` header, which Playwright adds to every request.

Opening the local store in a normal browser therefore still reaches the real services. With the header, the plugin also:

- turns off the Nova Poshta cache and the search rate limit;
- gives Telegram a fake token and keeps the messages in an option instead of sending them;
- adds two read-only routes for the tests: `uack-e2e/v1/order/{id}` with an order's status, payment and delivery, and `uack-e2e/v1/telegram` with the captured messages.

WayForPay's `CHECK_STATUS` answer follows a second header, `X-Uack-E2E-WayForPay` (`approved`, `pending` or `declined`), and is signed with the public test key, so the plugin checks it the same way it checks a real one. The WayForPay payment page itself is stubbed with `page.route()`: the tests read the form the store posts to it, and return to the store the way WayForPay does.

`docker/mu-plugins/dev-mail.php` keeps the last 20 emails in the `zernyar_dev_mail` option instead of sending them.

### What stays manual

The fixtures cannot show that the real services still answer the way the plugin expects. Before a release, check on the local store without the header, or on the live demo:

- a city and a branch from the real Nova Poshta API;
- a payment on the WayForPay test page, and a refund from the order screen;
- a message from a real bot (see [Telegram](telegram.md)).

Site Health (Tools → Site Health) runs a request to each of the three services.

## CI

`.github/workflows/ci.yml` has three jobs:

- `php`: PHPCS, PHPStan and PHPUnit;
- `js`: ESLint, Stylelint and the build;
- `site`: builds the store in Docker and runs the smoke test, Plugin Check and the end-to-end tests. When a test fails, the Playwright report with traces is uploaded as the `playwright-report` artifact.
