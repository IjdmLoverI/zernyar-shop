<?php
/**
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Tests\NovaPoshta;

use UaCommerceKit\NovaPoshta\Selection;
use UaCommerceKit\Tests\TestCase;

final class SelectionTest extends TestCase {

	private const CITY   = 'db5c88ce-391c-11dd-90d9-001a92567626';
	private const BRANCH = '0d545f5e-e1c2-11e3-8c4a-0050568002cf';

	public function test_a_full_pick_survives_the_round_trip(): void {
		$data = array(
			'city_ref'      => self::CITY,
			'city'          => 'Біла Церква',
			'warehouse_ref' => self::BRANCH,
			'warehouse'     => 'Відділення №3: вул. Чорних Запорожців, 47/1',
			'number'        => '3',
			'type'          => 'branch',
			'postcode'      => '09108',
		);

		$this->assertSame( $data, Selection::from_array( $data )->to_array() );
	}

	public function test_a_bad_city_reference_drops_everything(): void {
		$selection = Selection::from_array(
			array(
				'city_ref'      => "'; DROP TABLE wp_posts; --",
				'warehouse_ref' => self::BRANCH,
			)
		);

		$this->assertFalse( $selection->has_city() );
		$this->assertFalse( $selection->has_warehouse() );
	}

	public function test_a_bad_branch_reference_keeps_the_city(): void {
		$selection = Selection::from_array(
			array(
				'city_ref'      => self::CITY,
				'city'          => 'Біла Церква',
				'warehouse_ref' => 'not-a-ref',
			)
		);

		$this->assertTrue( $selection->has_city() );
		$this->assertFalse( $selection->has_warehouse() );
	}

	public function test_text_is_cleaned(): void {
		$selection = Selection::from_array(
			array(
				'city_ref'      => self::CITY,
				'city'          => "  Біла\n<script>alert(1)</script> Церква ",
				'warehouse_ref' => self::BRANCH,
				'warehouse'     => str_repeat( 'а', 300 ),
				'type'          => 'spaceship',
			)
		);

		$this->assertSame( 'Біла script alert(1) /script Церква', $selection->city );
		$this->assertSame( 200, mb_strlen( $selection->warehouse ) );
		$this->assertSame( 'branch', $selection->type );
	}

	public function test_a_branch_from_the_api_replaces_what_the_browser_sent(): void {
		$selection = Selection::from_array(
			array(
				'city_ref'      => self::CITY,
				'city'          => 'Біла Церква',
				'warehouse_ref' => self::BRANCH,
				'warehouse'     => 'Forged name',
			)
		)->with_warehouse(
			array(
				'ref'      => self::BRANCH,
				'number'   => '3',
				'name'     => 'Відділення №3',
				'type'     => 'branch',
				'city_ref' => self::CITY,
				'postcode' => '09108',
			)
		);

		$this->assertSame( 'Відділення №3', $selection->warehouse );
		$this->assertSame( '09108', $selection->postcode );
	}

	public function test_reference_format(): void {
		$this->assertTrue( Selection::is_ref( self::CITY ) );
		$this->assertTrue( Selection::is_ref( strtoupper( self::CITY ) ) );
		$this->assertFalse( Selection::is_ref( substr( self::CITY, 1 ) ) );
		$this->assertFalse( Selection::is_ref( '' ) );
	}
}
