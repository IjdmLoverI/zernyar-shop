<?php
/**
 * City and branch search for the checkout.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * GET /wp-json/ua-commerce-kit/v1/np/cities and /np/warehouses.
 *
 * The routes are public because guests check out, so they answer from the cache
 * where they can and limit how often one visitor may ask.
 */
final class Rest {

	public const NAMESPACE = 'ua-commerce-kit/v1';

	private const LIMIT  = 120;
	private const WINDOW = 600;

	/**
	 * Hooks the routes in.
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	/**
	 * Registers the routes.
	 */
	public function routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/np/cities',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'cities' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'required'          => true,
						'minLength'         => 2,
						'maxLength'         => 60,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/np/warehouses',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'warehouses' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'city' => array(
						'type'              => 'string',
						'required'          => true,
						'validate_callback' => static fn ( $value ): bool => is_string( $value ) && Selection::is_ref( $value ),
					),
					'q'    => array(
						'type'              => 'string',
						'default'           => '',
						'maxLength'         => 60,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Cities matching the query.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function cities( WP_REST_Request $request ) {
		$limited = $this->limit();
		if ( $limited ) {
			return $limited;
		}
		$cities = Client::from_settings()->cities( (string) $request->get_param( 'q' ) );
		return null === $cities ? $this->unavailable() : new WP_REST_Response( $cities );
	}

	/**
	 * Branches of a city matching the query.
	 *
	 * @param WP_REST_Request $request Request.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @return WP_REST_Response|WP_Error
	 */
	public function warehouses( WP_REST_Request $request ) {
		$limited = $this->limit();
		if ( $limited ) {
			return $limited;
		}
		$warehouses = Client::from_settings()->warehouses( (string) $request->get_param( 'city' ), trim( (string) $request->get_param( 'q' ) ) );
		return null === $warehouses ? $this->unavailable() : new WP_REST_Response( $warehouses );
	}

	/**
	 * Counts requests per visitor and refuses past the limit.
	 */
	private function limit(): ?WP_Error {
		$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key   = 'uack_np_rate_' . substr( hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ), 0, 20 );
		$count = (int) get_transient( $key );
		if ( $count >= self::LIMIT ) {
			return new WP_Error( 'uack_np_rate_limited', __( 'Too many searches. Try again in a few minutes.', 'ua-commerce-kit' ), array( 'status' => 429 ) );
		}
		set_transient( $key, $count + 1, self::WINDOW );
		return null;
	}

	/**
	 * Error for an unreachable API.
	 */
	private function unavailable(): WP_Error {
		return new WP_Error( 'uack_np_unavailable', __( 'Nova Poshta does not answer right now. Try again in a minute.', 'ua-commerce-kit' ), array( 'status' => 502 ) );
	}
}
