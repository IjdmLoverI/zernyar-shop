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
 * Calls the JSON API and caches reference lookups for a day.
 *
 * Reference lookups (cities, branches, delivery price) also work without an API key.
 *
 * @phpstan-import-type City from Parser
 * @phpstan-import-type Warehouse from Parser
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
	 * Client with the key from the settings.
	 */
	public static function from_settings(): self {
		return new self( Settings::api_key() );
	}

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

	/**
	 * Cities with at least one branch whose name starts with the query.
	 *
	 * @param string $query Part of a city name.
	 * @return list<City>|null Null when the API cannot be reached.
	 */
	public function cities( string $query ): ?array {
		$data = $this->cached(
			'AddressGeneral',
			'searchSettlements',
			array(
				'CityName' => $query,
				'Limit'    => '20',
				'Page'     => '1',
			)
		);
		return null === $data ? null : Parser::cities( $data );
	}

	/**
	 * Branches of a city. A query of digits looks for the branch number first.
	 *
	 * @param string $city_ref City reference.
	 * @param string $query    Number or part of the address.
	 * @return list<Warehouse>|null Null when the API cannot be reached.
	 */
	public function warehouses( string $city_ref, string $query = '' ): ?array {
		$properties = array(
			'CityRef' => $city_ref,
			'Limit'   => '50',
			'Page'    => '1',
		);
		$exact      = array();
		if ( ctype_digit( $query ) ) {
			$exact = $this->cached( 'AddressGeneral', 'getWarehouses', $properties + array( 'WarehouseId' => $query ) ) ?? array();
		}
		if ( '' !== $query ) {
			$properties['FindByString'] = $query;
		}
		$data = $this->cached( 'AddressGeneral', 'getWarehouses', $properties );
		if ( null === $data ) {
			return null;
		}

		$found = array();
		foreach ( array_merge( Parser::warehouses( $exact ), Parser::warehouses( $data ) ) as $warehouse ) {
			$found[ $warehouse['ref'] ] = $warehouse;
		}
		return array_values( $found );
	}

	/**
	 * One branch by its reference.
	 *
	 * @param string $ref Branch reference.
	 * @return Warehouse|null Null when it is unknown, closed or the API cannot be reached.
	 */
	public function warehouse( string $ref ): ?array {
		$data = $this->cached( 'AddressGeneral', 'getWarehouses', array( 'Ref' => $ref ) );
		return null === $data ? null : ( Parser::warehouses( $data )[0] ?? null );
	}

	/**
	 * Delivery price from one city's branch to another's.
	 *
	 * @param string $from_ref Sender city.
	 * @param string $to_ref   Recipient city.
	 * @param float  $weight   Kilograms.
	 * @param float  $value    Declared value in hryvnias.
	 */
	public function price( string $from_ref, string $to_ref, float $weight, float $value ): ?float {
		$data = $this->cached(
			'InternetDocument',
			'getDocumentPrice',
			array(
				'CitySender'    => $from_ref,
				'CityRecipient' => $to_ref,
				'Weight'        => (string) $weight,
				'ServiceType'   => 'WarehouseWarehouse',
				'Cost'          => (string) max( 1, (int) round( $value ) ),
				'CargoType'     => 'Parcel',
				'SeatsAmount'   => '1',
			)
		);
		return null === $data ? null : Parser::price( $data );
	}

	/**
	 * The data field of a successful answer, cached for a day.
	 *
	 * @param string               $model      Model name.
	 * @param string               $method     Method name.
	 * @param array<string, mixed> $properties Method properties.
	 * @return array<int|string, mixed>|null
	 */
	private function cached( string $model, string $method, array $properties ): ?array {
		$key    = 'uack_np_' . md5( (string) wp_json_encode( array( $model, $method, $properties ) ) );
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$result = $this->call( $model, $method, $properties );
		$json   = $result->json ?? array();
		if ( ! $result->reached() || true !== ( $json['success'] ?? null ) ) {
			$this->log( $method, $result );
			return null;
		}

		$data = is_array( $json['data'] ?? null ) ? $json['data'] : array();
		set_transient( $key, $data, DAY_IN_SECONDS );
		return $data;
	}

	/**
	 * Writes a failed call to the WooCommerce log.
	 *
	 * @param string     $method Method name.
	 * @param HttpResult $result Answer.
	 */
	private function log( string $method, HttpResult $result ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		$errors = $result->error ?? implode( '; ', array_map( 'strval', (array) ( $result->json['errors'] ?? array() ) ) );
		wc_get_logger()->warning( "Nova Poshta {$method} failed: {$errors}", array( 'source' => 'ua-commerce-kit' ) );
	}
}
