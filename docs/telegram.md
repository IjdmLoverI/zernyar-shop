# Telegram

The store messages its owner about every new order and every online payment.

```
🛒 Нове замовлення №158 · 470 ₴

• Колумбія Уїла - 250 г, У зернах × 1

👤 Олена Коваленко, +380671234567
📦 Відділення №3, Біла Церква
💳 Накладений платіж

Відкрити замовлення
```

## Setting it up

1. Create a bot with [@BotFather](https://t.me/BotFather) and copy its token.
2. Paste the token in **WooCommerce → Settings → UA Commerce Kit → Telegram** and save.
3. Write anything to the bot. The same screen then lists the chats that wrote to it lately; copy
   your chat ID into the field and save. A group works too, once the bot is in it.
4. **Send a test message** checks the whole chain.

The token and chat ID can also come from `UACK_TELEGRAM_TOKEN` and `UACK_TELEGRAM_CHAT_ID` in
`wp-config.php`; the local Docker site reads them from `.env`, so they never reach the database.

## When messages go out

| Event | Hook |
| --- | --- |
| Order placed in the Checkout block | `woocommerce_store_api_checkout_order_processed` |
| Order placed with the classic checkout | `woocommerce_checkout_order_processed` |
| Online payment confirmed | `woocommerce_payment_complete` |

A new order paid by card says that it is waiting for payment; the payment message follows when
WayForPay confirms it. Each event is sent once per order (the `_uack_tg_sent` meta), however many
times the hook fires. Both events can be switched off.

## Sending

`Bot::send()` calls `sendMessage` with `parse_mode=HTML` and a five-second timeout, while the
checkout request runs. A background job would not slow the checkout down, but free hosting may
never run one, and five seconds is the worst case of a request that usually takes a few hundred
milliseconds. A failure does not stop the order: it is written to the order notes and the
WooCommerce log.

Everything the shopper typed is escaped before it goes into the HTML (`Message::escape()`), so a
name like `<a href=…>` arrives as text. Messages longer than Telegram's 4096 characters drop item
lines from the middle and keep the totals, the customer and the link.
