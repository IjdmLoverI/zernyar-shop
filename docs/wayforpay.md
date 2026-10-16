# WayForPay

The gateway sends the shopper to the WayForPay payment page (card, Apple Pay, Google Pay) and
confirms the payment by asking WayForPay itself. It works with the public test merchant out of
the box and with a store's own merchant account.

```
Place order ─► /wc-api/uack_wayforpay_pay/   signed form, posts itself ─► secure.wayforpay.com/pay
                                                                                │
   ┌──── shopper comes back ◄── returnUrl: /wc-api/uack_wayforpay_return/?order&key
   │       └─ CHECK_STATUS ─► api.wayforpay.com ─► Payments::apply()
   │
   └──── WayForPay server ──► serviceUrl: /wc-api/uack_wayforpay_callback/
           └─ signature checked ─► Payments::apply() ─► signed "accept" reply
```

## Settings

**WooCommerce → Settings → Payments → WayForPay**

| Setting | |
| --- | --- |
| Test mode | on by default: the public test merchant `test_merch_n1` from the WayForPay docs, nothing is charged |
| Merchant account, secret key | the store's own merchant, used when test mode is off; or the `UACK_WFP_ACCOUNT` and `UACK_WFP_SECRET` constants |
| Title, description | shown at checkout; in test mode the Checkout block adds a line saying no money is taken |

The test merchant is shared by everyone who tests against it, so each payment attempt gets a
reference made of a hash of the site address, the order ID and the time: `zr01025e-164-1792145009`.
The latest reference is kept in the `_uack_wfp_reference` order meta.

## The payment form

`Purchase::fields()` builds the form in the order the WayForPay docs give and signs
`merchantAccount;merchantDomainName;orderReference;orderDate;amount;currency;names…;counts…;prices…`
with HMAC-MD5. The lines are the products, delivery and fees. When discounts or rounding make
them add up to anything but the order total, one line with the order reference and the total is
sent instead, so the payment page always shows the right amount.

The form is posted from a small page of its own, not from the order-pay page, so it does not
depend on the theme.

## Confirming the payment

Every result goes through `Payments::apply()`:

| WayForPay status | Order |
| --- | --- |
| `Approved`, amount and currency match | `payment_complete()` with the reference as the transaction ID, a note with the masked card and authorization code |
| `Approved`, amount differs | On hold, with a note to check the merchant account |
| `InProcessing`, `Pending`, `WaitingAuthComplete` | On hold |
| `Declined`, `Expired` | Failed, with the reason |
| reason code 1127, order not found | unchanged: the shopper left before paying |

An answer is used only if it comes from this merchant, its signature matches and its reference
is the order's latest attempt. A paid order is never changed again, so repeated callbacks do no
harm.

The result reaches the store in three ways:

- **The return URL.** It carries the order ID and key in the query string, and the store asks
  `CHECK_STATUS` instead of reading the POST body. A failed or abandoned payment sends the shopper
  to the order-pay page with a notice, and they can try again or pick cash on delivery.
- **The service URL.** WayForPay posts the result and retries for up to four days until the store
  replies with a signed `accept`.
- **The thank-you page.** While the order is pending or on hold, it asks
  `/wc-api/uack_wayforpay_status/` every six seconds, which checks with WayForPay at most every five
  seconds per order, and reloads when the payment is approved.

The order screen also has an **Check the WayForPay payment** action.

### On InfinityFree

Free hosting there answers non-browser requests with a JavaScript challenge, so the WayForPay
callback never arrives, and a cross-site POST back to the return URL can lose its body. The return
URL and the thank-you page check cover both: they run in the shopper's browser and ask WayForPay
from the server, which the host allows.

## Refunds

Refunds from the order screen call the `REFUND` API with the order reference. A refund needs a real
approved payment, so on the test merchant it only works after a payment made on the WayForPay page.

## Tests

`PurchaseTest` checks the order of the signed fields against the documented string and the
single-line fallback; `AnswerTest` checks signed, changed and foreign answers, the 1127 case and
the signed `accept` reply. The signature itself is checked against an answer the live API gave the
test merchant (`SignatureTest`).
