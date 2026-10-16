<?php
/**
 * Script of the branch picker for the Checkout block.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Registers build/checkout.js and passes it the settings it needs.
 */
final class BlocksIntegration implements IntegrationInterface {

	private const HANDLE = 'uack-np-checkout';

	/**
	 * Integration name; the script reads its data as `uack-nova-poshta_data`.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'uack-nova-poshta';
	}

	/**
	 * Registers the script and the stylesheet.
	 *
	 * @return void
	 */
	public function initialize() {
		$asset_file = UACK_DIR . '/build/checkout.asset.php';
		$src        = plugins_url( 'build/checkout.js', UACK_FILE );
		if ( ! is_readable( $asset_file ) || '' === $src ) {
			return;
		}
		$asset = require $asset_file;
		wp_register_script( self::HANDLE, $src, $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( self::HANDLE, 'ua-commerce-kit', UACK_DIR . '/languages' );
		if ( is_readable( UACK_DIR . '/build/style-checkout.css' ) ) {
			wp_enqueue_style( self::HANDLE, plugins_url( 'build/style-checkout.css', UACK_FILE ), array(), $asset['version'] );
		}
	}

	/**
	 * Scripts for the checkout page.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return wp_script_is( self::HANDLE, 'registered' ) ? array( self::HANDLE ) : array();
	}

	/**
	 * Nothing extra in the editor.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * Settings for the script.
	 *
	 * @return array<string, string>
	 */
	public function get_script_data() {
		return array(
			'restUrl'   => esc_url_raw( rest_url( Rest::NAMESPACE . '/np/' ) ),
			'methodId'  => ShippingMethod::ID,
			'namespace' => Checkout::STORE_NAMESPACE,
		);
	}
}
