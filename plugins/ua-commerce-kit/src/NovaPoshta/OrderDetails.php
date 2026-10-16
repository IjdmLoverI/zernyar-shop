<?php
/**
 * The branch on the order.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Stores the branch as order meta and as the shipping address, so emails,
 * the thank-you page and My account show it without extra templates.
 */
final class OrderDetails {

	private const META = '_uack_np';

	/**
	 * Hooks the admin box in.
	 */
	public function register(): void {
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'admin' ) );
	}

	/**
	 * Writes the branch into the order.
	 *
	 * @param WC_Order  $order     Order being placed.
	 * @param Selection $selection Checked pick.
	 * @param string    $phone     Normalized phone.
	 */
	public static function save( WC_Order $order, Selection $selection, string $phone ): void {
		$order->update_meta_data( self::META, $selection->to_array() );
		$order->set_shipping_country( 'UA' );
		$order->set_shipping_city( $selection->city );
		$order->set_shipping_address_1( $selection->warehouse );
		$order->set_shipping_address_2( '' );
		$order->set_shipping_postcode( $selection->postcode );
		$order->set_shipping_state( '' );
		$order->set_shipping_phone( $phone );
		if ( '' === $order->get_billing_city() ) {
			$order->set_billing_city( $selection->city );
		}
		$order->set_billing_phone( $phone );
	}

	/**
	 * The branch saved on an order.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function selection( WC_Order $order ): ?Selection {
		$saved = $order->get_meta( self::META );
		return is_array( $saved ) && ! empty( $saved['warehouse_ref'] ) ? Selection::from_array( $saved ) : null;
	}

	/**
	 * Human label of a branch type.
	 *
	 * @param string $type branch, locker or point.
	 */
	public static function type_label( string $type ): string {
		return match ( $type ) {
			'locker' => __( 'Parcel locker', 'ua-commerce-kit' ),
			'point'  => __( 'Pickup point', 'ua-commerce-kit' ),
			default  => __( 'Branch', 'ua-commerce-kit' ),
		};
	}

	/**
	 * Box under the shipping address on the order screen.
	 *
	 * @param WC_Order $order Order.
	 */
	public function admin( WC_Order $order ): void {
		$selection = self::selection( $order );
		if ( null === $selection ) {
			return;
		}
		printf(
			'<div class="uack-np-order"><h3>%1$s</h3><p><strong>%2$s №%3$s</strong>, %4$s<br>%5$s</p><p class="description">%6$s<br><code>%7$s</code></p></div>',
			esc_html__( 'Nova Poshta', 'ua-commerce-kit' ),
			esc_html( self::type_label( $selection->type ) ),
			esc_html( $selection->number ),
			esc_html( $selection->city ),
			esc_html( $selection->warehouse ),
			esc_html__( 'Branch reference for the waybill:', 'ua-commerce-kit' ),
			esc_html( $selection->warehouse_ref )
		);
	}
}
