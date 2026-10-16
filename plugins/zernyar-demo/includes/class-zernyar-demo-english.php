<?php
/**
 * English names for data that never reaches TranslatePress as page text.
 *
 * @package ZernyarDemo
 */

defined( 'ABSPATH' ) || exit;

/**
 * TranslatePress translates the HTML of a page. Some demo texts reach the shopper
 * another way: the product filters rebuild their labels from JSON, and the
 * Checkout block joins attribute labels and values into one line. On the English
 * version these filters swap the Ukrainian names for the ones in
 * data/translations-en.json before WooCommerce uses them.
 */
final class Zernyar_Demo_English {

	/**
	 * Plain Ukrainian text => English.
	 *
	 * @var array<string, string>|null
	 */
	private static ?array $map = null;

	/**
	 * Hooks the filters in.
	 */
	public static function register(): void {
		add_filter( 'get_term', array( self::class, 'term' ) );
		add_filter( 'woocommerce_attribute_label', array( self::class, 'text' ) );
		add_filter( 'woocommerce_variation_option_name', array( self::class, 'text' ) );
		add_filter( 'woocommerce_product_get_name', array( self::class, 'text' ) );
		add_filter( 'woocommerce_product_title', array( self::class, 'text' ) );
		add_filter( 'woocommerce_product_variation_get_name', array( self::class, 'variation_name' ) );
		add_filter( 'woocommerce_gateway_title', array( self::class, 'text' ) );
		add_filter( 'woocommerce_gateway_description', array( self::class, 'text' ) );
		add_filter( 'woocommerce_package_rates', array( self::class, 'rates' ) );
	}

	/**
	 * Term name and description.
	 *
	 * @param mixed $term Term.
	 * @return mixed
	 */
	public static function term( $term ) {
		if ( $term instanceof WP_Term && self::active() ) {
			$term->name        = self::text( $term->name );
			$term->description = self::text( $term->description );
		}
		return $term;
	}

	/**
	 * "Ефіопія Іргачеффе - 250 г, У зернах" part by part.
	 *
	 * @param string $name Variation name.
	 */
	public static function variation_name( $name ): string {
		$name = (string) $name;
		if ( ! self::active() || ! str_contains( $name, ' - ' ) ) {
			return self::text( $name );
		}
		list( $product, $options ) = explode( ' - ', $name, 2 );
		return self::text( $product ) . ' - ' . implode( ', ', array_map( array( self::class, 'text' ), explode( ', ', $options ) ) );
	}

	/**
	 * Labels of the shipping rates.
	 *
	 * @param array<string, mixed> $rates Rates.
	 * @return array<string, mixed>
	 */
	public static function rates( $rates ) {
		foreach ( (array) $rates as $rate ) {
			if ( $rate instanceof WC_Shipping_Rate ) {
				$rate->set_label( self::text( $rate->get_label() ) );
			}
		}
		return $rates;
	}

	/**
	 * English for a text on the English version, the text itself otherwise.
	 *
	 * @param mixed $text Text.
	 */
	public static function text( $text ): string {
		$text = (string) $text;
		if ( '' === $text || ! self::active() ) {
			return $text;
		}
		return self::map()[ self::plain( $text ) ] ?? $text;
	}

	/**
	 * Whether the request is for the English version.
	 */
	private static function active(): bool {
		return ! is_admin() && str_starts_with( determine_locale(), 'en' );
	}

	/**
	 * The translations, keyed by plain text.
	 *
	 * @return array<string, string>
	 */
	private static function map(): array {
		if ( null === self::$map ) {
			$json      = (string) file_get_contents( dirname( ZERNYAR_DEMO_CATALOG ) . '/translations-en.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
			self::$map = array();
			foreach ( (array) json_decode( $json, true ) as $uk => $en ) {
				if ( ! str_starts_with( (string) $uk, '&#47;' ) ) {
					self::$map[ self::plain( (string) $uk ) ] = html_entity_decode( (string) $en, ENT_QUOTES, 'UTF-8' );
				}
			}
		}
		return self::$map;
	}

	/**
	 * Text without entities, with plain apostrophes and spaces.
	 *
	 * @param string $text Text.
	 */
	private static function plain( string $text ): string {
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		return trim( str_replace( array( "\u{00A0}", '’' ), array( ' ', "'" ), $text ) );
	}
}
