<?php
/**
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Tests\WayForPay;

use UaCommerceKit\Tests\TestCase;
use UaCommerceKit\WayForPay\Answer;
use UaCommerceKit\WayForPay\Credentials;
use UaCommerceKit\WayForPay\Signature;
use UaCommerceKit\WayForPay\Status;

final class AnswerTest extends TestCase {

	/**
	 * A callback for an approved payment, signed with the test key.
	 *
	 * @return array<string, mixed>
	 */
	private function approved( array $changes = array() ): array {
		$data                      = array_merge(
			array(
				'merchantAccount'   => 'test_merch_n1',
				'orderReference'    => 'zr1-163-1792140000',
				'amount'            => 490,
				'currency'          => 'UAH',
				'authCode'          => '541963',
				'cardPan'           => '41****8217',
				'transactionStatus' => 'Approved',
				'reasonCode'        => 1100,
				'reason'            => 'Ok',
			),
			$changes
		);
		$data['merchantSignature'] = Signature::sign( Signature::result_fields( $data ), Credentials::TEST_SECRET );
		return $data;
	}

	public function test_reads_a_signed_answer(): void {
		$answer = Answer::verified( $this->approved(), Credentials::test() );

		$this->assertNotNull( $answer );
		$this->assertSame( Status::PAID, $answer->state );
		$this->assertSame( '41****8217', $answer->card );
		$this->assertTrue( $answer->matches( 490.0, 'uah' ) );
		$this->assertFalse( $answer->matches( 489.99, 'UAH' ) );
	}

	public function test_rejects_a_changed_status(): void {
		$data                      = $this->approved( array( 'transactionStatus' => 'Declined' ) );
		$data['transactionStatus'] = 'Approved';

		$this->assertNull( Answer::verified( $data, Credentials::test() ) );
	}

	public function test_rejects_another_merchant(): void {
		$this->assertNull( Answer::verified( $this->approved(), new Credentials( 'shop', Credentials::TEST_SECRET ) ) );
	}

	public function test_rejects_a_missing_signature(): void {
		$data = $this->approved();
		unset( $data['merchantSignature'] );

		$this->assertNull( Answer::verified( $data, Credentials::test() ) );
	}

	public function test_order_not_found_is_recognised(): void {
		$answer = Answer::verified(
			$this->approved(
				array(
					'transactionStatus' => 'Declined',
					'reasonCode'        => 1127,
					'reason'            => 'Order Not Found',
				)
			),
			Credentials::test()
		);

		$this->assertNotNull( $answer );
		$this->assertTrue( $answer->not_found() );
	}

	public function test_accept_reply_is_signed(): void {
		$reply = Answer::accept( 'zr1-163-1792140000', Credentials::test(), 1792140100 );

		$this->assertSame( 'accept', $reply['status'] );
		$this->assertSame( hash_hmac( 'md5', 'zr1-163-1792140000;accept;1792140100', Credentials::TEST_SECRET ), $reply['signature'] );
	}

	public function test_statuses(): void {
		$this->assertSame( Status::PENDING, Status::of( 'InProcessing' ) );
		$this->assertSame( Status::PENDING, Status::of( 'WaitingAuthComplete' ) );
		$this->assertSame( Status::FAILED, Status::of( 'Expired' ) );
		$this->assertSame( Status::REFUNDED, Status::of( 'Voided' ) );
		$this->assertSame( Status::UNKNOWN, Status::of( 'Something new' ) );
	}

	public function test_test_mode_uses_the_public_merchant(): void {
		$this->assertTrue( Credentials::from_settings( array() )->is_test() );
		$this->assertTrue(
			Credentials::from_settings(
				array(
					'test_mode'        => 'yes',
					'merchant_account' => 'shop',
					'secret_key'       => 's',
				)
			)->is_test()
		);
		$this->assertTrue(
			Credentials::from_settings(
				array(
					'test_mode'        => 'no',
					'merchant_account' => 'shop',
				)
			)->is_test()
		);
		$this->assertSame(
			'shop',
			Credentials::from_settings(
				array(
					'test_mode'        => 'no',
					'merchant_account' => 'shop',
					'secret_key'       => 's',
				)
			)->account
		);
	}
}
