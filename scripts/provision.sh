#!/bin/sh
# Runs inside the WP-CLI container (see setup.sh).
set -eu

WOOCOMMERCE_VERSION=11.2.1
TRANSLATEPRESS_VERSION=3.3.8

if ! wp core is-installed 2>/dev/null; then
	wp core install \
		--url="$SITE_URL" \
		--title="Зерняр" \
		--admin_user="$WP_ADMIN_USER" \
		--admin_password="$WP_ADMIN_PASSWORD" \
		--admin_email="$WP_ADMIN_EMAIL" \
		--skip-email
fi

wp language core install uk --activate
wp option update timezone_string Europe/Kyiv
wp option update date_format 'd.m.Y'
wp option update time_format 'H:i'
wp option update start_of_week 1
wp rewrite structure '/%postname%/'

install_plugin() {
	slug="$1"
	version="$2"
	if [ "$(wp plugin get "$slug" --field=version 2>/dev/null || true)" != "$version" ]; then
		wp plugin install "$slug" --version="$version" --force
	fi
}

install_plugin woocommerce "$WOOCOMMERCE_VERSION"
install_plugin translatepress-multilingual "$TRANSLATEPRESS_VERSION"
wp plugin activate woocommerce
wp language plugin install woocommerce uk

wp option update woocommerce_default_country UA
wp option update woocommerce_currency UAH
wp option update woocommerce_currency_pos right_space
wp option update woocommerce_price_thousand_sep ' '
wp option update woocommerce_price_decimal_sep ','
wp option update woocommerce_price_num_decimals 0
wp option update woocommerce_allowed_countries specific
wp option update woocommerce_specific_allowed_countries '["UA"]' --format=json
wp option update woocommerce_ship_to_countries ''
wp option update woocommerce_calc_taxes no
wp option update woocommerce_coming_soon no
wp option update woocommerce_allow_tracking no
wp option update woocommerce_show_marketplace_suggestions no
wp option update woocommerce_task_list_hidden yes
wp option update woocommerce_onboarding_profile '{"skipped":true}' --format=json

wp theme activate zernyar
wp plugin activate ua-commerce-kit zernyar-demo

wp plugin delete hello akismet 2>/dev/null || true
wp theme delete twentytwentythree twentytwentyfour 2>/dev/null || true
