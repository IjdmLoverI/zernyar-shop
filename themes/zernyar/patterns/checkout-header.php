<?php
/**
 * Title: Checkout header
 * Slug: zernyar/checkout-header
 * Categories: header
 * Block Types: core/template-part/checkout-header
 * Inserter: no
 *
 * @package Zernyar
 */

$zernyar_cart_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
?>
<!-- wp:group {"tagName":"header","className":"site-header site-header--checkout","layout":{"type":"constrained"}} -->
<header class="wp-block-group site-header site-header--checkout">
	<!-- wp:group {"align":"wide","className":"site-header__inner","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide site-header__inner">
		<!-- wp:site-title {"level":0,"className":"site-logo"} /-->

		<!-- wp:paragraph {"className":"site-header__back"} -->
		<p class="site-header__back"><a href="<?php echo esc_url( $zernyar_cart_url ); ?>"><?php esc_html_e( 'Back to cart', 'zernyar' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
