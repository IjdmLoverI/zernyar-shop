<?php
/**
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Tests\WayForPay;

use UaCommerceKit\Tests\TestCase;
use UaCommerceKit\WayForPay\Credentials;
use UaCommerceKit\WayForPay\Signature;

final class SignatureTest extends TestCase {

	/**
	 * A CHECK_STATUS answer the live API gave the test merchant on 16.10.2026.
	 *
	 * @var array<string, mixed>
	 */
	private const LIVE_ANSWER = array(
		'merchantAccount'   => 'test_merch_n1',
		'reason'            => 'Order Not Found',
		'reasonCode'        => 1127,
		'orderReference'    => 'probe-1792134791',
		'amount'            => '',
		'currency'          => '',
		'authCode'          => '',
		'cardPan'           => '',
		'transactionStatus' => 'Declined',
		'merchantSignature' => 'cc10225aedba58e0cd562c2cdc1291e3',
	);

	public function test_signs_a_check_status_request_the_api_accepted(): void {
		$signature = Signature::sign( array( 'test_merch_n1', 'probe-1792134791' ), Credentials::TEST_SECRET );

		$this->assertSame( 'e3d235a638c166fd6805db162d0a622e', $signature );
	}

	public function test_verifies_the_signature_of_a_live_answer(): void {
		$values = Signature::result_fields( self::LIVE_ANSWER );

		$this->assertSame( array( 'test_merch_n1', 'probe-1792134791', '', '', '', '', 'Declined', '1127' ), $values );
		$this->assertTrue( Signature::matches( $values, Credentials::TEST_SECRET, self::LIVE_ANSWER['merchantSignature'] ) );
	}

	public function test_rejects_a_changed_answer(): void {
		$answer                      = self::LIVE_ANSWER;
		$answer['transactionStatus'] = 'Approved';

		$this->assertFalse( Signature::matches( Signature::result_fields( $answer ), Credentials::TEST_SECRET, $answer['merchantSignature'] ) );
	}

	public function test_accepts_an_upper_case_signature(): void {
		$values = Signature::result_fields( self::LIVE_ANSWER );

		$this->assertTrue( Signature::matches( $values, Credentials::TEST_SECRET, strtoupper( self::LIVE_ANSWER['merchantSignature'] ) ) );
	}

	public function test_treats_missing_and_non_scalar_fields_as_empty(): void {
		$values = Signature::result_fields(
			array(
				'merchantAccount' => 'shop',
				'amount'          => array( 1 ),
			)
		);

		$this->assertSame( array( 'shop', '', '', '', '', '', '', '' ), $values );
	}
}
