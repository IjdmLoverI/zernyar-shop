<?php
/**
 * Wires the modules into WordPress.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit;

use UaCommerceKit\Admin\SettingsPage;
use UaCommerceKit\Health\SiteHealth;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin entry point.
 */
final class Plugin {

	/**
	 * Hooks everything up once WooCommerce is loaded.
	 */
	public static function boot(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'init', array( self::class, 'load_textdomain' ) );
		add_filter( 'woocommerce_get_settings_pages', array( self::class, 'add_settings_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( UACK_FILE ), array( self::class, 'add_action_links' ) );

		( new SiteHealth() )->register();
	}

	/**
	 * Loads translations from the plugin's languages folder.
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain( 'ua-commerce-kit', false, dirname( plugin_basename( UACK_FILE ) ) . '/languages' );
	}

	/**
	 * Adds the plugin's tab to WooCommerce → Settings.
	 *
	 * @param array<int, \WC_Settings_Page> $pages Settings pages.
	 * @return array<int, \WC_Settings_Page>
	 */
	public static function add_settings_page( array $pages ): array {
		$pages[] = new SettingsPage();
		return $pages;
	}

	/**
	 * Links to the settings from the plugins list.
	 *
	 * @param array<string|int, string> $links Action links.
	 * @return array<string|int, string>
	 */
	public static function add_action_links( array $links ): array {
		$url = admin_url( 'admin.php?page=wc-settings&tab=' . SettingsPage::ID );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'ua-commerce-kit' ) . '</a>' );
		return $links;
	}

	/**
	 * Whether a module is switched on in the settings.
	 *
	 * @param string $module Module key: nova_poshta, wayforpay or telegram.
	 */
	public static function module_enabled( string $module ): bool {
		return 'no' !== get_option( 'uack_' . $module . '_enabled', 'yes' );
	}
}
