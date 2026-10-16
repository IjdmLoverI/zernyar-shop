<?php
/**
 * WayForPay payment gateway.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

use WC_Order;
use WC_Payment_Gateway;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Sends the shopper to the WayForPay payment page and refunds from the order screen.
 */
final class Gateway extends WC_Payment_Gateway {

	public const ID     = 'uack_wayforpay';
	public const OPTION = 'woocommerce_uack_wayforpay_settings';

	/**
	 * Sets the gateway up.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->method_title       = __( 'WayForPay', 'ua-commerce-kit' );
		$this->method_description = __( 'Card, Apple Pay and Google Pay on the WayForPay payment page. Test mode uses the public test merchant from the WayForPay docs, so nothing is charged.', 'ua-commerce-kit' );
		$this->has_fields         = false;
		$this->supports           = array( 'products', 'refunds' );

		$this->init_form_fields();
		$this->init_settings();
		$this->title       = (string) $this->get_option( 'title' );
		$this->description = (string) $this->get_option( 'description' );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			function (): void {
				$this->process_admin_options();
			}
		);
	}

	/**
	 * Settings fields.
	 */
	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled'          => array(
				'title'   => __( 'Enable', 'ua-commerce-kit' ),
				'type'    => 'checkbox',
				'label'   => __( 'Accept payments through WayForPay', 'ua-commerce-kit' ),
				'default' => 'no',
			),
			'title'            => array(
				'title'   => __( 'Title', 'ua-commerce-kit' ),
				'type'    => 'text',
				'default' => __( 'Card online', 'ua-commerce-kit' ),
			),
			'description'      => array(
				'title'   => __( 'Description', 'ua-commerce-kit' ),
				'type'    => 'textarea',
				'default' => __( 'Visa, Mastercard, Apple Pay or Google Pay on the secure WayForPay page.', 'ua-commerce-kit' ),
			),
			'test_mode'        => array(
				'title'       => __( 'Test mode', 'ua-commerce-kit' ),
				'type'        => 'checkbox',
				'label'       => __( 'Use the public test merchant; nothing is charged', 'ua-commerce-kit' ),
				'default'     => 'yes',
				'description' => __( 'Turn this off and fill in the merchant account and secret key from the WayForPay merchant account to take real payments.', 'ua-commerce-kit' ),
			),
			'merchant_account' => array(
				'title'   => __( 'Merchant account', 'ua-commerce-kit' ),
				'type'    => 'text',
				'default' => '',
			),
			'secret_key'       => array(
				'title'       => __( 'Secret key', 'ua-commerce-kit' ),
				'type'        => 'password',
				'default'     => '',
				'description' => __( 'Or define UACK_WFP_ACCOUNT and UACK_WFP_SECRET in wp-config.php.', 'ua-commerce-kit' ),
			),
		);
	}

	/**
	 * Credentials from the saved settings, also outside a gateway instance.
	 */
	public static function credentials(): Credentials {
		$settings = get_option( self::OPTION, array() );
		return Credentials::from_settings( is_array( $settings ) ? $settings : array() );
	}

	/**
	 * Sends the shopper to the page that posts the signed form to WayForPay.
	 *
	 * @param int $order_id Order.
	 * @return array{result: string, redirect: string}
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return array(
				'result'   => 'failure',
				'redirect' => '',
			);
		}
		$order->update_meta_data( Payments::LANGUAGE, str_starts_with( determine_locale(), 'uk' ) ? 'UA' : 'EN' );
		$order->save();
		return array(
			'result'   => 'success',
			'redirect' => Endpoints::url( Endpoints::PAY, $order ),
		);
	}

	/**
	 * Refunds through the WayForPay API.
	 *
	 * @param int        $order_id Order.
	 * @param float|null $amount   Amount.
	 * @param string     $reason   Reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || null === $amount || $amount <= 0 ) {
			return new WP_Error( 'uack_wfp_refund', __( 'Nothing to refund.', 'ua-commerce-kit' ) );
		}
		$reference = $order->get_transaction_id() ? $order->get_transaction_id() : (string) $order->get_meta( Payments::REFERENCE );
		$result    = ( new Api( self::credentials() ) )->refund( $reference, (float) $amount, $order->get_currency(), (string) $reason );
		if ( ! $result['ok'] ) {
			/* translators: %s: reason from WayForPay. */
			return new WP_Error( 'uack_wfp_refund', sprintf( __( 'WayForPay refused the refund: %s', 'ua-commerce-kit' ), $result['reason'] ) );
		}
		$order->add_order_note(
			sprintf(
				/* translators: 1: amount, 2: status from WayForPay. */
				__( 'WayForPay refunded %1$s (%2$s).', 'ua-commerce-kit' ),
				wp_strip_all_tags( wc_price( (float) $amount, array( 'currency' => $order->get_currency() ) ) ),
				$result['status']
			)
		);
		return true;
	}
}
