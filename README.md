# Zernyar

A demo WooCommerce store for a coffee roastery in Ukraine, built as a portfolio project. The store is fictional: payments run in test mode and nothing is shipped.

The work lives in three parts:

| Part | What it is |
| --- | --- |
| [`plugins/ua-commerce-kit`](plugins/ua-commerce-kit) | A WooCommerce plugin with Nova Poshta delivery, WayForPay payments and Telegram order notifications. Nothing in it is specific to this store. |
| [`themes/zernyar`](themes/zernyar) | The block theme. |
| [`plugins/zernyar-demo`](plugins/zernyar-demo) | Demo content and settings for free hosting. |

## Run it locally

You need Docker and Bash (Git Bash on Windows).

```bash
bash scripts/setup.sh
```

The script creates `.env` with random local passwords, starts WordPress 7.1 on PHP 8.3 with MariaDB 10.4, installs WooCommerce and activates the theme and the plugins. The store opens at http://localhost:8090; the admin user and password are in `.env`.

More in [docs/development.md](docs/development.md).

## License

GPL-2.0-or-later, like WordPress and WooCommerce.
