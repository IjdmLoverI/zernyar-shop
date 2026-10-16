<?php
/**
 * Delivery cost rules.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

defined( 'ABSPATH' ) || exit;

/**
 * Picks the delivery cost from the method settings and the carrier's price.
 */
final class Cost {

	public const FIXED   = 'fixed';
	public const CARRIER = 'carrier';

	/**
	 * Cost of delivery for one package.
	 *
	 * @param string     $mode      fixed or carrier.
	 * @param float      $subtotal  Cost of the goods.
	 * @param float      $fixed     Fixed cost, also used when the carrier price is unknown.
	 * @param float      $free_from Order total from which delivery is free; 0 turns it off.
	 * @param float|null $carrier   Price from the Nova Poshta API, if there is one.
	 */
	public static function calculate( string $mode, float $subtotal, float $fixed, float $free_from, ?float $carrier ): float {
		if ( $free_from > 0 && $subtotal >= $free_from ) {
			return 0.0;
		}
		if ( self::CARRIER === $mode && null !== $carrier ) {
			return round( $carrier, 2 );
		}
		return max( 0.0, $fixed );
	}

	/**
	 * Parcel weight for the price request: the goods, or 0.3 kg per item without a weight.
	 *
	 * @param list<array{weight: float, quantity: int}> $items Cart lines.
	 */
	public static function weight( array $items ): float {
		$total = 0.0;
		foreach ( $items as $item ) {
			$total += ( $item['weight'] > 0 ? $item['weight'] : 0.3 ) * max( 1, $item['quantity'] );
		}
		return max( 0.1, round( $total, 2 ) );
	}
}
