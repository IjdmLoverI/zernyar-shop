<?php
/**
 * Bot token and chat for notifications.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Telegram;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the bot settings. Constants in wp-config.php win over the settings screen.
 */
final class Config {

	/**
	 * Bot token from @BotFather.
	 */
	public static function token(): string {
		if ( defined( 'UACK_TELEGRAM_TOKEN' ) && is_string( UACK_TELEGRAM_TOKEN ) && '' !== UACK_TELEGRAM_TOKEN ) {
			return UACK_TELEGRAM_TOKEN;
		}
		return trim( (string) get_option( 'uack_telegram_token', '' ) );
	}

	/**
	 * Chat that receives the notifications.
	 */
	public static function chat_id(): string {
		if ( defined( 'UACK_TELEGRAM_CHAT_ID' ) && is_string( UACK_TELEGRAM_CHAT_ID ) && '' !== UACK_TELEGRAM_CHAT_ID ) {
			return UACK_TELEGRAM_CHAT_ID;
		}
		return trim( (string) get_option( 'uack_telegram_chat_id', '' ) );
	}
}
