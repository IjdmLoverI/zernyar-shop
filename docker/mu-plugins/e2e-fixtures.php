<?php
/**
 * Plugin Name: Zernyar end-to-end fixtures
 * Description: Answers the Nova Poshta, WayForPay and Telegram APIs from fixtures, and shows order state, for requests sent by the end-to-end tests. Local Docker only.
 *
 * The tests send an X-Uack-E2E header with every request; other requests,
 * including the developer's own, reach the real services.
 *
 * @package Zernyar
 */

defined( 'ABSPATH' ) || exit;

if ( 'local' !== wp_get_environment_type() || empty( $_SERVER['HTTP_X_UACK_E2E'] ) ) {
	return;
}

const UACK_E2E_KYIV = '8d5a980d-391c-11dd-90d9-001a92567626';
const UACK_E2E_BILA = 'db5c88ce-391c-11dd-90d9-001a92567626';

/**
 * Cities known to the fixtures.
 *
 * @return list<array<string, mixed>>
 */
function uack_e2e_cities(): array {
	return array(
		array(
			'Present'         => 'м. Київ, Київська обл.',
			'MainDescription' => 'Київ',
			'DeliveryCity'    => UACK_E2E_KYIV,
			'Warehouses'      => 3000,
		),
		array(
			'Present'         => 'м. Біла Церква, Білоцерківський р-н, Київська обл.',
			'MainDescription' => 'Біла Церква',
			'DeliveryCity'    => UACK_E2E_BILA,
			'Warehouses'      => 897,
		),
	);
}

/**
 * Branches known to the fixtures.
 *
 * @return list<array<string, string>>
 */
function uack_e2e_warehouses(): array {
	$branch = static fn ( string $ref, string $city, string $number, string $category, string $description, string $postcode ): array => array(
		'Ref'                 => $ref,
		'CityRef'             => $city,
		'Number'              => $number,
		'CategoryOfWarehouse' => $category,
		'WarehouseStatus'     => 'Working',
		'Description'         => $description,
		'PostalCodeUA'        => $postcode,
	);
	return array(
		$branch( 'aaaaaaaa-0000-4000-8000-000000000001', UACK_E2E_BILA, '1', 'Branch', 'Відділення №1 (до 200 кг): вул. Київська, 33', '09109' ),
		$branch( '0d545f5e-e1c2-11e3-8c4a-0050568002cf', UACK_E2E_BILA, '3', 'Branch', 'Відділення №3 (до 30 кг на одне місце): вул. Чорних Запорожців, 47/1', '09108' ),
		$branch( 'aaaaaaaa-0000-4000-8000-000000031544', UACK_E2E_BILA, '31544', 'Postomat', 'Поштомат №31544: вул. Ярослава Мудрого, 40', '09117' ),
		$branch( 'bbbbbbbb-0000-4000-8000-000000000025', UACK_E2E_KYIV, '25', 'Branch', 'Відділення №25: вул. Левка Лук\'яненка, 21', '04212' ),
	);
}

/**
 * Nova Poshta answer for a request body.
 *
 * @param array<string, mixed> $body Request.
 * @return array<string, mixed>
 */
function uack_e2e_nova_poshta( array $body ): array {
	$props = (array) ( $body['methodProperties'] ?? array() );
	switch ( $body['calledMethod'] ?? '' ) {
		case 'searchSettlements':
			$query  = mb_strtolower( (string) ( $props['CityName'] ?? '' ) );
			$cities = array_values( array_filter( uack_e2e_cities(), static fn ( array $city ): bool => str_starts_with( mb_strtolower( $city['MainDescription'] ), $query ) ) );
			return array(
				'success' => true,
				'data'    => array( array( 'Addresses' => $cities ) ),
			);
		case 'getWarehouses':
			$found = array_filter(
				uack_e2e_warehouses(),
				static function ( array $branch ) use ( $props ): bool {
					if ( isset( $props['Ref'] ) ) {
						return $branch['Ref'] === $props['Ref'];
					}
					if ( ( $props['CityRef'] ?? '' ) !== $branch['CityRef'] ) {
						return false;
					}
					if ( isset( $props['WarehouseId'] ) ) {
						return $branch['Number'] === $props['WarehouseId'];
					}
					return ! isset( $props['FindByString'] ) || str_contains( mb_strtolower( $branch['Description'] ), mb_strtolower( (string) $props['FindByString'] ) );
				}
			);
			return array(
				'success' => true,
				'data'    => array_values( $found ),
			);
		case 'getDocumentPrice':
			return array(
				'success' => true,
				'data'    => array( array( 'Cost' => 90 ) ),
			);
	}
	return array(
		'success' => false,
		'errors'  => array( 'Unknown method in the fixtures' ),
	);
}

/**
 * WayForPay answer: CHECK_STATUS follows the X-Uack-E2E-WayForPay header
 * (approved, pending or declined), REFUND always succeeds.
 *
 * @param array<string, mixed> $body Request.
 * @return array<string, mixed>
 */
