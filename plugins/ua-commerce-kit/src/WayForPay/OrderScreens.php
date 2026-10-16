<?php
/**
 * Payment status on the thank-you page and in the admin.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * The thank-you page keeps asking while a payment is pending; the order screen
 * has an action that asks WayForPay once.
 */
final class OrderScreens {

	private const ACTION = 'uack_wfp_check';

	/**
	 * Hooks the screens in.
	 */
	public function register(): void {
		add_action( 'woocommerce_thankyou_' . Gateway::ID, array( $this, 'thank_you' ) );
		add_filter( 'woocommerce_order_actions', array( $this, 'add_action' ), 10, 2 );
		add_action( 'woocommerce_order_action_' . self::ACTION, array( $this, 'run_action' ) );
	}

	/**
	 * Note that waits for the payment, with a short poll.
	 *
	 * @param int $order_id Order.
	 */
	public function thank_you( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || ! $order->has_status( array( 'pending', 'on-hold' ) ) ) {
			return;
		}
		printf(
			'<div class="uack-wfp-status" role="status" data-url="%1$s" data-failed="%2$s"><p>%3$s</p></div>',
			esc_url( Endpoints::url( Endpoints::STATUS, $order ) ),
			esc_attr__( 'The payment did not go through. You can pay again from your account or contact us.', 'ua-commerce-kit' ),
			esc_html__( 'Waiting for WayForPay to confirm the payment. The page updates by itself.', 'ua-commerce-kit' )
		);
		wp_print_inline_script_tag(
			'(function(){var box=document.querySelector(".uack-wfp-status");if(!box)return;var tries=0;'
			. 'function ask(){fetch(box.dataset.url,{credentials:"same-origin"}).then(function(r){return r.json();}).then(function(d){'
			. 'if(d.state==="paid"){location.reload();return;}'
			. 'if(d.state==="failed"){box.querySelector("p").textContent=box.dataset.failed;return;}'
			. 'if(++tries<20){setTimeout(ask,6000);}}).catch(function(){if(++tries<20){setTimeout(ask,6000);}});}'
			. 'setTimeout(ask,1500);})();'
		);
	}

	/**
	 * Adds "Check the WayForPay payment" to the order actions.
	 *
	 * @param array<string, string> $actions Order actions.
	 * @param WC_Order|null         $order   Order.
	 * @return array<string, string>
	 */
	public function add_action( array $actions, $order = null ): array {
		if ( $order instanceof WC_Order && Gateway::ID === $order->get_payment_method() && $order->get_meta( Payments::REFERENCE ) ) {
			$actions[ self::ACTION ] = __( 'Check the WayForPay payment', 'ua-commerce-kit' );
		}
		return $actions;
	}

	/**
	 * Asks WayForPay and notes the answer.
	 *
	 * @param WC_Order $order Order.
	 */
	public function run_action( WC_Order $order ): void {
		$state = Payments::check( $order, Gateway::credentials(), __( 'manual check', 'ua-commerce-kit' ) );
		if ( Status::UNKNOWN === $state ) {
			$order->add_order_note( __( 'WayForPay has no payment for this order yet, or does not answer.', 'ua-commerce-kit' ) );
		}
	}
}
