<?php
/**
 * Title: Page not found
 * Slug: zernyar/not-found
 * Categories: zernyar
 * Inserter: no
 *
 * @package Zernyar
 */

?>
<!-- wp:group {"className":"zernyar-not-found","layout":{"type":"constrained"}} -->
<div class="wp-block-group zernyar-not-found">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading"><?php esc_html_e( 'This page is out of stock', 'zernyar' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'The link is broken or the page was moved. The coffee is still in the catalog.', 'zernyar' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( zernyar_shop_url() ); ?>"><?php esc_html_e( 'Go to the catalog', 'zernyar' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
