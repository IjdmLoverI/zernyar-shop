<?php
/**
 * Store-wide Nova Poshta settings.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

defined( 'ABSPATH' ) || exit;

/**
 * Values from WooCommerce → Settings → UA Commerce Kit → Nova Poshta.
 */
final class Settings {

	/**
	 * API key; constant UACK_NP_API_KEY wins over the settings screen.
	 */
	public static function api_key(): string {
		if ( defined( 'UACK_NP_API_KEY' ) && is_string( UACK_NP_API_KEY ) ) {
			return UACK_NP_API_KEY;
		}
		return trim( (string) get_option( 'uack_np_api_key', '' ) );
	}

	/**
	 * City the parcels are sent from, as a Nova Poshta reference.
	 *
	 * The settings keep the name; the reference is looked up once and remembered.
	 */
	public static function sender_city_ref(): string {
		$name   = trim( (string) get_option( 'uack_np_sender_city', 'Київ' ) );
		$cached = get_option( 'uack_np_sender_city_ref' );
		if ( is_array( $cached ) && ( $cached['name'] ?? '' ) === $name ) {
			return (string) $cached['ref'];
		}
		if ( '' === $name ) {
			return '';
		}

		$cities = Client::from_settings()->cities( $name );
		$ref    = $cities[0]['ref'] ?? '';
		if ( '' !== $ref ) {
			update_option(
				'uack_np_sender_city_ref',
				array(
					'name' => $name,
					'ref'  => $ref,
				),
				false
			);
		}
		return $ref;
	}

	/**
	 * Whether the street, postcode and region fields are hidden for Ukraine.
	 */
	public static function hide_address(): bool {
		return 'no' !== get_option( 'uack_np_hide_address', 'yes' );
	}
}
