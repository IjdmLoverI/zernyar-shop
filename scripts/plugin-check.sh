#!/usr/bin/env bash
# Runs the official Plugin Check on both plugins and fails on errors.
set -euo pipefail

cd "$(dirname "$0")/.."

PLUGIN_CHECK_VERSION=2.1.0

wp() {
	MSYS_NO_PATHCONV=1 docker compose run --rm -T cli wp "$@"
}

if [ "$(wp plugin get plugin-check --field=version 2>/dev/null || true)" != "$PLUGIN_CHECK_VERSION" ]; then
	wp plugin install plugin-check --version="$PLUGIN_CHECK_VERSION" --force
fi
wp plugin activate plugin-check

failed=0
for plugin in ua-commerce-kit zernyar-demo; do
	echo "== $plugin"
	report="$(wp plugin check "$plugin" --format=csv --fields=line,column,type,code,message --exclude-directories=tests 2>&1 || true)"
	echo "$report"
	if echo "$report" | grep -q ',ERROR,'; then
		failed=1
	fi
done

wp plugin deactivate plugin-check
exit "$failed"
