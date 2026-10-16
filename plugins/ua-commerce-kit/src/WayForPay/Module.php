<?php
/**
 * WayForPay module.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the gateway, its endpoints, the order screens and the Checkout block support.
 */
final class Module {

	/**
	 * Hooks the module in.
	 */
	public static function register(): void {
		add_filter(
			'woocommerce_payment_gateways',
			static function ( array $gateways ): array {
				$gateways[] = Gateway::class;
				return $gateways;
			}
		);
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			static function ( PaymentMethodRegistry $registry ): void {
				$registry->register( new BlocksSupport() );
			}
		);
		( new Endpoints() )->register();
		( new OrderScreens() )->register();
	}
}
