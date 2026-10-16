<?php
/**
 * Nova Poshta shipping method.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

use WC_Shipping_Method;

defined( 'ABSPATH' ) || exit;

/**
 * Delivery to a branch, parcel locker or pickup point picked at checkout.
 */
final class ShippingMethod extends WC_Shipping_Method {

	public const ID = 'uack_nova_poshta';

	/**
	 * Sets the method up for a shipping zone.
	 *
	 * @param int $instance_id Shipping zone method instance.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = self::ID;
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Nova Poshta', 'ua-commerce-kit' );
		$this->method_description = __( 'Delivery to a Nova Poshta branch, parcel locker or pickup point that the shopper picks at checkout.', 'ua-commerce-kit' );
		$this->supports           = array( 'shipping-zones', 'instance-settings', 'instance-settings-modal' );
		$this->init();
	}

	/**
	 * Settings of the instance.
	 */
	private function init(): void {
		$this->instance_form_fields = array(
			'title'     => array(
				'title'   => __( 'Title', 'ua-commerce-kit' ),
				'type'    => 'text',
				'default' => __( 'Nova Poshta: branch or parcel locker', 'ua-commerce-kit' ),
			),
			'cost_mode' => array(
				'title'   => __( 'Cost', 'ua-commerce-kit' ),
				'type'    => 'select',
				'default' => Cost::CARRIER,
				'options' => array(
					Cost::CARRIER => __( 'Nova Poshta tariff for the picked city', 'ua-commerce-kit' ),
					Cost::FIXED   => __( 'Fixed cost', 'ua-commerce-kit' ),
				),
			),
			'cost'      => array(
				'title'       => __( 'Fixed cost', 'ua-commerce-kit' ),
				'type'        => 'price',
				'default'     => '80',
				'description' => __( 'Also used with the tariff until the shopper picks a city or when Nova Poshta does not answer.', 'ua-commerce-kit' ),
				'desc_tip'    => true,
			),
			'free_from' => array(
				'title'       => __( 'Free from', 'ua-commerce-kit' ),
				'type'        => 'price',
				'default'     => '',
				'description' => __( 'Order total from which delivery is free. Leave empty to always charge.', 'ua-commerce-kit' ),
				'desc_tip'    => true,
			),
		);
		$this->title                = (string) $this->get_option( 'title', __( 'Nova Poshta: branch or parcel locker', 'ua-commerce-kit' ) );

		add_action(
			'woocommerce_update_options_shipping_' . $this->id,
			function (): void {
				$this->process_admin_options();
			}
		);
	}

	/**
	 * Adds the rate for a package.
	 *
	 * @param array<string, mixed> $package Package with the Nova Poshta city added by the checkout.
	 */
	public function calculate_shipping( $package = array() ): void {
		$mode     = (string) $this->get_option( 'cost_mode', Cost::CARRIER );
		$subtotal = (float) ( $package['contents_cost'] ?? 0 );
		$city_ref = (string) ( $package['uack_np_city'] ?? '' );
		$carrier  = null;

		if ( Cost::CARRIER === $mode && Selection::is_ref( $city_ref ) ) {
			$sender = Settings::sender_city_ref();
			if ( '' !== $sender ) {
				$carrier = Client::from_settings()->price( $sender, $city_ref, Cost::weight( $this->items( $package ) ), $subtotal );
			}
		}

		$this->add_rate(
			array(
				'label'     => $this->title,
				'cost'      => Cost::calculate( $mode, $subtotal, (float) $this->get_option( 'cost', 0 ), (float) $this->get_option( 'free_from', 0 ), $carrier ),
				'package'   => $package,
				'meta_data' => array( 'uack_np_tariff' => null === $carrier ? '' : (string) $carrier ),
			)
		);
	}

	/**
	 * Weight and quantity of each line.
	 *
	 * @param array<string, mixed> $package Package.
	 * @return list<array{weight: float, quantity: int}>
	 */
	private function items( array $package ): array {
		$items = array();
		foreach ( (array) ( $package['contents'] ?? array() ) as $line ) {
			$product = $line['data'] ?? null;
			$items[] = array(
				'weight'   => $product instanceof \WC_Product ? (float) wc_get_weight( (float) $product->get_weight(), 'kg' ) : 0.0,
				'quantity' => (int) ( $line['quantity'] ?? 1 ),
			);
		}
		return $items;
	}
}
