<?php
/**
 * Turns Nova Poshta API answers into the shapes the store uses.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

defined( 'ABSPATH' ) || exit;

/**
 * Plain functions over decoded JSON, so they can be tested without the API.
 *
 * @phpstan-type City array{ref: string, name: string, label: string}
 * @phpstan-type Warehouse array{ref: string, number: string, name: string, type: string, city_ref: string, postcode: string}
 */
final class Parser {

	private const TYPES = array(
		'Branch'   => 'branch',
		'Postomat' => 'locker',
		'DropOff'  => 'point',
	);

	/**
	 * Cities from searchSettlements, without settlements that have no branch.
	 *
	 * @param array<int|string, mixed> $data The data field of the answer.
	 * @return list<City>
	 */
	public static function cities( array $data ): array {
		$addresses = $data[0]['Addresses'] ?? array();
		$cities    = array();
		foreach ( is_array( $addresses ) ? $addresses : array() as $address ) {
			$ref = (string) ( $address['DeliveryCity'] ?? '' );
			if ( ! Selection::is_ref( $ref ) || (int) ( $address['Warehouses'] ?? 0 ) < 1 || isset( $cities[ $ref ] ) ) {
				continue;
			}
			$cities[ $ref ] = array(
				'ref'   => $ref,
				'name'  => (string) ( $address['MainDescription'] ?? '' ),
				'label' => (string) ( $address['Present'] ?? $address['MainDescription'] ?? '' ),
			);
		}
		return array_values( $cities );
	}

	/**
	 * Working branches, parcel lockers and pickup points from getWarehouses.
	 *
	 * @param array<int|string, mixed> $data The data field of the answer.
	 * @return list<Warehouse>
	 */
	public static function warehouses( array $data ): array {
		$warehouses = array();
		foreach ( $data as $warehouse ) {
			$type = self::TYPES[ (string) ( $warehouse['CategoryOfWarehouse'] ?? '' ) ] ?? null;
			if ( null === $type || 'Working' !== ( $warehouse['WarehouseStatus'] ?? 'Working' ) || ! Selection::is_ref( (string) ( $warehouse['Ref'] ?? '' ) ) ) {
				continue;
			}
			$warehouses[] = array(
				'ref'      => (string) $warehouse['Ref'],
				'number'   => (string) ( $warehouse['Number'] ?? '' ),
				'name'     => (string) ( $warehouse['Description'] ?? '' ),
				'type'     => $type,
				'city_ref' => (string) ( $warehouse['CityRef'] ?? '' ),
				'postcode' => (string) ( $warehouse['PostalCodeUA'] ?? '' ),
			);
		}
		return $warehouses;
	}

	/**
	 * Delivery price from getDocumentPrice.
	 *
	 * @param array<int|string, mixed> $data The data field of the answer.
	 */
	public static function price( array $data ): ?float {
		$cost = $data[0]['Cost'] ?? null;
		return is_numeric( $cost ) ? (float) $cost : null;
	}
}
