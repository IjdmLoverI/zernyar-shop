<?php
/**
 * WooCommerce API endpoints of the gateway.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

use WC_Order;
use WC_Order_Item_Product;

defined( 'ABSPATH' ) || exit;

/**
 * /wc-api/uack_wayforpay_pay/      posts the signed form to WayForPay
 * /wc-api/uack_wayforpay_return/   where WayForPay sends the shopper back
 * /wc-api/uack_wayforpay_callback/ the service URL WayForPay calls
 * /wc-api/uack_wayforpay_status/   asked by the thank-you page while a payment is pending
 *
 * The return URL carries the order and its key in the query string and asks
 * WayForPay for the status itself: hosts that challenge non-browser requests
 * may drop the POST body on the way back, and may never pass the callback on.
 */
final class Endpoints {

	public const PAY      = 'uack_wayforpay_pay';
	public const RETURN   = 'uack_wayforpay_return';
	public const CALLBACK = 'uack_wayforpay_callback';
	public const STATUS   = 'uack_wayforpay_status';

	/**
	 * Hooks the endpoints in.
	 */
	public function register(): void {
		add_action( 'woocommerce_api_' . self::PAY, array( $this, 'pay' ) );
		add_action( 'woocommerce_api_' . self::RETURN, array( $this, 'back' ) );
		add_action( 'woocommerce_api_' . self::CALLBACK, array( $this, 'callback' ) );
		add_action( 'woocommerce_api_' . self::STATUS, array( $this, 'status' ) );
	}

	/**
	 * Endpoint address for an order.
	 *
	 * @param string   $endpoint One of the constants.
	 * @param WC_Order $order    Order.
	 */
	public static function url( string $endpoint, WC_Order $order ): string {
		return add_query_arg(
			array(
				'order' => $order->get_id(),
				'key'   => $order->get_order_key(),
			),
			WC()->api_request_url( $endpoint )
		);
	}

	/**
	 * Prints a page that posts the signed form to WayForPay.
	 */
	public function pay(): void {
		$order = $this->order_from_request();
		if ( null === $order || ! $order->needs_payment() ) {
			wp_safe_redirect( null === $order ? wc_get_cart_url() : $order->get_checkout_order_received_url() );
			exit;
		}

		$credentials = Gateway::credentials();
		$fields      = Purchase::fields(
			$credentials,
			(string) wp_parse_url( home_url(), PHP_URL_HOST ),
			Payments::new_reference( $order ),
			(int) $order->get_date_created()?->getTimestamp(),
			(float) $order->get_total(),
			$order->get_currency(),
			$this->items( $order ),
			array(
				'clientFirstName' => $order->get_billing_first_name(),
				'clientLastName'  => $order->get_billing_last_name(),
				'clientEmail'     => $order->get_billing_email(),
				'clientPhone'     => $order->get_billing_phone(),
				'language'        => $order->get_meta( Payments::LANGUAGE ) ? (string) $order->get_meta( Payments::LANGUAGE ) : 'UA',
				'returnUrl'       => self::url( self::RETURN, $order ),
				'serviceUrl'      => WC()->api_request_url( self::CALLBACK ),
			)
		);

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		$this->render_redirect( $fields );
		exit;
	}

	/**
	 * Back from the payment page: checks the status and sends the shopper on.
	 */
	public function back(): void {
		$order = $this->order_from_request();
		if ( null === $order ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}

		$state               = Payments::check( $order, Gateway::credentials(), __( 'return from the payment page', 'ua-commerce-kit' ) );
		$left_without_paying = Status::UNKNOWN === $state && $order->needs_payment() && ! $order->has_status( 'on-hold' );
		if ( Status::FAILED === $state || $left_without_paying ) {
			wc_add_notice( __( 'The payment did not go through. You can try again or choose another payment method.', 'ua-commerce-kit' ), 'error' );
			wp_safe_redirect( $order->get_checkout_payment_url() );
			exit;
		}
		wp_safe_redirect( $order->get_checkout_order_received_url() );
		exit;
	}

	/**
	 * Service URL: WayForPay posts the result and expects a signed reply.
	 */
	public function callback(): void {
		$data   = $this->callback_body();
		$answer = Answer::verified( $data, Gateway::credentials() );
		$order  = null === $answer ? null : Payments::order_for( $answer->reference );
		if ( null === $answer || null === $order ) {
			status_header( 400 );
			exit;
		}

		Payments::apply( $order, $answer, __( 'WayForPay callback', 'ua-commerce-kit' ) );
		wp_send_json( Answer::accept( $answer->reference, Gateway::credentials(), time() ) );
	}

