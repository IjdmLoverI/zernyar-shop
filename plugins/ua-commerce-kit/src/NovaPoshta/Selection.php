<?php
/**
 * The city and branch a shopper picked.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable pick, kept in the WooCommerce session between checkout requests.
 */
final class Selection {

	public const SESSION_KEY = 'uack_np';

	/**
	 * A pick.
	 *
	 * @param string $city_ref      City reference.
	 * @param string $city          City name.
	 * @param string $warehouse_ref Branch reference.
	 * @param string $warehouse     Branch description with the address.
	 * @param string $number        Branch number.
	 * @param string $type          branch, locker or point.
	 * @param string $postcode      Branch postcode.
	 */
	public function __construct(
		public readonly string $city_ref = '',
		public readonly string $city = '',
		public readonly string $warehouse_ref = '',
		public readonly string $warehouse = '',
		public readonly string $number = '',
		public readonly string $type = '',
		public readonly string $postcode = '',
	) {}

	/**
	 * Builds a pick from untrusted input, dropping what does not look right.
	 *
	 * @param array<string, mixed> $data Input.
	 */
	public static function from_array( array $data ): self {
		$city_ref = self::text( $data['city_ref'] ?? '' );
		if ( ! self::is_ref( $city_ref ) ) {
			return new self();
		}
		$warehouse_ref = self::text( $data['warehouse_ref'] ?? '' );
		if ( ! self::is_ref( $warehouse_ref ) ) {
			return new self( $city_ref, self::text( $data['city'] ?? '' ) );
		}
		$type = self::text( $data['type'] ?? '' );
		return new self(
			$city_ref,
			self::text( $data['city'] ?? '' ),
			$warehouse_ref,
			self::text( $data['warehouse'] ?? '' ),
			self::text( $data['number'] ?? '' ),
			in_array( $type, array( 'branch', 'locker', 'point' ), true ) ? $type : 'branch',
			self::text( $data['postcode'] ?? '' ),
		);
	}

	/**
	 * Same city with a branch from the API.
	 *
	 * @param array{ref: string, number: string, name: string, type: string, city_ref: string, postcode: string} $warehouse Branch.
	 */
	public function with_warehouse( array $warehouse ): self {
		return new self( $this->city_ref, $this->city, $warehouse['ref'], $warehouse['name'], $warehouse['number'], $warehouse['type'], $warehouse['postcode'] );
	}

	/**
	 * Plain array for the session and the Store API.
	 *
	 * @return array<string, string>
	 */
	public function to_array(): array {
		return array(
			'city_ref'      => $this->city_ref,
			'city'          => $this->city,
			'warehouse_ref' => $this->warehouse_ref,
			'warehouse'     => $this->warehouse,
			'number'        => $this->number,
			'type'          => $this->type,
			'postcode'      => $this->postcode,
		);
	}

	/**
	 * Whether a city is picked.
	 */
	public function has_city(): bool {
		return '' !== $this->city_ref;
	}

	/**
	 * Whether a branch is picked.
	 */
	public function has_warehouse(): bool {
		return '' !== $this->warehouse_ref;
	}

	/**
	 * Nova Poshta references are UUIDs.
	 *
	 * @param string $value Candidate.
	 */
	public static function is_ref( string $value ): bool {
		return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value );
	}

	/**
	 * One line of plain text, at most 200 characters.
	 *
	 * @param mixed $value Input.
	 */
	private static function text( mixed $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$text = trim( (string) preg_replace( '/[\x00-\x1F\x7F<>]+/u', ' ', (string) $value ) );
		return mb_substr( (string) preg_replace( '/\s+/u', ' ', $text ), 0, 200 );
	}
}
