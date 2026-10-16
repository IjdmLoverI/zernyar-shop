<?php
/**
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use UaCommerceKit\Phone;

final class PhoneTest extends TestCase {

	/**
	 * @return array<string, array{string, ?string}>
	 */
	public static function numbers(): array {
		return array(
			'international'       => array( '+380671234567', '+380671234567' ),
			'with spaces'         => array( '+38 (067) 123-45-67', '+380671234567' ),
			'local'               => array( '067 123 45 67', '+380671234567' ),
			'without the zero'    => array( '671234567', '+380671234567' ),
			'old 80 prefix'       => array( '80671234567', '+380671234567' ),
			'too short'           => array( '067 123 45', null ),
			'another country'     => array( '+48 501 234 567', null ),
			'zero after the code' => array( '+380 012345678', null ),
			'letters'             => array( 'call me', null ),
		);
	}

	#[DataProvider( 'numbers' )]
	public function test_normalize( string $input, ?string $expected ): void {
		$this->assertSame( $expected, Phone::normalize( $input ) );
	}
}
