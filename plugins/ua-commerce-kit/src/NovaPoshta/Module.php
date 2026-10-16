<?php
/**
 * Nova Poshta module.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the shipping method, the checkout, the REST routes and the order box.
 */
final class Module {

	/**
	 * Hooks the module in.
	 */
	public static function register(): void {
		add_filter(
			'woocommerce_shipping_methods',
			static function ( array $methods ): array {
				$methods[ ShippingMethod::ID ] = ShippingMethod::class;
				return $methods;
			}
		);
		( new Checkout() )->register();
		( new Rest() )->register();
		( new OrderDetails() )->register();
	}
}
