<?php
/**
 * Plugin Name:       Zernyar Demo
 * Plugin URI:        https://github.com/IjdmLoverI/zernyar-shop
 * Description:       Demo content for the Zernyar coffee store and settings for free hosting.
 * Version:           0.1.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            Kyrylo Buchynskyi
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zernyar-demo
 * Domain Path:       /languages
 *
 * @package ZernyarDemo
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ALTERNATE_WP_CRON' ) && 'yes' === get_option( 'zernyar_demo_alternate_cron', 'no' ) ) {
	define( 'ALTERNATE_WP_CRON', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core constant.
}

require __DIR__ . '/includes/class-zernyar-demo-admin-page.php';

add_action( 'plugins_loaded', array( 'Zernyar_Demo_Admin_Page', 'register' ) );
add_action(
	'init',
	static function (): void {
		load_plugin_textdomain( 'zernyar-demo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);
