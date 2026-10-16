<?php
/**
 * Connection checks in Tools → Site Health.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\Health;

use UaCommerceKit\Http;
use UaCommerceKit\NovaPoshta\Client as NovaPoshtaClient;
use UaCommerceKit\Plugin;
use UaCommerceKit\Telegram\Config as TelegramConfig;
use UaCommerceKit\WayForPay\Gateway;
use UaCommerceKit\WayForPay\Signature;

defined( 'ABSPATH' ) || exit;

/**
 * Asks each service a harmless question from this server.
 *
 * Free hosting often blocks outgoing requests, and every module depends on them.
 */
final class SiteHealth {

	/**
	 * Adds the checks.
	 */
	public function register(): void {
		add_filter( 'site_status_tests', array( $this, 'add_tests' ) );
	}

	/**
	 * Registers one direct test per enabled module.
	 *
	 * @param array<string, array<string, mixed>> $tests Site Health tests.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_tests( array $tests ): array {
		$checks = array(
			'nova_poshta' => array( __( 'Nova Poshta API', 'ua-commerce-kit' ), array( $this, 'test_nova_poshta' ) ),
			'wayforpay'   => array( __( 'WayForPay API', 'ua-commerce-kit' ), array( $this, 'test_wayforpay' ) ),
			'telegram'    => array( __( 'Telegram Bot API', 'ua-commerce-kit' ), array( $this, 'test_telegram' ) ),
		);
		foreach ( $checks as $module => list( $label, $callback ) ) {
			if ( Plugin::module_enabled( $module ) ) {
				$tests['direct'][ 'uack_' . $module ] = array(
					'label' => $label,
					'test'  => $callback,
				);
			}
		}
		return $tests;
	}

	/**
	 * Searches for a city.
	 *
	 * @return array<string, mixed>
	 */
	public function test_nova_poshta(): array {
		$result  = ( new NovaPoshtaClient() )->call(
			'AddressGeneral',
			'searchSettlements',
			array(
				'CityName' => 'Київ',
				'Limit'    => '1',
				'Page'     => '1',
			)
		);
		$verdict = Probe::nova_poshta( $result );
		return $this->report(
			'uack_nova_poshta',
			$verdict,
			array(
				Probe::GOOD     => __( 'Nova Poshta API can be reached', 'ua-commerce-kit' ),
				Probe::CRITICAL => __( 'Nova Poshta API cannot be reached', 'ua-commerce-kit' ),
			),
			__( 'Checkout looks up cities and branches on the Nova Poshta API from this server.', 'ua-commerce-kit' )
		);
	}

	/**
	 * Asks for the status of an order that does not exist.
	 *
	 * @return array<string, mixed>
	 */
	public function test_wayforpay(): array {
		$credentials = Gateway::credentials();
		$reference   = 'health-' . time();
		$result      = Http::post_json(
			'https://api.wayforpay.com/api',
			array(
				'transactionType'   => 'CHECK_STATUS',
				'merchantAccount'   => $credentials->account,
				'orderReference'    => $reference,
				'merchantSignature' => Signature::sign( array( $credentials->account, $reference ), $credentials->secret ),
				'apiVersion'        => 1,
			),
			8
		);
		$verdict     = Probe::wayforpay( $result );
		return $this->report(
			'uack_wayforpay',
			$verdict,
			array(
				Probe::GOOD        => __( 'WayForPay API can be reached', 'ua-commerce-kit' ),
				Probe::RECOMMENDED => __( 'WayForPay API refused the merchant account', 'ua-commerce-kit' ),
				Probe::CRITICAL    => __( 'WayForPay API cannot be reached', 'ua-commerce-kit' ),
			),
			__( 'After a payment the store asks WayForPay for its status from this server.', 'ua-commerce-kit' )
		);
	}

	/**
	 * Calls getMe for the configured bot, or opens the API root.
	 *
	 * @return array<string, mixed>
	 */
	public function test_telegram(): array {
		$token   = TelegramConfig::token();
		$result  = '' === $token
			? Http::get( 'https://api.telegram.org/', 8 )
			: Http::get( 'https://api.telegram.org/bot' . $token . '/getMe', 8 );
		$verdict = Probe::telegram( $result, '' !== $token );
		return $this->report(
			'uack_telegram',
			$verdict,
			array(
				Probe::GOOD        => __( 'Telegram bot is connected', 'ua-commerce-kit' ),
				Probe::RECOMMENDED => __( 'Telegram API can be reached, but no bot is set up', 'ua-commerce-kit' ),
				Probe::CRITICAL    => __( 'Telegram bot cannot be reached', 'ua-commerce-kit' ),
			),
			__( 'Order notifications are sent to Telegram from this server.', 'ua-commerce-kit' )
		);
	}

	/**
	 * Builds a Site Health result.
	 *
	 * @param string                                $test    Test ID.
	 * @param array{status: string, detail: string} $verdict Probe verdict.
	 * @param array<string, string>                 $labels  Label per status.
	 * @param string                                $why     Why the check matters.
	 * @return array<string, mixed>
	 */
	private function report( string $test, array $verdict, array $labels, string $why ): array {
		$description = '<p>' . esc_html( $why ) . '</p>';
		if ( '' !== $verdict['detail'] ) {
			$description .= '<p><code>' . esc_html( $verdict['detail'] ) . '</code></p>';
		}
		return array(
			'test'        => $test,
			'status'      => $verdict['status'],
			'label'       => $labels[ $verdict['status'] ] ?? $labels[ Probe::CRITICAL ],
			'badge'       => array(
				'label' => __( 'UA Commerce Kit', 'ua-commerce-kit' ),
				'color' => Probe::GOOD === $verdict['status'] ? 'green' : ( Probe::CRITICAL === $verdict['status'] ? 'red' : 'orange' ),
			),
			'description' => $description,
			'actions'     => '',
		);
	}
}
