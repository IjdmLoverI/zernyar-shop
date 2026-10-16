=== UA Commerce Kit ===
Tags: woocommerce, nova poshta, wayforpay, telegram, ukraine
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.4.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Nova Poshta delivery, WayForPay payments and Telegram order notifications for WooCommerce.

== Description ==

Three modules for a store that sells in Ukraine. Each one can be switched off in WooCommerce → Settings → UA Commerce Kit.

* **Nova Poshta**: delivery to a branch or a parcel locker, picked at checkout.
* **WayForPay**: card payments, with a test mode that uses the public test merchant.
* **Telegram**: a message to the store's chat for every new and every paid order.

Tools → Site Health shows whether this server can reach each service, which matters on free hosting.

= External services =

The plugin talks to these services from the store's server:

* [Nova Poshta API](https://developers.novaposhta.ua/) (`api.novaposhta.ua`): city and branch search and delivery prices. The search text typed at checkout is sent. [Privacy policy](https://novaposhta.ua/privacy_policy/).
* [WayForPay](https://wayforpay.com/) (`secure.wayforpay.com`, `api.wayforpay.com`): the shopper is sent to the payment page with the order total, products and contact details; the store then asks for the payment status. [Terms](https://wayforpay.com/en/terms).
* [Telegram Bot API](https://core.telegram.org/bots/api) (`api.telegram.org`): order notifications with the shopper's name, phone and delivery branch go to the chat set in the settings. [Terms](https://telegram.org/tos).

== Changelog ==

= 0.4.0 =
* Telegram messages for new orders and online payments, with a test message and a chat ID finder in the settings.

= 0.3.0 =
* WayForPay gateway with test mode on the public test merchant, a signed payment form and the Checkout block.
* Payment confirmed by CHECK_STATUS on return, by the service callback and from the thank-you page.
* Refunds from the order screen and a manual status check.

= 0.2.0 =
* Nova Poshta shipping method with the carrier tariff or a fixed cost and a free-delivery threshold.
* City and branch picker in the Checkout block; the branch becomes the shipping address.
* Server-side checks of the branch and the phone before the order is placed.

= 0.1.0 =
* Module switches.
* Site Health checks for the Nova Poshta, WayForPay and Telegram APIs.