function uack_e2e_wayforpay( array $body ): array {
	$reference = (string) ( $body['orderReference'] ?? '' );
	if ( 'REFUND' === ( $body['transactionType'] ?? '' ) ) {
		return array(
			'merchantAccount'   => $body['merchantAccount'],
			'orderReference'    => $reference,
			'transactionStatus' => 'Refunded',
			'reason'            => 'Ok',
			'reasonCode'        => 1100,
		);
	}

	$scenario = sanitize_key( wp_unslash( $_SERVER['HTTP_X_UACK_E2E_WAYFORPAY'] ?? 'declined' ) );
	$statuses = array(
		'approved' => array( 'Approved', 1100, 'Ok' ),
		'pending'  => array( 'InProcessing', 1134, 'In processing' ),
		'declined' => array( 'Declined', 1124, 'Cardholder session expired' ),
	);
	list( $status, $code, $reason ) = $statuses[ $scenario ] ?? $statuses['declined'];

	$order  = UaCommerceKit\WayForPay\Payments::order_for( $reference );
	$answer = array(
		'merchantAccount'   => (string) $body['merchantAccount'],
		'orderReference'    => $reference,
		'amount'            => $order ? (float) $order->get_total() : 0,
		'currency'          => $order ? $order->get_currency() : 'UAH',
		'authCode'          => 'approved' === $scenario ? '541963' : '',
		'cardPan'           => '41****8217',
		'transactionStatus' => $status,
		'reasonCode'        => $code,
		'reason'            => $reason,
	);
	$answer['merchantSignature'] = UaCommerceKit\WayForPay\Signature::sign(
		UaCommerceKit\WayForPay\Signature::result_fields( $answer ),
		UaCommerceKit\WayForPay\Credentials::TEST_SECRET
	);
	return $answer;
}

/**
 * Fake HTTP response.
 *
 * @param array<string, mixed> $json Body.
 * @return array<string, mixed>
 */
function uack_e2e_reply( array $json ): array {
	return array(
		'headers'  => array( 'content-type' => 'application/json' ),
		'body'     => (string) wp_json_encode( $json ),
		'response' => array(
			'code'    => 200,
			'message' => 'OK',
		),
		'cookies'  => array(),
	);
}

add_filter(
	'pre_http_request',
	static function ( $pre, $args, $url ) {
		$body = json_decode( (string) ( $args['body'] ?? '' ), true );
		$body = is_array( $body ) ? $body : array();
		switch ( wp_parse_url( $url, PHP_URL_HOST ) ) {
			case 'api.novaposhta.ua':
				return uack_e2e_reply( uack_e2e_nova_poshta( $body ) );
			case 'api.wayforpay.com':
				return uack_e2e_reply( uack_e2e_wayforpay( $body ) );
			case 'api.telegram.org':
				$sent   = (array) get_option( 'uack_e2e_telegram', array() );
				$sent[] = array(
					'chat_id' => $body['chat_id'] ?? '',
					'text'    => $body['text'] ?? '',
				);
				update_option( 'uack_e2e_telegram', $sent, false );
				return uack_e2e_reply(
					array(
						'ok'     => true,
						'result' => array( 'username' => 'zernyar_e2e_bot' ),
					)
				);
		}
		return $pre;
	},
	10,
	3
);

add_filter( 'uack_np_cache_ttl', '__return_zero' );
add_filter( 'uack_np_rate_limit', static fn (): int => PHP_INT_MAX );

add_filter(
	'pre_option_uack_telegram_token',
	static fn (): string => '123456:e2e-token'
);
add_filter(
	'pre_option_uack_telegram_chat_id',
	static fn (): string => '1000'
);

add_action(
	'rest_api_init',
	static function (): void {
		register_rest_route(
			'uack-e2e/v1',
			'/order/(?P<id>\d+)',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => static function ( WP_REST_Request $request ) {
					$order = wc_get_order( (int) $request['id'] );
					if ( ! $order instanceof WC_Order ) {
						return new WP_Error( 'not_found', 'No such order', array( 'status' => 404 ) );
					}
					return array(
						'status'         => $order->get_status(),
						'paid'           => $order->is_paid(),
						'total'          => (float) $order->get_total(),
						'payment_method' => $order->get_payment_method(),
						'transaction_id' => $order->get_transaction_id(),
						'reference'      => $order->get_meta( '_uack_wfp_reference' ),
						'phone'          => $order->get_billing_phone(),
						'shipping'       => array(
							'city'      => $order->get_shipping_city(),
							'address_1' => $order->get_shipping_address_1(),
							'postcode'  => $order->get_shipping_postcode(),
						),
						'nova_poshta'    => $order->get_meta( '_uack_np' ),
						'notes'          => array_map( static fn ( $note ) => wp_strip_all_tags( $note->content ), wc_get_order_notes( array( 'order_id' => $order->get_id() ) ) ),
					);
				},
			)
		);
		register_rest_route(
			'uack-e2e/v1',
			'/telegram',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => '__return_true',
					'callback'            => static fn () => (array) get_option( 'uack_e2e_telegram', array() ),
				),
				array(
					'methods'             => 'DELETE',
					'permission_callback' => '__return_true',
					'callback'            => static fn () => delete_option( 'uack_e2e_telegram' ),
				),
			)
		);
	}
);
