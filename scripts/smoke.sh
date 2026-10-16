#!/usr/bin/env bash
# Quick checks that the local store is up with the theme, plugins and demo catalog.
set -euo pipefail

cd "$(dirname "$0")/.."
set -a
# shellcheck disable=SC1091
. ./.env
set +a
url="http://localhost:${WP_PORT:-8090}"

wp() {
	MSYS_NO_PATHCONV=1 docker compose run --rm -T cli wp "$@"
}

wp plugin is-active woocommerce
wp plugin is-active ua-commerce-kit
wp plugin is-active zernyar-demo
test "$(wp theme list --status=active --field=name)" = zernyar

status="$(curl -s -o /dev/null -w '%{http_code}' "$url/")"
test "$status" = 200 || { echo "Home page answered $status"; exit 1; }

cart="$(curl -s "$url/wp-json/wc/store/v1/cart")"
[[ "$cart" == *'"items_count":0'* ]] || { echo "Store API cart: $cart"; exit 1; }

headers="$(curl -s -D - -o /dev/null "$url/wp-json/wc/store/v1/products?per_page=1")"
total="$(echo "$headers" | tr -d '\r' | awk -F': ' 'tolower($1) == "x-wp-total" { print $2 }')"
test "$total" = 19 || { echo "Store API lists $total products, expected 19"; exit 1; }

home="$(curl -s "$url/")"
[[ "$home" == *zernyar-hero* ]] || { echo "Home page has no hero"; exit 1; }

echo "Smoke checks passed."
