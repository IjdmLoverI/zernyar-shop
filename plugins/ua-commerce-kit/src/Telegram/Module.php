<?php
/**
 * Telegram module.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Telegram;

use UaCommerceKit\Admin\SettingsPage;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the notifications, the test message and the chat finder on the settings screen.
 */
final class Module {

	private const TEST_ACTION = 'uack_telegram_test';

	/**
	 * Hooks the module in.
	 */
	public static function register(): void {
		( new Notifier() )->register();
		add_action( 'admin_post_' . self::TEST_ACTION, array( self::class, 'send_test' ) );
		add_action( 'woocommerce_admin_field_uack_telegram_tools', array( self::class, 'render_tools' ) );
		add_action( 'admin_notices', array( self::class, 'test_notice' ) );
	}

	/**
	 * Settings field with the test button and the chats that wrote to the bot.
	 */
	public static function render_tools(): void {
		$bot = Bot::from_settings();
		echo '<tr><th scope="row">' . esc_html__( 'Check', 'ua-commerce-kit' ) . '</th><td>';
		if ( null === $bot ) {
			echo '<p class="description">' . esc_html__( 'Save a bot token to send a test message.', 'ua-commerce-kit' ) . '</p></td></tr>';
			return;
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=' . self::TEST_ACTION ), self::TEST_ACTION );
		echo '<a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Send a test message', 'ua-commerce-kit' ) . '</a>';

		if ( '' === Config::chat_id() ) {
			$chats = $bot->recent_chats();
			echo '<p class="description">';
			if ( $chats ) {
				esc_html_e( 'Chats that wrote to the bot lately; copy the ID into the field above:', 'ua-commerce-kit' );
				echo '</p><ul>';
				foreach ( $chats as $id => $name ) {
					echo '<li><code>' . esc_html( $id ) . '</code> ' . esc_html( $name ) . '</li>';
				}
				echo '</ul>';
			} else {
				esc_html_e( 'Write anything to the bot in Telegram and reload this page to see your chat ID.', 'ua-commerce-kit' );
				echo '</p>';
			}
		}
		echo '</td></tr>';
	}

	/**
	 * Sends a test message and comes back to the settings.
	 */
	public static function send_test(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'ua-commerce-kit' ), 403 );
		}
		check_admin_referer( self::TEST_ACTION );

		$bot   = Bot::from_settings();
		$error = null === $bot || '' === Config::chat_id()
			? __( 'Set the bot token and the chat ID first.', 'ua-commerce-kit' )
			: $bot->send( Config::chat_id(), '👋 ' . Message::escape( __( 'Test message from the store. Order notifications will come here.', 'ua-commerce-kit' ) ) );

		$back = admin_url( 'admin.php?page=wc-settings&tab=' . SettingsPage::ID . '&section=telegram' );
		set_transient( 'uack_telegram_test_' . get_current_user_id(), '' === $error ? 'ok' : $error, 60 );
		wp_safe_redirect( $back );
		exit;
	}

	/**
	 * Result of the test message.
	 */
	public static function test_notice(): void {
		$key    = 'uack_telegram_test_' . get_current_user_id();
		$result = get_transient( $key );
		if ( false === $result ) {
			return;
		}
		delete_transient( $key );
		if ( 'ok' === $result ) {
			echo '<div class="notice notice-success"><p>' . esc_html__( 'The test message is sent. Check the chat.', 'ua-commerce-kit' ) . '</p></div>';
			return;
		}
		/* translators: %s: error from Telegram. */
		echo '<div class="notice notice-error"><p>' . esc_html( sprintf( __( 'Telegram did not take the message: %s', 'ua-commerce-kit' ), (string) $result ) ) . '</p></div>';
	}
}
