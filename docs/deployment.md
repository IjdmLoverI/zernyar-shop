# Deployment

The demo runs on free shared hosting ([InfinityFree](https://www.infinityfree.com)). Nothing in the code depends on it; on ordinary hosting the steps are the same and the workarounds below are not needed.

## Steps

1. Create the hosting account and a site, turn on SSL in the control panel and install WordPress with Softaculous.
2. In wp-admin, install WooCommerce, and TranslatePress for the English version, from Plugins → Add New.
3. Upload `ua-commerce-kit-<version>.zip` and `zernyar-demo-<version>.zip` in Plugins → Add New → Upload and activate them. Upload `zernyar-<version>.zip` in Appearance → Themes → Add New → Upload and activate it. The zips come from the [releases](https://github.com/IjdmLoverI/zernyar-shop/releases) or from `npm run zips`.
4. Open Tools → Zernyar demo:
   - turn on **Run scheduled tasks during page visits** and save;
   - press **Import demo content**. The import runs in parts of about 15 seconds and continues by itself until it is done.
5. Add the Telegram bot token and chat ID in WooCommerce → Settings → UA Commerce Kit → Telegram, and send a test message. On a host where `wp-config.php` can be edited, the `UACK_TELEGRAM_TOKEN` and `UACK_TELEGRAM_CHAT_ID` constants keep them out of the database.
6. Check Tools → Site Health: it sends a request to Nova Poshta, WayForPay and Telegram and reports any that fail.

## Free hosting limits

| Limit | What it means for the store | What the code does |
| --- | --- | --- |
| Requests that do not come from a browser get a JavaScript challenge | WayForPay's service callback never arrives; WP-Cron and Action Scheduler cannot call the site | The payment is confirmed with `CHECK_STATUS` when the shopper returns and from the thank-you page (see [WayForPay](wayforpay.md)). Scheduled tasks run during page visits through `ALTERNATE_WP_CRON`, switched on in the demo plugin |
| Files up to 10 MB | Uploads larger than that fail | `npm run zips` stops when a zip is bigger; the largest is about 1 MB |
| About 30,000 files and folders | WordPress, WooCommerce and TranslatePress take most of it | The local copy with all of them and this repository's code has about 12,000 |
| CPU limits per account | Long requests are stopped | The demo import works in short parts; Nova Poshta answers are cached for a day |
| At least one visit in 30 days | Otherwise the account is suspended | Nothing; the demo is opened from the portfolio |

## Updating

Upload the new zips over the old ones (WordPress offers to replace the plugin or theme) and run the import again if the catalog changed: it updates products by SKU and does not duplicate them.
