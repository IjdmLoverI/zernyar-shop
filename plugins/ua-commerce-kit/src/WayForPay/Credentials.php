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
}
