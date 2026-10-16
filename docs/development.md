# Development

## Containers

`docker-compose.yml` matches the free hosting the demo runs on: PHP 8.3 and MariaDB 10.4.

| Service | Image | Use |
| --- | --- | --- |
| `db` | `mariadb:10.4` | Database |
| `wordpress` | `wordpress:7.1.3-php8.3-apache` | The store on http://localhost:8090 |
| `cli` | `wordpress:cli-2.12-php8.3` | WP-CLI, profile `tools` |
| `tools` | `docker/tools` (PHP 8.3 + Composer) | Linters and unit tests, profile `tools` |

The plugins and the theme are mounted from this repository, so changes show up without a rebuild. WordPress itself lives in the `wp` volume.

```bash
bash scripts/setup.sh                    # start and install; safe to run again
docker compose run --rm cli wp <command> # WP-CLI
docker compose down                      # stop
docker compose down -v                   # stop and delete the database and WordPress files
```

On Windows Git Bash, set `MSYS_NO_PATHCONV=1` before WP-CLI commands with paths in them; the scripts do this already.

## Checks

PHP is not needed on the host; Composer runs in the `tools` container.

```bash
docker compose run --rm tools composer install
docker compose run --rm tools composer lint      # WordPress Coding Standards
docker compose run --rm tools composer analyse   # PHPStan, level 8, with WordPress and WooCommerce stubs
docker compose run --rm tools composer test      # PHPUnit unit tests
bash scripts/smoke.sh                            # the store is up, the plugins and theme are active
bash scripts/plugin-check.sh                     # the official Plugin Check
```

The unit tests run without WordPress. [Brain Monkey](https://giacomo.gitbook.io/brain-monkey) stands in for the WordPress functions, so the logic is kept in classes that take plain values, such as `HttpResult`, instead of calling the HTTP API themselves.

CI in `.github/workflows/ci.yml` runs the same steps on every pull request.

## Telegram on the local site

Put a bot token and a chat ID into `UACK_TELEGRAM_TOKEN` and `UACK_TELEGRAM_CHAT_ID` in `.env` and run `docker compose up -d`. The site reads them as constants, so they never land in the database.
