<?php
/**
 * Nova Poshta in the Checkout block.
 *
 * @package UaCommerceKit
 */

namespace UaCommerceKit\NovaPoshta;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationRegistry;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use UaCommerceKit\Phone;
use WC_Order;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the shopper's pick in the session, prices delivery to the picked city
 * and checks the pick again before the order is placed.
 *
 * The browser sends the pick twice: with extensionCartUpdate(), so the rate
 * follows the city, and with the checkout request itself. The session alone is
 * not enough: WooCommerce saves the whole session at the end of each request,
 * so an address update running at the same time can write the pick away.
 * The server does not trust the names it is sent and looks the branch up by reference.
 */
final class Checkout {

	public const STORE_NAMESPACE = 'ua-commerce-kit-np';

	/**
	 * Hooks everything in.
	 */
	public function register(): void {
		if ( did_action( 'woocommerce_blocks_loaded' ) ) {
			$this->extend_store_api();
		} else {
			add_action( 'woocommerce_blocks_loaded', array( $this, 'extend_store_api' ) );
		}
		add_action( 'woocommerce_blocks_checkout_block_registration', array( $this, 'register_integration' ) );
		add_filter( 'woocommerce_cart_shipping_packages', array( $this, 'add_city_to_packages' ) );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( $this, 'update_order' ), 10, 2 );
		add_filter( 'woocommerce_get_country_locale', array( $this, 'country_locale' ) );
	}

	/**
	 * Store API: an update callback for the pick and the pick in the cart response.
	 */
	public function extend_store_api(): void {
		woocommerce_store_api_register_update_callback(
			array(
				'namespace' => self::STORE_NAMESPACE,
				'callback'  => array( $this, 'save_selection' ),
			)
		);
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => self::STORE_NAMESPACE,
				'data_callback'   => static fn (): array => self::selection()->to_array(),
				'schema_callback' => static fn (): array => self::schema( true ),
				'schema_type'     => ARRAY_A,
			)
		);
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CheckoutSchema::IDENTIFIER,
				'namespace'       => self::STORE_NAMESPACE,
				'schema_callback' => static fn (): array => self::schema( false ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Loads the checkout script with the block.
	 *
	 * @param IntegrationRegistry $registry Checkout block integrations.
	 */
	public function register_integration( IntegrationRegistry $registry ): void {
		$registry->register( new BlocksIntegration() );
	}

	/**
	 * Saves a pick sent by the checkout.
	 *
	 * @param array<string, mixed> $data city_ref and city, plus warehouse_ref once a branch is picked.
	 * @throws RouteException When the branch does not belong to the city.
	 */
	public function save_selection( array $data ): void {
		$selection = Selection::from_array( $data );
		if ( $selection->has_warehouse() ) {
			$warehouse = Client::from_settings()->warehouse( $selection->warehouse_ref );
			if ( null !== $warehouse && $warehouse['city_ref'] !== $selection->city_ref ) {
				throw new RouteException( 'uack_np_wrong_city', esc_html__( 'This branch is in another city.', 'ua-commerce-kit' ), 400 );
			}
			if ( null !== $warehouse ) {
				$selection = $selection->with_warehouse( $warehouse );
			}
		}
		WC()->session->set( Selection::SESSION_KEY, $selection->to_array() );
	}

	/**
	 * Adds the picked city to each package, so the rate is recalculated when it changes.
	 *
	 * @param array<int, array<string, mixed>> $packages Shipping packages.
	 * @return array<int, array<string, mixed>>
	 */
	public function add_city_to_packages( array $packages ): array {
		$city = self::selection()->city_ref;
		foreach ( $packages as $index => $package ) {
			$packages[ $index ]['uack_np_city'] = $city;
		}
		return $packages;
	}

	/**
	 * Checks the pick and the phone, then writes the branch into the order.
	 *
	 * @param WC_Order        $order   Order being placed.
	 * @param WP_REST_Request $request Checkout request with the pick in its extensions.
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 * @throws RouteException When the branch or the phone is missing or wrong.
	 */
	public function update_order( WC_Order $order, WP_REST_Request $request ): void {
		if ( ! $order->has_shipping_method( ShippingMethod::ID ) ) {
			return;
		}

		$extensions = (array) $request->get_param( 'extensions' );
		$posted     = Selection::from_array( (array) ( $extensions[ self::STORE_NAMESPACE ] ?? array() ) );
		$selection  = $posted->has_warehouse() ? $posted : self::selection();
		if ( ! $selection->has_warehouse() ) {
			throw new RouteException( 'uack_np_no_branch', esc_html__( 'Choose a Nova Poshta branch or parcel locker for delivery.', 'ua-commerce-kit' ), 400 );
		}
		$warehouse = Client::from_settings()->warehouse( $selection->warehouse_ref );
		if ( null !== $warehouse ) {
			if ( $warehouse['city_ref'] !== $selection->city_ref ) {
				throw new RouteException( 'uack_np_wrong_city', esc_html__( 'The picked branch is in another city. Choose the branch again.', 'ua-commerce-kit' ), 400 );
			}
			$selection = $selection->with_warehouse( $warehouse );
		}

		$phone = Phone::normalize( $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone() );
		if ( null === $phone ) {
			throw new RouteException( 'uack_np_phone', esc_html__( 'Enter a Ukrainian mobile number, for example +380 67 123 45 67. Nova Poshta sends the parcel code to it.', 'ua-commerce-kit' ), 400 );
		}

		OrderDetails::save( $order, $selection, $phone );
	}

	/**
	 * Hides the street, postcode and region fields for Ukraine: the branch replaces them.
	 *
	 * @param array<string, array<string, mixed>> $locale Address fields per country.
	 * @return array<string, array<string, mixed>>
	 */
	public function country_locale( array $locale ): array {
		if ( ! Settings::hide_address() ) {
			return $locale;
		}
		foreach ( array( 'address_1', 'address_2', 'city', 'state', 'postcode', 'company' ) as $field ) {
			$locale['UA'][ $field ] = array_merge(
				(array) ( $locale['UA'][ $field ] ?? array() ),
				array(
					'required' => false,
					'hidden'   => true,
				)
			);
		}
		return $locale;
	}

	/**
	 * The pick saved in the session.
	 */
	public static function selection(): Selection {
		/**
		 * Null outside a shopper request, for example in WP-CLI.
		 *
		 * @var \WC_Session|null $session
		 */
		$session = WC()->session;
		$saved   = null === $session ? null : $session->get( Selection::SESSION_KEY );
		return Selection::from_array( is_array( $saved ) ? $saved : array() );
	}

	/**
	 * Store API schema of the pick: read-only in the cart, sent with the checkout.
	 *
	 * @param bool $in_cart Read-only fields of the cart, or the fields the checkout sends.
	 * @return array<string, array<string, mixed>>
	 */
	private static function schema( bool $in_cart ): array {
		$fields = $in_cart ? array_keys( ( new Selection() )->to_array() ) : array( 'city_ref', 'city', 'warehouse_ref' );
		$schema = array();
		foreach ( $fields as $field ) {
			$schema[ $field ] = array(
				'type'     => 'string',
				'context'  => array( 'view', 'edit' ),
				'readonly' => $in_cart,
				'default'  => '',
			);
		}
		return $schema;
	}
}
