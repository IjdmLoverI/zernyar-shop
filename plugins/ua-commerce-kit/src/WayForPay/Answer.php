<?php
/**
 * A payment result from WayForPay.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

defined( 'ABSPATH' ) || exit;

/**
 * The service callback and the CHECK_STATUS answer share fields and signature.
 */
final class Answer {

	/**
	 * A checked answer.
	 *
	 * @param string $reference Order reference.
	 * @param string $state     Status::PAID, PENDING, FAILED, REFUNDED or UNKNOWN.
	 * @param string $status    Raw transactionStatus.
	 * @param float  $amount    Amount.
	 * @param string $currency  Currency.
	 * @param string $card      Masked card number.
	 * @param string $auth_code   Bank authorization code.
	 * @param string $reason      Reason text with its code.
	 * @param int    $reason_code Reason code; 1127 means WayForPay has no such order.
	 */
	public function __construct(
		public readonly string $reference,
		public readonly string $state,
		public readonly string $status,
		public readonly float $amount,
		public readonly string $currency,
		public readonly string $card,
		public readonly string $auth_code,
		public readonly string $reason,
		public readonly int $reason_code = 0,
	) {}

	/**
	 * Whether WayForPay has never seen this order: the shopper left before paying.
	 */
	public function not_found(): bool {
		return 1127 === $this->reason_code;
	}

	/**
	 * Reads an answer if it comes from this merchant and its signature is right.
	 *
	 * @param array<string, mixed> $data        Decoded JSON.
	 * @param Credentials          $credentials Merchant.
	 */
	public static function verified( array $data, Credentials $credentials ): ?self {
		$signature = isset( $data['merchantSignature'] ) && is_string( $data['merchantSignature'] ) ? $data['merchantSignature'] : '';
		if (
			( $data['merchantAccount'] ?? null ) !== $credentials->account
			|| '' === $signature
			|| ! Signature::matches( Signature::result_fields( $data ), $credentials->secret, $signature )
		) {
			return null;
		}

		$text = static fn ( string $key ): string => isset( $data[ $key ] ) && is_scalar( $data[ $key ] ) ? (string) $data[ $key ] : '';
		return new self(
			$text( 'orderReference' ),
			Status::of( $text( 'transactionStatus' ) ),
			$text( 'transactionStatus' ),
			(float) $text( 'amount' ),
			$text( 'currency' ),
			$text( 'cardPan' ),
			$text( 'authCode' ),
			trim( $text( 'reason' ) . ' (' . $text( 'reasonCode' ) . ')' ),
			(int) $text( 'reasonCode' ),
		);
	}

	/**
	 * Whether the answer is for this amount and currency.
	 *
	 * @param float  $total    Order total.
	 * @param string $currency Order currency.
	 */
	public function matches( float $total, string $currency ): bool {
		return abs( $this->amount - $total ) < 0.005 && strtoupper( $this->currency ) === strtoupper( $currency );
	}

	/**
	 * The reply WayForPay expects from the service URL.
	 *
	 * @param string      $reference Order reference.
	 * @param Credentials $credentials Merchant.
	 * @param int         $time      Unix time.
	 * @return array{orderReference: string, status: string, time: int, signature: string}
	 */
	public static function accept( string $reference, Credentials $credentials, int $time ): array {
		return array(
			'orderReference' => $reference,
			'status'         => 'accept',
			'time'           => $time,
			'signature'      => Signature::sign( array( $reference, 'accept', $time ), $credentials->secret ),
		);
	}
}
