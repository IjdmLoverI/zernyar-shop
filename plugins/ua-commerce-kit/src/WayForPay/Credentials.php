<?php
/**
 * Merchant account and secret in use.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

defined( 'ABSPATH' ) || exit;

/**
 * The public test merchant from the WayForPay docs, or the store's own account.
 */
final class Credentials {

	public const TEST_ACCOUNT = 'test_merch_n1';
	public const TEST_SECRET  = 'flk3409refn54t54t*FNJRET';

	/**
	 * Account and secret.
	 *
	 * @param string $account Merchant account.
	 * @param string $secret  Merchant secret key.
	 */
	public function __construct(
		public readonly string $account,
		public readonly string $secret,
	) {}

	/**
	 * The shared test merchant: payments are not charged.
	 */
	public static function test(): self {
		return new self( self::TEST_ACCOUNT, self::TEST_SECRET );
	}

	/**
	 * Credentials from the gateway settings. Constants UACK_WFP_ACCOUNT and
	 * UACK_WFP_SECRET win; test mode or a missing secret uses the test merchant.
	 *
	 * @param array<string, mixed> $settings Gateway settings.
	 */
	public static function from_settings( array $settings ): self {
		if ( 'no' !== ( $settings['test_mode'] ?? 'yes' ) ) {
			return self::test();
		}
		$account = defined( 'UACK_WFP_ACCOUNT' ) && is_string( UACK_WFP_ACCOUNT ) ? UACK_WFP_ACCOUNT : trim( (string) ( $settings['merchant_account'] ?? '' ) );
		$secret  = defined( 'UACK_WFP_SECRET' ) && is_string( UACK_WFP_SECRET ) ? UACK_WFP_SECRET : trim( (string) ( $settings['secret_key'] ?? '' ) );
		return '' === $account || '' === $secret ? self::test() : new self( $account, $secret );
	}

	/**
	 * Whether these are the public test credentials.
	 */
	public function is_test(): bool {
		return self::TEST_ACCOUNT === $this->account;
	}
}
