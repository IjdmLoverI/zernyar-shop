#!/usr/bin/env bash
# Rebuilds the translation template and compiles the Ukrainian translations.
set -euo pipefail

cd "$(dirname "$0")/.."

wp() {
	MSYS_NO_PATHCONV=1 docker compose run --rm -T cli wp "$@"
}

for plugin in ua-commerce-kit zernyar-demo; do
	dir="/var/www/html/wp-content/plugins/$plugin"
	wp i18n make-pot "$dir" "$dir/languages/$plugin.pot" --exclude=tests,build,node_modules --headers='{"Report-Msgid-Bugs-To":"https://github.com/IjdmLoverI/zernyar-shop/issues"}'
	if ls "plugins/$plugin/languages/"*.po > /dev/null 2>&1; then
		wp i18n update-po "$dir/languages/$plugin.pot" "$dir/languages"
		wp i18n make-mo "$dir/languages"
	fi
done
