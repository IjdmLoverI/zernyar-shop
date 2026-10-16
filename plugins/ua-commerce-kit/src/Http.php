<?php
/**
 * JSON over HTTP through the WordPress HTTP API.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit;

defined( 'ABSPATH' ) || exit;

/**
 * Small wrapper so the API clients get a plain result instead of WP_Error.
 */
final class Http {

	/**
	 * Sends a JSON body and decodes a JSON answer.
	 *
	 * @param string               $url     Endpoint.
	 * @param array<string, mixed> $body    Request body.
	 * @param int                  $timeout Seconds.
	 */
	public static function post_json( string $url, array $body, int $timeout = 10 ): HttpResult {
		$response = wp_remote_post(
			$url,
			array(
				'timeout' => $timeout,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'    => (string) wp_json_encode( $body ),
			)
		);
		return self::result( $response );
	}

	/**
	 * Sends a GET request and decodes a JSON answer if there is one.
	 *
	 * @param string $url     Endpoint.
	 * @param int    $timeout Seconds.
	 */
	public static function get( string $url, int $timeout = 10 ): HttpResult {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => $timeout,
				'redirection' => 0,
			)
		);
		return self::result( $response );
	}

	/**
	 * Turns a WordPress HTTP response into a result.
	 *
	 * @param array<string, mixed>|\WP_Error $response Response.
	 */
	private static function result( $response ): HttpResult {
		if ( is_wp_error( $response ) ) {
			return HttpResult::failed( $response->get_error_message() );
		}
		$json = json_decode( wp_remote_retrieve_body( $response ), true );
		return new HttpResult( (int) wp_remote_retrieve_response_code( $response ), is_array( $json ) ? $json : null );
	}
}
