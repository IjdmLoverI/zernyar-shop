<?php
/**
 * Fields of the WayForPay payment form.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\WayForPay;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the signed form posted to secure.wayforpay.com/pay.
 *
 * @phpstan-type Item array{name: string, price: float, count: int}
 */
final class Purchase {

	public const URL = 'https://secure.wayforpay.com/pay';

	/**
	 * Form fields in the order WayForPay documents them.
	 *
	 * @param Credentials           $credentials Merchant.
	 * @param string                $domain      Store domain.
	 * @param string                $reference   Unique order reference.
	 * @param int                   $date        Order date, Unix time.
	 * @param float                 $amount      Order total.
	 * @param string                $currency    ISO code.
	 * @param array                 $items       Lines of the order.
	 * @param array<string, string> $extra       Client data, language and URLs, sent unsigned.
	 * @phpstan-param list<Item> $items
	 * @return array<string, string|list<string>>
	 */
	public static function fields( Credentials $credentials, string $domain, string $reference, int $date, float $amount, string $currency, array $items, array $extra ): array {
		$items  = self::matching_items( $items, $amount, $reference );
		$names  = array_map( static fn ( array $item ): string => $item['name'], $items );
		$counts = array_map( static fn ( array $item ): string => (string) $item['count'], $items );
		$prices = array_map( static fn ( array $item ): string => self::money( $item['price'] ), $items );
		$total  = self::money( $amount );

		$signature = Signature::sign(
			array_merge( array( $credentials->account, $domain, $reference, (string) $date, $total, $currency ), $names, $counts, $prices ),
			$credentials->secret
		);

		return array_merge(
			array(
				'merchantAccount'               => $credentials->account,
				'merchantAuthType'              => 'SimpleSignature',
				'merchantDomainName'            => $domain,
				'merchantTransactionSecureType' => 'AUTO',
				'merchantSignature'             => $signature,
				'orderReference'                => $reference,
				'orderDate'                     => (string) $date,
				'amount'                        => $total,
				'currency'                      => $currency,
				'productName'                   => $names,
				'productPrice'                  => $prices,
				'productCount'                  => $counts,
			),
			$extra
		);
	}

	/**
	 * Amount as WayForPay expects it: dot, two decimals.
	 *
	 * @param float $value Amount.
	 */
	public static function money( float $value ): string {
		return number_format( $value, 2, '.', '' );
	}

	/**
	 * The order lines, or one line for the whole order when discounts or rounding
	 * make the lines add up to another total.
	 *
	 * @param array  $items     Lines.
	 * @param float  $amount    Order total.
	 * @param string $reference Order reference, for the single line's name.
	 * @phpstan-param list<Item> $items
	 * @return list<Item>
	 */
	private static function matching_items( array $items, float $amount, string $reference ): array {
		$sum = 0.0;
		foreach ( $items as $item ) {
			$sum += round( $item['price'], 2 ) * $item['count'];
		}
		if ( $items && abs( $sum - $amount ) < 0.005 ) {
			return $items;
		}
		return array(
			array(
				'name'  => $reference,
				'price' => $amount,
				'count' => 1,
			),
		);
	}
}
