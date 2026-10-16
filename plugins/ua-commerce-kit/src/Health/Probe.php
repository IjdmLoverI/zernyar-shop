<?php
/**
 * Reads the answers of the connection checks.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Health;

use UaCommerceKit\HttpResult;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether each service can be used from this server.
 */
final class Probe {

	public const GOOD        = 'good';
	public const RECOMMENDED = 'recommended';
	public const CRITICAL    = 'critical';

	/**
	 * A city search on the Nova Poshta API.
	 *
	 * @param HttpResult $result Answer to searchSettlements.
	 * @return array{status: string, detail: string}
	 */
	public static function nova_poshta( HttpResult $result ): array {
		if ( ! $result->reached() ) {
			return self::verdict( self::CRITICAL, (string) $result->error );
		}
		$json = $result->json ?? array();
		if ( true === ( $json['success'] ?? null ) ) {
			return self::verdict( self::GOOD, '' );
		}
		$errors = array_filter( array_map( 'strval', (array) ( $json['errors'] ?? array() ) ) );
		return self::verdict( self::CRITICAL, $errors ? implode( '; ', $errors ) : 'HTTP ' . $result->status );
	}

	/**
	 * A CHECK_STATUS call for an order that does not exist.
	 *
	 * Code 1127 (order not found) means the merchant and the signature were accepted.
	 *
	 * @param HttpResult $result Answer to CHECK_STATUS.
	 * @return array{status: string, detail: string}
	 */
	public static function wayforpay( HttpResult $result ): array {
		if ( ! $result->reached() ) {
			return self::verdict( self::CRITICAL, (string) $result->error );
		}
		$json   = $result->json ?? array();
		$code   = (int) ( $json['reasonCode'] ?? 0 );
		$reason = (string) ( $json['reason'] ?? 'HTTP ' . $result->status );
		if ( 1127 === $code || 1100 === $code ) {
			return self::verdict( self::GOOD, '' );
		}
		return self::verdict( self::RECOMMENDED, $code ? $reason . ' (' . $code . ')' : $reason );
	}

	/**
	 * Answer to getMe with the bot token, or of the API root when no bot is set up.
	 *
	 * @param HttpResult $result    Answer.
	 * @param bool       $has_token Whether a bot token is configured.
	 * @return array{status: string, detail: string}
	 */
	public static function telegram( HttpResult $result, bool $has_token ): array {
		if ( ! $result->reached() ) {
			return self::verdict( self::CRITICAL, (string) $result->error );
		}
		if ( ! $has_token ) {
			return self::verdict( self::RECOMMENDED, '' );
		}
		$json = $result->json ?? array();
		if ( true === ( $json['ok'] ?? null ) ) {
			return self::verdict( self::GOOD, '@' . (string) ( $json['result']['username'] ?? '' ) );
		}
		return self::verdict( self::CRITICAL, (string) ( $json['description'] ?? 'HTTP ' . $result->status ) );
	}

	/**
	 * Builds a verdict.
	 *
	 * @param string $status good, recommended or critical.
	 * @param string $detail What the service said.
	 * @return array{status: string, detail: string}
	 */
	private static function verdict( string $status, string $detail ): array {
		return array(
			'status' => $status,
			'detail' => $detail,
		);
	}
}
