<?php
/**
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Tests\WayForPay;

use UaCommerceKit\Tests\TestCase;
use UaCommerceKit\WayForPay\Credentials;
use UaCommerceKit\WayForPay\Purchase;

final class PurchaseTest extends TestCase {

	/**
	 * @var list<array{name: string, price: float, count: int}>
	 */
	private const ITEMS = array(
		array(
			'name'  => 'Ефіопія Іргачеффе - 250 г, У зернах',
			'price' => 420.0,
			'count' => 1,
		),
		array(
			'name'  => 'Нова Пошта',
			'price' => 70.0,
			'count' => 1,
		),
	);

	public function test_signature_covers_the_documented_fields_in_order(): void {
		$fields = Purchase::fields( Credentials::test(), 'zernyar.free.nf', 'zr1-163-1792140000', 1792139990, 490.0, 'UAH', self::ITEMS, array() );

		$signed = 'test_merch_n1;zernyar.free.nf;zr1-163-1792140000;1792139990;490.00;UAH;'
			. 'Ефіопія Іргачеффе - 250 г, У зернах;Нова Пошта;1;1;420.00;70.00';
		$this->assertSame( hash_hmac( 'md5', $signed, Credentials::TEST_SECRET ), $fields['merchantSignature'] );
		$this->assertSame( array( '420.00', '70.00' ), $fields['productPrice'] );
		$this->assertSame( array( '1', '1' ), $fields['productCount'] );
		$this->assertSame( '490.00', $fields['amount'] );
	}

	public function test_unsigned_extras_are_passed_through(): void {
		$fields = Purchase::fields(
			Credentials::test(),
			'zernyar.free.nf',
			'zr1-163-1',
			1,
			490.0,
			'UAH',
			self::ITEMS,
			array(
				'language'  => 'EN',
				'returnUrl' => 'https://zernyar.free.nf/wc-api/uack_wayforpay_return/?order=163&key=k',
			)
		);

		$this->assertSame( 'EN', $fields['language'] );
		$this->assertStringContainsString( 'order=163', (string) $fields['returnUrl'] );
	}

	public function test_lines_that_do_not_add_up_become_one_line(): void {
		$fields = Purchase::fields( Credentials::test(), 'zernyar.free.nf', 'zr1-163-1', 1, 450.0, 'UAH', self::ITEMS, array() );

		$this->assertSame( array( 'zr1-163-1' ), $fields['productName'] );
		$this->assertSame( array( '450.00' ), $fields['productPrice'] );
	}

	public function test_money_format(): void {
		$this->assertSame( '1547.36', Purchase::money( 1547.355 ) );
		$this->assertSame( '1480.00', Purchase::money( 1480 ) );
	}
}
