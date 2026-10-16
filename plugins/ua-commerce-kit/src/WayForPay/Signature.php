<?php
/**
 * WayForPay request and response signatures.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

defined( 'ABSPATH' ) || exit;

/**
 * HMAC-MD5 over the field values joined with ";".
 */
final class Signature {

	/**
	 * Signs values in the order the API expects.
	 *
	 * @param array<int, string|int|float> $values Field values.
	 * @param string                       $secret Merchant secret key.
	 */
	public static function sign( array $values, string $secret ): string {
		return hash_hmac( 'md5', implode( ';', array_map( 'strval', $values ) ), $secret );
	}

	/**
	 * Checks a signature without leaking timing.
	 *
	 * @param array<int, string|int|float> $values    Field values.
	 * @param string                       $secret    Merchant secret key.
	 * @param string                       $signature Signature to check.
	 */
	public static function matches( array $values, string $secret, string $signature ): bool {
		return hash_equals( self::sign( $values, $secret ), strtolower( $signature ) );
	}

	/**
	 * Values signed in a payment result: the callback and the CHECK_STATUS answer.
	 *
	 * @param array<string, mixed> $data Answer from WayForPay.
	 * @return array<int, string>
	 */
	public static function result_fields( array $data ): array {
		$fields = array( 'merchantAccount', 'orderReference', 'amount', 'currency', 'authCode', 'cardPan', 'transactionStatus', 'reasonCode' );
		return array_map(
			static fn ( string $field ): string => isset( $data[ $field ] ) && is_scalar( $data[ $field ] ) ? (string) $data[ $field ] : '',
			$fields
		);
	}
}
