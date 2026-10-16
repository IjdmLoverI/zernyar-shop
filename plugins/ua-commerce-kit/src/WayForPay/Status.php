<?php
/**
 * What a WayForPay transaction status means for the order.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

defined( 'ABSPATH' ) || exit;

/**
 * Maps transactionStatus to paid, pending, failed or refunded.
 */
final class Status {

	public const PAID     = 'paid';
	public const PENDING  = 'pending';
	public const FAILED   = 'failed';
	public const REFUNDED = 'refunded';
	public const UNKNOWN  = 'unknown';

	private const MAP = array(
		'Approved'            => self::PAID,
		'InProcessing'        => self::PENDING,
		'Pending'             => self::PENDING,
		'WaitingAuthComplete' => self::PENDING,
		'Declined'            => self::FAILED,
		'Expired'             => self::FAILED,
		'Refunded'            => self::REFUNDED,
		'Voided'              => self::REFUNDED,
		'RefundInProcessing'  => self::REFUNDED,
	);

	/**
	 * State of the order for a transaction status.
	 *
	 * @param string $transaction_status Status from WayForPay.
	 */
	public static function of( string $transaction_status ): string {
		return self::MAP[ $transaction_status ] ?? self::UNKNOWN;
	}
}
