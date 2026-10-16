<?php
/**
 * Nova Poshta API 2.0.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

use UaCommerceKit\Http;
use UaCommerceKit\HttpResult;

defined( 'ABSPATH' ) || exit;

/**
 * Calls a model method of the JSON API.
 *
 * Reference lookups (cities, branches, delivery price) also work without an API key.
 */
final class Client {

	public const ENDPOINT = 'https://api.novaposhta.ua/v2.0/json/';

	/**
	 * Client with an optional key from the Nova Poshta business account.
	 *
	 * @param string $api_key API key, empty for anonymous lookups.
	 */
	public function __construct( private readonly string $api_key = '' ) {}

	/**
	 * Calls one API method.
	 *
	 * @param string               $model      Model name, e.g. AddressGeneral.
	 * @param string               $method     Method name, e.g. getWarehouses.
	 * @param array<string, mixed> $properties Method properties.
	 * @param int                  $timeout    Seconds.
	 */
	public function call( string $model, string $method, array $properties, int $timeout = 8 ): HttpResult {
		return Http::post_json(
			self::ENDPOINT,
			array(
				'apiKey'           => $this->api_key,
				'modelName'        => $model,
				'calledMethod'     => $method,
				'methodProperties' => (object) $properties,
			),
			$timeout
		);
	}
}
