<?php
/**
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Tests\Health;

use UaCommerceKit\Health\Probe;
use UaCommerceKit\HttpResult;
use UaCommerceKit\Tests\TestCase;

final class ProbeTest extends TestCase {

	public function test_nova_poshta_answer_with_success_is_good(): void {
		$verdict = Probe::nova_poshta( new HttpResult( 200, array( 'success' => true ) ) );

		$this->assertSame( Probe::GOOD, $verdict['status'] );
	}

	public function test_nova_poshta_errors_are_shown(): void {
		$verdict = Probe::nova_poshta(
			new HttpResult(
				200,
				array(
					'success' => false,
					'errors'  => array( 'API key expired' ),
				)
			)
		);

		$this->assertSame( Probe::CRITICAL, $verdict['status'] );
		$this->assertSame( 'API key expired', $verdict['detail'] );
	}

	public function test_blocked_request_is_critical(): void {
		$verdict = Probe::nova_poshta( HttpResult::failed( 'cURL error 7: Failed to connect' ) );

		$this->assertSame( Probe::CRITICAL, $verdict['status'] );
		$this->assertStringContainsString( 'Failed to connect', $verdict['detail'] );
	}

	public function test_html_instead_of_json_is_critical(): void {
		$verdict = Probe::nova_poshta( new HttpResult( 403 ) );

		$this->assertSame( Probe::CRITICAL, $verdict['status'] );
		$this->assertSame( 'HTTP 403', $verdict['detail'] );
	}

	public function test_wayforpay_order_not_found_means_the_merchant_was_accepted(): void {
		$verdict = Probe::wayforpay(
			new HttpResult(
				200,
				array(
					'reason'     => 'Order Not Found',
					'reasonCode' => 1127,
				)
			)
		);

		$this->assertSame( Probe::GOOD, $verdict['status'] );
	}

	public function test_wayforpay_wrong_signature_needs_attention(): void {
		$verdict = Probe::wayforpay(
			new HttpResult(
				200,
				array(
					'reason'     => 'Invalid signature',
					'reasonCode' => 1113,
				)
			)
		);

		$this->assertSame( Probe::RECOMMENDED, $verdict['status'] );
		$this->assertSame( 'Invalid signature (1113)', $verdict['detail'] );
	}

	public function test_telegram_without_a_bot_only_checks_the_connection(): void {
		$verdict = Probe::telegram( new HttpResult( 302 ), false );

		$this->assertSame( Probe::RECOMMENDED, $verdict['status'] );
	}

	public function test_telegram_bot_name_is_shown(): void {
		$verdict = Probe::telegram(
			new HttpResult(
				200,
				array(
					'ok'     => true,
					'result' => array( 'username' => 'zernyar_orders_bot' ),
				)
			),
			true
		);

		$this->assertSame( Probe::GOOD, $verdict['status'] );
		$this->assertSame( '@zernyar_orders_bot', $verdict['detail'] );
	}

	public function test_telegram_rejected_token_is_critical(): void {
		$verdict = Probe::telegram(
			new HttpResult(
				401,
				array(
					'ok'          => false,
					'description' => 'Unauthorized',
				)
			),
			true
		);

		$this->assertSame( Probe::CRITICAL, $verdict['status'] );
		$this->assertSame( 'Unauthorized', $verdict['detail'] );
	}
}
