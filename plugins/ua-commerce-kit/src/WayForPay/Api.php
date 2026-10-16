<?php
/**
 * WayForPay API calls made from the store.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

use UaCommerceKit\Http;

defined( 'ABSPATH' ) || exit;

/**
 * CHECK_STATUS and REFUND.
 */
final class Api {

	public const URL = 'https://api.wayforpay.com/api';

	/**
	 * API for a merchant.
	 *
	 * @param Credentials $credentials Merchant.
	 */
	public function __construct( private readonly Credentials $credentials ) {}

	/**
	 * Status of a payment, or null when WayForPay cannot be reached or the answer is not signed by it.
	 *
	 * @param string $reference Order reference.
	 */
	public function check_status( string $reference ): ?Answer {
		$result = Http::post_json(
			self::URL,
			array(
				'transactionType'   => 'CHECK_STATUS',
				'merchantAccount'   => $this->credentials->account,
				'orderReference'    => $reference,
				'merchantSignature' => Signature::sign( array( $this->credentials->account, $reference ), $this->credentials->secret ),
				'apiVersion'        => 1,
			),
			15
		);
		return null === $result->json ? null : Answer::verified( $result->json, $this->credentials );
	}

	/**
	 * Refunds part or all of a payment.
	 *
	 * @param string $reference Order reference.
	 * @param float  $amount    Amount to refund.
	 * @param string $currency  Currency.
	 * @param string $comment   Reason shown in the merchant account.
	 * @return array{ok: bool, status: string, reason: string}
	 */
	public function refund( string $reference, float $amount, string $currency, string $comment ): array {
		$money  = Purchase::money( $amount );
		$result = Http::post_json(
			self::URL,
			array(
				'transactionType'   => 'REFUND',
				'merchantAccount'   => $this->credentials->account,
				'orderReference'    => $reference,
				'amount'            => $money,
				'currency'          => $currency,
				'comment'           => '' === $comment ? 'Refund' : $comment,
				'merchantSignature' => Signature::sign( array( $this->credentials->account, $reference, $money, $currency ), $this->credentials->secret ),
				'apiVersion'        => 1,
			),
			20
		);
		if ( ! $result->reached() ) {
			return array(
				'ok'     => false,
				'status' => '',
				'reason' => (string) $result->error,
			);
		}

		$json   = $result->json ?? array();
		$status = (string) ( $json['transactionStatus'] ?? '' );
		return array(
			'ok'     => in_array( $status, array( 'Refunded', 'Voided', 'RefundInProcessing' ), true ),
			'status' => $status,
			'reason' => trim( (string) ( $json['reason'] ?? '' ) . ' (' . (string) ( $json['reasonCode'] ?? '' ) . ')' ),
		);
	}
}
