<?php
/**
 * WayForPay in the Checkout block.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the payment method script and passes it the title and description.
 */
final class BlocksSupport extends AbstractPaymentMethodType {

	private const HANDLE = 'uack-wfp-checkout';

	/**
	 * Payment method name; the script reads its data as `uack_wayforpay_data`.
	 *
	 * @var string
	 */
	protected $name = Gateway::ID;

	/**
	 * Loads the gateway settings.
	 *
	 * @return void
	 */
	public function initialize() {
		$settings       = get_option( Gateway::OPTION, array() );
		$this->settings = is_array( $settings ) ? $settings : array();
	}

	/**
	 * Whether the gateway is switched on.
	 *
	 * @return bool
	 */
	public function is_active() {
		return 'yes' === $this->get_setting( 'enabled', 'no' );
	}

	/**
	 * Script of the payment method.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		$asset_file = UACK_DIR . '/build/wayforpay.asset.php';
		$src        = plugins_url( 'build/wayforpay.js', UACK_FILE );
		if ( ! is_readable( $asset_file ) || '' === $src ) {
			return array();
		}
		$asset = require $asset_file;
		wp_register_script( self::HANDLE, $src, $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( self::HANDLE, 'ua-commerce-kit', UACK_DIR . '/languages' );
		return array( self::HANDLE );
	}

	/**
	 * Data for the script.
	 *
	 * @return array<string, mixed>
	 */
	public function get_payment_method_data() {
		return array(
			'title'       => (string) $this->get_setting( 'title' ),
			'description' => (string) $this->get_setting( 'description' ),
			'test'        => Gateway::credentials()->is_test(),
			'supports'    => array( 'products' ),
		);
	}
}