	/**
	 * Status for the thank-you page, asking WayForPay at most every five seconds per order.
	 */
	public function status(): void {
		$order = $this->order_from_request();
		if ( null === $order ) {
			wp_send_json( array( 'state' => Status::UNKNOWN ), 404 );
		}
		if ( $order->is_paid() ) {
			wp_send_json( array( 'state' => Status::PAID ) );
		}

		$lock = 'uack_wfp_check_' . $order->get_id();
		if ( get_transient( $lock ) ) {
			wp_send_json( array( 'state' => Status::PENDING ) );
		}
		set_transient( $lock, 1, 5 );
		wp_send_json( array( 'state' => Payments::check( $order, Gateway::credentials(), __( 'thank-you page', 'ua-commerce-kit' ) ) ) );
	}

	/**
	 * The order named in the query string, if its key matches and it is paid with WayForPay.
	 */
	private function order_from_request(): ?WC_Order {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- The order key authorizes these links.
		$id  = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
		$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		// phpcs:enable
		$order = $id ? wc_get_order( $id ) : null;
		if ( ! $order instanceof WC_Order || '' === $key || ! hash_equals( $order->get_order_key(), $key ) || Gateway::ID !== $order->get_payment_method() ) {
			return null;
		}
		return $order;
	}

	/**
	 * Lines of the order for the payment page: products, delivery and fees.
	 *
	 * @param WC_Order $order Order.
	 * @return list<array{name: string, price: float, count: int}>
	 */
	private function items( WC_Order $order ): array {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$count   = max( 1, (int) $item->get_quantity() );
			$items[] = array(
				'name'  => wp_strip_all_tags( $item->get_name() ),
				'price' => round( ( (float) $item->get_total() + (float) $item->get_total_tax() ) / $count, 2 ),
				'count' => $count,
			);
		}
		$shipping = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
		if ( $shipping > 0 ) {
			$items[] = array(
				'name'  => wp_strip_all_tags( $order->get_shipping_method() ),
				'price' => $shipping,
				'count' => 1,
			);
		}
		foreach ( $order->get_fees() as $fee ) {
			$items[] = array(
				'name'  => wp_strip_all_tags( $fee->get_name() ),
				'price' => (float) $fee->get_total() + (float) $fee->get_total_tax(),
				'count' => 1,
			);
		}
		return $items;
	}

	/**
	 * JSON body of the callback. WayForPay sends raw JSON; some setups receive it
	 * as the single key of a form post.
	 *
	 * @return array<string, mixed>
	 */
	private function callback_body(): array {
		$raw  = (string) file_get_contents( 'php://input' );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- The signature authorizes the callback.
			$keys = array_keys( $_POST );
			$data = json_decode( wp_unslash( (string) ( $keys[0] ?? '' ) ), true );
		}
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Minimal page with the form that submits itself.
	 *
	 * @param array<string, string|list<string>> $fields Signed fields.
	 */
	private function render_redirect( array $fields ): void {
		$inputs = '';
		foreach ( $fields as $name => $value ) {
			foreach ( (array) $value as $one ) {
				$inputs .= sprintf(
					'<input type="hidden" name="%s" value="%s">',
					esc_attr( is_array( $value ) ? $name . '[]' : $name ),
					esc_attr( (string) $one )
				);
			}
		}
		printf(
			'<!doctype html><html lang="%1$s"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex"><title>%2$s</title>'
			. '<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f7f2eb;color:#1f1712;font:16px/1.5 system-ui,sans-serif;text-align:center}'
			. 'button{margin-top:1rem;padding:.8rem 1.6rem;border:0;border-radius:999px;background:#a8481f;color:#fff;font:inherit;font-weight:700;cursor:pointer}</style></head>'
			. '<body><form id="uack-wfp" method="post" action="%3$s" accept-charset="utf-8">%4$s<p>%5$s</p><button type="submit">%6$s</button></form>'
			. '<script>document.getElementById("uack-wfp").submit();</script></body></html>',
			esc_attr( str_replace( '_', '-', determine_locale() ) ),
			esc_html__( 'Payment', 'ua-commerce-kit' ),
			esc_url( Purchase::URL ),
			$inputs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
			esc_html__( 'Taking you to the WayForPay payment page…', 'ua-commerce-kit' ),
			esc_html__( 'Go to payment', 'ua-commerce-kit' )
		);
	}
}
