<?php
/**
 * Plugin Name:          UA Commerce Kit
 * Plugin URI:           https://github.com/IjdmLoverI/zernyar-shop
 * Description:          Nova Poshta delivery, WayForPay payments and Telegram order notifications for WooCommerce.
 * Version:              1.0.0
 * Requires at least:    7.0
 * Requires PHP:         8.1
 * Requires Plugins:     woocommerce
 * Author:               Kyrylo Buchynskyi
 * Author URI:           https://github.com/IjdmLoverI
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          ua-commerce-kit
 * Domain Path:          /languages
 * WC requires at least: 10.0
 * WC tested up to:      11.2
 *
 * @package UaCommerceKit
 */

defined( 'ABSPATH' ) || exit;

define( 'UACK_VERSION', '1.0.0' );
define( 'UACK_FILE', __FILE__ );
define( 'UACK_DIR', __DIR__ );

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'UaCommerceKit\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}
		$path = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action( 'plugins_loaded', array( \UaCommerceKit\Plugin::class, 'boot' ) );
