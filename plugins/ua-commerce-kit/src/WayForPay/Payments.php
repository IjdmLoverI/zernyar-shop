<?php
/**
 * Applies WayForPay results to orders.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * One place that changes the order after a payment, whichever way the result arrived:
 * the return from the payment page, the service callback, the thank-you page or the admin.
 */
final class Payments {

	public const REFERENCE = '_uack_wfp_reference';
	public const LANGUAGE  = '_uack_wfp_language';

	/**
	 * A new reference for a payment attempt, unique across stores on the shared test merchant.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function new_reference( WC_Order $order ): string {
		$site      = substr( md5( (string) home_url() ), 0, 6 );
		$reference = sprintf( 'zr%s-%d-%d', $site, $order->get_id(), time() );
		$order->update_meta_data( self::REFERENCE, $reference );
		$order->save();
		return $reference;
	}

	/**
	 * The order a reference was made for, if it is still the order's latest attempt.
	 *
	 * @param string $reference Order reference from WayForPay.
	 */
	public static function order_for( string $reference ): ?WC_Order {
		if ( 1 !== preg_match( '/-(\d+)-\d+$/', $reference, $match ) ) {
			return null;
		}
		$order = wc_get_order( (int) $match[1] );
		return $order instanceof WC_Order && $order->get_meta( self::REFERENCE ) === $reference ? $order : null;
	}

	/**
	 * Asks WayForPay for the latest attempt and applies the answer.
	 *
	 * @param WC_Order    $order       Order.
	 * @param Credentials $credentials Merchant.
	 * @param string      $source      Where the check comes from, for the order note.
	 * @return string Status constant.
	 */
	public static function check( WC_Order $order, Credentials $credentials, string $source ): string {
		$reference = (string) $order->get_meta( self::REFERENCE );
		if ( '' === $reference ) {
			return Status::UNKNOWN;
		}
		$answer = ( new Api( $credentials ) )->check_status( $reference );
		return null === $answer ? Status::UNKNOWN : self::apply( $order, $answer, $source );
	}

	/**
	 * Changes the order for a checked answer.
	 *
	 * @param WC_Order $order  Order.
	 * @param Answer   $answer Signed answer.
	 * @param string   $source Where the answer came from, for the order note.
	 * @return string Status constant.
	 */
	public static function apply( WC_Order $order, Answer $answer, string $source ): string {
		if ( $answer->reference !== $order->get_meta( self::REFERENCE ) || $answer->not_found() ) {
			return Status::UNKNOWN;
		}

		switch ( $answer->state ) {
			case Status::PAID:
				if ( $order->is_paid() ) {
					return Status::PAID;
				}
				if ( ! $answer->matches( (float) $order->get_total(), $order->get_currency() ) ) {
					$order->update_status(
						'on-hold',
						sprintf(
							/* translators: 1: amount, 2: currency. */
							__( 'WayForPay reports a payment of %1$s %2$s, which does not match the order. Check it in the merchant account.', 'ua-commerce-kit' ),
							Purchase::money( $answer->amount ),
							$answer->currency
						)
					);
					return Status::PENDING;
				}
				$order->add_order_note(
					sprintf(
						/* translators: 1: where the result came from, 2: masked card number, 3: bank authorization code. */
						__( 'WayForPay approved the payment (%1$s): card %2$s, authorization code %3$s.', 'ua-commerce-kit' ),
						$source,
						$answer->card,
						$answer->auth_code
					)
				);
				$order->payment_complete( $answer->reference );
				return Status::PAID;

			case Status::PENDING:
				if ( $order->has_status( 'pending' ) ) {
					$order->update_status( 'on-hold', __( 'WayForPay is still processing the payment.', 'ua-commerce-kit' ) );
				}
				return Status::PENDING;

			case Status::FAILED:
				if ( ! $order->is_paid() && ! $order->has_status( 'failed' ) ) {
					/* translators: %s: reason from WayForPay. */
					$order->update_status( 'failed', sprintf( __( 'WayForPay did not take the payment: %s.', 'ua-commerce-kit' ), $answer->reason ) );
				}
				return Status::FAILED;

			default:
				return $answer->state;
		}//end switch
	}
}
