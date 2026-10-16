<?php
/**
 * Zernyar theme setup.
 *
 * @package Zernyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Translations, WooCommerce image sizes and the editor stylesheet.
 */
function zernyar_setup(): void {
	load_theme_textdomain( 'zernyar', get_template_directory() . '/languages' );

	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 960,
		)
	);
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );
}
add_action( 'after_setup_theme', 'zernyar_setup' );

/**
 * Styles that theme.json cannot express.
 */
function zernyar_enqueue_styles(): void {
	$path = get_theme_file_path( 'assets/css/theme.css' );
	wp_enqueue_style( 'zernyar', get_theme_file_uri( 'assets/css/theme.css' ), array(), (string) filemtime( $path ) );
}
add_action( 'wp_enqueue_scripts', 'zernyar_enqueue_styles' );

/**
 * Preloads the two font files the first screen needs in the current language.
 */
function zernyar_preload_fonts(): void {
	$subset = str_starts_with( determine_locale(), 'uk' ) ? 'cyrillic' : 'latin';
	foreach ( array( 'manrope', 'lora' ) as $family ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( get_theme_file_uri( "assets/fonts/{$family}-{$subset}.woff2" ) )
		);
	}
}
add_action( 'wp_head', 'zernyar_preload_fonts', 1 );

/**
 * Pattern category for the theme's sections.
 */
function zernyar_register_pattern_category(): void {
	register_block_pattern_category( 'zernyar', array( 'label' => __( 'Zernyar', 'zernyar' ) ) );
}
add_action( 'init', 'zernyar_register_pattern_category' );

/**
 * Link to a product category, or to the shop when the category does not exist yet.
 *
 * @param string $slug Category slug.
 */
function zernyar_category_url( string $slug ): string {
	$link = get_term_link( $slug, 'product_cat' );
	return is_wp_error( $link ) ? zernyar_shop_url() : $link;
}

/**
 * Link to the shop page.
 */
function zernyar_shop_url(): string {
	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}

/**
 * Shows "from 320 ₴" instead of a price range for coffee sold in several weights.
 *
 * @param string     $html    Price HTML.
 * @param WC_Product $product Variable product.
 */
function zernyar_variable_price_html( string $html, $product ): string {
	if ( ! $product instanceof WC_Product_Variable ) {
		return $html;
	}
	$min = (float) $product->get_variation_price( 'min', true );
	$max = (float) $product->get_variation_price( 'max', true );
	if ( $min === $max ) {
		return $html;
	}
	/* translators: %s: the lowest price. */
	return sprintf( esc_html__( 'from %s', 'zernyar' ), wc_price( $min ) );
}
add_filter( 'woocommerce_variable_price_html', 'zernyar_variable_price_html', 10, 2 );
