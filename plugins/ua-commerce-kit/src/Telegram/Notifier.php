<?php
/**
 * Order notifications in Telegram.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Telegram;

use UaCommerceKit\NovaPoshta\OrderDetails;
use WC_Order;
use WC_Order_Item_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Sends a message when an order is placed and when it is paid online.
 *
 * Sending is synchronous with a short timeout: free hosting may never run
 * background jobs, and a failed message must not break the checkout.
 */
final class Notifier {

	private const SENT = '_uack_tg_sent';

	/**
	 * Hooks the notifications in.
	 */
	public function register(): void {
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'new_order' ) );
		add_action(
			'woocommerce_checkout_order_processed',
			function ( $order_id ): void {
				$order = wc_get_order( $order_id );
				if ( $order instanceof WC_Order ) {
					$this->new_order( $order );
				}
			}
		);
		add_action( 'woocommerce_payment_complete', array( $this, 'paid' ) );
	}

	/**
	 * A new order.
	 *
	 * @param WC_Order $order Order.
	 */
	public function new_order( WC_Order $order ): void {
		if ( 'no' !== get_option( 'uack_telegram_new', 'yes' ) ) {
			$this->notify( $order, Message::NEW_ORDER );
		}
	}

	/**
	 * An order paid online.
	 *
	 * @param int $order_id Order.
	 */
	public function paid( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order && 'no' !== get_option( 'uack_telegram_paid', 'yes' ) ) {
			$this->notify( $order, Message::PAID );
		}
	}

	/**
	 * Sends one message per order and event, and notes a failure on the order.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $event Message::NEW_ORDER or Message::PAID.
	 */
	private function notify( WC_Order $order, string $event ): void {
		$bot  = Bot::from_settings();
		$chat = Config::chat_id();
		$sent = (array) $order->get_meta( self::SENT );
		if ( null === $bot || '' === $chat || in_array( $event, $sent, true ) ) {
			return;
		}

		$error = $bot->send( $chat, Message::for_order( self::data( $order, $event ) ) );
		if ( '' === $error ) {
			$sent[] = $event;
			$order->update_meta_data( self::SENT, $sent );
			$order->save_meta_data();
			return;
		}
		/* translators: %s: error from Telegram. */
		$order->add_order_note( sprintf( __( 'Telegram notification was not sent: %s', 'ua-commerce-kit' ), $error ) );
		wc_get_logger()->warning( "Telegram notification for order {$order->get_id()} failed: {$error}", array( 'source' => 'ua-commerce-kit' ) );
	}

	/**
	 * Plain data of an order for the message.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $event Event.
	 * @return array{event: string, number: string, total: string, items: list<array{name: string, quantity: int}>, customer: string, phone: string, delivery: string, payment: string, url: string}
	 */
	public static function data( WC_Order $order, string $event ): array {
		$items = array();
		foreach ( $order->get_items() as $item ) {
			if ( $item instanceof WC_Order_Item_Product ) {
				$items[] = array(
					'name'     => wp_strip_all_tags( $item->get_name() ),
					'quantity' => (int) $item->get_quantity(),
				);
			}
		}

		$delivery  = '';
		$selection = class_exists( OrderDetails::class ) ? OrderDetails::selection( $order ) : null;
		if ( null !== $selection ) {
			$delivery = sprintf( '%s №%s, %s', OrderDetails::type_label( $selection->type ), $selection->number, $selection->city );
		} elseif ( $order->get_shipping_method() ) {
			$delivery = $order->get_shipping_method();
		}

		$payment = $order->get_payment_method_title();
		if ( Message::NEW_ORDER === $event && $order->needs_payment() && 'cod' !== $order->get_payment_method() ) {
			$payment .= ', ' . __( 'waiting for payment', 'ua-commerce-kit' );
		}
		if ( Message::PAID === $event && $order->get_transaction_id() ) {
			$payment .= ', ' . $order->get_transaction_id();
		}

		return array(
			'event'    => $event,
			'number'   => $order->get_order_number(),
			'total'    => html_entity_decode( wp_strip_all_tags( wc_price( (float) $order->get_total(), array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' ),
			'items'    => $items,
			'customer' => trim( $order->get_formatted_billing_full_name() ),
			'phone'    => $order->get_billing_phone(),
			'delivery' => $delivery,
			'payment'  => $payment,
			'url'      => $order->get_edit_order_url(),
		);
	}
}
