<?php
/**
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Tests\NovaPoshta;

use PHPUnit\Framework\Attributes\DataProvider;
use UaCommerceKit\NovaPoshta\Cost;
use UaCommerceKit\Tests\TestCase;

final class CostTest extends TestCase {

	/**
	 * @return array<string, array{string, float, float, float, ?float, float}>
	 */
	public static function cases(): array {
		return array(
			'fixed'                               => array( Cost::FIXED, 380, 80, 0, null, 80.0 ),
			'fixed ignores the tariff'            => array( Cost::FIXED, 380, 80, 0, 95, 80.0 ),
			'tariff'                              => array( Cost::CARRIER, 380, 80, 0, 90, 90.0 ),
			'tariff unknown falls back to fixed'  => array( Cost::CARRIER, 380, 80, 0, null, 80.0 ),
			'free from the threshold'             => array( Cost::CARRIER, 1500, 80, 1500, 120, 0.0 ),
			'below the threshold pays the tariff' => array( Cost::CARRIER, 1499, 80, 1500, 120, 120.0 ),
			'negative fixed cost is zero'         => array( Cost::FIXED, 100, -5, 0, null, 0.0 ),
		);
	}

	#[DataProvider( 'cases' )]
	public function test_cost( string $mode, float $subtotal, float $fixed, float $free_from, ?float $carrier, float $expected ): void {
		$this->assertSame( $expected, Cost::calculate( $mode, $subtotal, $fixed, $free_from, $carrier ) );
	}

	public function test_weight_uses_a_default_for_items_without_one(): void {
		$weight = Cost::weight(
			array(
				array(
					'weight'   => 0.27,
					'quantity' => 2,
				),
				array(
					'weight'   => 0.0,
					'quantity' => 1,
				),
			)
		);

		$this->assertSame( 0.84, $weight );
	}

	public function test_weight_is_never_below_100_grams(): void {
		$this->assertSame( 0.1, Cost::weight( array() ) );
	}
}
