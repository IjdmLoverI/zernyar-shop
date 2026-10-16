<?php
/**
 * Telegram Bot API calls.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Telegram;

use UaCommerceKit\Http;
use UaCommerceKit\HttpResult;

defined( 'ABSPATH' ) || exit;

/**
 * Calls sendMessage and getUpdates for one bot.
 */
final class Bot {

	/**
	 * Bot with a token from @BotFather.
	 *
	 * @param string $token Bot token.
	 */
	public function __construct( private readonly string $token ) {}

	/**
	 * Bot from the settings, or null without a token.
	 */
	public static function from_settings(): ?self {
		$token = Config::token();
		return 1 === preg_match( '/^\d+:[\w-]+$/', $token ) ? new self( $token ) : null;
	}

	/**
	 * Sends an HTML message. The checkout waits for at most five seconds.
	 *
	 * @param string $chat_id Chat.
	 * @param string $html    Message.
	 * @return string Empty on success, otherwise what went wrong.
	 */
	public function send( string $chat_id, string $html ): string {
		$result = Http::post_json(
			$this->url( 'sendMessage' ),
			array(
				'chat_id'                  => $chat_id,
				'text'                     => $html,
				'parse_mode'               => 'HTML',
				'disable_web_page_preview' => true,
			),
			5
		);
		return self::error( $result );
	}

	/**
	 * Chats that wrote to the bot lately, to find the chat ID.
	 *
	 * @return array<string, string> Chat ID => name.
	 */
	public function recent_chats(): array {
		$result = Http::get( $this->url( 'getUpdates' ), 5 );
		$chats  = array();
		foreach ( (array) ( $result->json['result'] ?? array() ) as $update ) {
			$chat = $update['message']['chat'] ?? $update['my_chat_member']['chat'] ?? null;
			if ( is_array( $chat ) && isset( $chat['id'] ) ) {
				$name                          = $chat['title'] ?? trim( ( $chat['first_name'] ?? '' ) . ' ' . ( $chat['last_name'] ?? '' ) );
				$chats[ (string) $chat['id'] ] = (string) $name;
			}
		}
		return $chats;
	}

	/**
	 * Method URL.
	 *
	 * @param string $method Bot API method.
	 */
	private function url( string $method ): string {
		return 'https://api.telegram.org/bot' . $this->token . '/' . $method;
	}

	/**
	 * Error text of a call, empty when it worked.
	 *
	 * @param HttpResult $result Answer.
	 */
	private static function error( HttpResult $result ): string {
		if ( ! $result->reached() ) {
			return (string) $result->error;
		}
		if ( true === ( $result->json['ok'] ?? null ) ) {
			return '';
		}
		return (string) ( $result->json['description'] ?? 'HTTP ' . $result->status );
	}
}
