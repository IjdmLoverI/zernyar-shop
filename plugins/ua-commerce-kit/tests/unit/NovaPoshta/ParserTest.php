<?php
/**
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Tests\NovaPoshta;

use UaCommerceKit\NovaPoshta\Parser;
use UaCommerceKit\Tests\TestCase;

final class ParserTest extends TestCase {

	private const KYIV       = '8d5a980d-391c-11dd-90d9-001a92567626';
	private const BILA       = 'db5c88ce-391c-11dd-90d9-001a92567626';
	private const BRANCH     = '0d545f5e-e1c2-11e3-8c4a-0050568002cf';
	private const LOCKER     = '1f7c9a2e-5b33-11ee-a60f-48df37b921db';
	private const FULFILMENT = '7a2b4c6d-8e9f-11e3-8c4a-0050568002cf';

	public function test_cities_skip_settlements_without_branches_and_duplicates(): void {
		$data = array(
			array(
				'TotalCount' => 3,
				'Addresses'  => array(
					array(
						'Present'         => 'м. Біла Церква, Білоцерківський р-н, Київська обл.',
						'MainDescription' => 'Біла Церква',
						'DeliveryCity'    => self::BILA,
						'Warehouses'      => 897,
					),
					array(
						'Present'         => 'с. Біла, Тернопільський р-н, Тернопільська обл.',
						'MainDescription' => 'Біла',
						'DeliveryCity'    => '736b2f80-0050-11e5-8a92-005056887b8d',
						'Warehouses'      => 0,
					),
					array(
						'Present'         => 'м. Біла Церква (дубль)',
						'MainDescription' => 'Біла Церква',
						'DeliveryCity'    => self::BILA,
						'Warehouses'      => 897,
					),
				),
			),
		);

		$this->assertSame(
			array(
				array(
					'ref'   => self::BILA,
					'name'  => 'Біла Церква',
					'label' => 'м. Біла Церква, Білоцерківський р-н, Київська обл.',
				),
			),
			Parser::cities( $data )
		);
	}

	public function test_cities_of_an_empty_answer(): void {
		$this->assertSame( array(), Parser::cities( array() ) );
		$this->assertSame( array(), Parser::cities( array( array( 'Addresses' => 'oops' ) ) ) );
	}

	public function test_warehouses_keep_branches_lockers_and_points_that_work(): void {
		$data = array(
			$this->warehouse( self::BRANCH, '3', 'Branch', 'Working', 'Відділення №3: вул. Чорних Запорожців, 47/1' ),
			$this->warehouse( self::LOCKER, '31544', 'Postomat', 'Working', 'Поштомат №31544: вул. Київська, 2' ),
			$this->warehouse( 'c1d2e3f4-0000-11e3-8c4a-0050568002cf', '955', 'DropOff', 'Working', 'Пункт №955: вул. Хрещатик, 21А' ),
			$this->warehouse( self::FULFILMENT, '1', 'Fulfillment', 'Working', 'Склад' ),
			$this->warehouse( 'd1d2e3f4-0000-11e3-8c4a-0050568002cf', '7', 'Branch', 'Closed', 'Відділення №7' ),
		);

		$warehouses = Parser::warehouses( $data );

		$this->assertSame( array( 'branch', 'locker', 'point' ), array_column( $warehouses, 'type' ) );
		$this->assertSame(
			array(
				'ref'      => self::BRANCH,
				'number'   => '3',
				'name'     => 'Відділення №3: вул. Чорних Запорожців, 47/1',
				'type'     => 'branch',
				'city_ref' => self::KYIV,
				'postcode' => '09108',
			),
			$warehouses[0]
		);
	}

	public function test_price(): void {
		$this->assertSame(
			70.0,
			Parser::price(
				array(
					array(
						'Cost'         => 70,
						'AssessedCost' => 500,
					),
				)
			)
		);
		$this->assertNull( Parser::price( array() ) );
		$this->assertNull( Parser::price( array( array( 'Cost' => 'n/a' ) ) ) );
	}

	/**
	 * @return array<string, string>
	 */
	private function warehouse( string $ref, string $number, string $category, string $status, string $description ): array {
		return array(
			'Ref'                 => $ref,
			'Number'              => $number,
			'CategoryOfWarehouse' => $category,
			'WarehouseStatus'     => $status,
			'Description'         => $description,
			'CityRef'             => self::KYIV,
			'PostalCodeUA'        => '09108',
		);
	}
}
