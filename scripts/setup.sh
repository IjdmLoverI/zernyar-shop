#!/usr/bin/env bash
# Starts the local store and installs WordPress, WooCommerce, the theme and the plugins.
# Safe to run again: every step checks what is already there.
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
	cp .env.example .env
	for key in DB_PASSWORD DB_ROOT_PASSWORD WP_ADMIN_PASSWORD; do
		value="$(openssl rand -hex 12)"
		sed -i "s/^${key}=.*/${key}=${value}/" .env
	done
	echo "Created .env with random local passwords."
fi

set -a
# shellcheck disable=SC1091
. ./.env
set +a

docker compose up -d --wait db wordpress

url="http://localhost:${WP_PORT:-8090}"
for _ in $(seq 1 60); do
	if curl -s -o /dev/null "$url/wp-login.php"; then
		break
	fi
	sleep 2
done

MSYS_NO_PATHCONV=1 docker compose run --rm -T \
	-e SITE_URL="$url" \
	-e WP_ADMIN_USER="$WP_ADMIN_USER" \
	-e WP_ADMIN_PASSWORD="$WP_ADMIN_PASSWORD" \
	-e WP_ADMIN_EMAIL="$WP_ADMIN_EMAIL" \
	cli sh /scripts/provision.sh

echo "Store: $url  Admin: $url/wp-admin (user and password in .env)"
