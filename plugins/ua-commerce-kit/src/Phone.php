<?php
/**
 * Ukrainian phone numbers.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes what shoppers type into +380XXXXXXXXX.
 */
final class Phone {

	/**
	 * The number in international form, or null when it is not a Ukrainian number.
	 *
	 * @param string $input What the shopper typed.
	 */
	public static function normalize( string $input ): ?string {
		$digits = (string) preg_replace( '/\D+/', '', $input );
		if ( 9 === strlen( $digits ) ) {
			$digits = '380' . $digits;
		} elseif ( 10 === strlen( $digits ) && str_starts_with( $digits, '0' ) ) {
			$digits = '38' . $digits;
		} elseif ( 11 === strlen( $digits ) && str_starts_with( $digits, '80' ) ) {
			$digits = '3' . $digits;
		}
		return 1 === preg_match( '/^380[1-9]\d{8}$/', $digits ) ? '+' . $digits : null;
	}
}
