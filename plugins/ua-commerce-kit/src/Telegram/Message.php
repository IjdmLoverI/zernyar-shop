<?php
/**
 * Text of an order notification.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Telegram;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the HTML message Telegram shows, from plain order data.
 *
 * @phpstan-type OrderData array{
 *     event: string,
 *     number: string,
 *     total: string,
 *     items: list<array{name: string, quantity: int}>,
 *     customer: string,
 *     phone: string,
 *     delivery: string,
 *     payment: string,
 *     url: string
 * }
 */
final class Message {

	public const NEW_ORDER = 'new';
	public const PAID      = 'paid';

	/**
	 * Telegram's limit for one message.
	 */
	private const LIMIT = 4096;

	/**
	 * Message for an order event.
	 *
	 * @param array $data Order data.
	 * @phpstan-param OrderData $data
	 */
	public static function for_order( array $data ): string {
		$title = self::PAID === $data['event']
			/* translators: %s: order number. */
			? sprintf( __( 'Order #%s is paid', 'ua-commerce-kit' ), $data['number'] )
			/* translators: %s: order number. */
			: sprintf( __( 'New order #%s', 'ua-commerce-kit' ), $data['number'] );

		$lines   = array( ( self::PAID === $data['event'] ? '✅' : '🛒' ) . ' <b>' . self::escape( $title ) . '</b> · ' . self::escape( $data['total'] ) );
		$lines[] = '';
		foreach ( $data['items'] as $item ) {
			$lines[] = '• ' . self::escape( $item['name'] ) . ' × ' . $item['quantity'];
		}
		$lines[] = '';
		$lines[] = '👤 ' . self::escape( trim( $data['customer'] . ', ' . $data['phone'], ', ' ) );
		if ( '' !== $data['delivery'] ) {
			$lines[] = '📦 ' . self::escape( $data['delivery'] );
		}
		$lines[] = '💳 ' . self::escape( $data['payment'] );
		$lines[] = '';
		$lines[] = '<a href="' . self::escape( $data['url'] ) . '">' . self::escape( __( 'Open the order', 'ua-commerce-kit' ) ) . '</a>';

		return self::fit( implode( "\n", $lines ) );
	}

	/**
	 * Escapes text for Telegram's HTML mode.
	 *
	 * @param string $text Plain text.
	 */
	public static function escape( string $text ): string {
		return str_replace( array( '&', '<', '>', '"' ), array( '&amp;', '&lt;', '&gt;', '&quot;' ), $text );
	}

	/**
	 * Cuts a long list of items so the message stays under the limit.
	 *
	 * @param string $text Message.
	 */
	private static function fit( string $text ): string {
		if ( mb_strlen( $text ) <= self::LIMIT ) {
			return $text;
		}
		$lines  = explode( "\n", $text );
		$length = mb_strlen( $text );
		$count  = count( $lines );
		while ( $length > self::LIMIT - 2 && $count > 6 ) {
			$length -= mb_strlen( $lines[3] ) + 1;
			array_splice( $lines, 3, 1 );
			--$count;
		}
		array_splice( $lines, 3, 0, array( '…' ) );
		return implode( "\n", $lines );
	}
}
