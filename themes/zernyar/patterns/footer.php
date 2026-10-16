<?php
/**
 * Title: Footer
 * Slug: zernyar/footer
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 *
 * @package Zernyar
 */

?>
<!-- wp:group {"tagName":"footer","className":"site-footer","backgroundColor":"contrast","textColor":"base","layout":{"type":"constrained"}} -->
<footer class="wp-block-group site-footer has-base-color has-contrast-background-color has-text-color has-background">
	<!-- wp:columns {"align":"wide","className":"site-footer__columns"} -->
	<div class="wp-block-columns alignwide site-footer__columns">
		<!-- wp:column {"width":"40%"} -->
		<div class="wp-block-column" style="flex-basis:40%">
			<!-- wp:site-title {"level":0,"className":"site-logo"} /-->

			<!-- wp:paragraph {"className":"site-footer__about"} -->
			<p class="site-footer__about"><?php esc_html_e( 'A small roastery that roasts specialty coffee every Tuesday and ships it across Ukraine the same week.', 'zernyar' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":2,"className":"site-footer__heading"} -->
			<h2 class="wp-block-heading site-footer__heading"><?php esc_html_e( 'Store', 'zernyar' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:navigation {"overlayMenu":"never","className":"site-footer__nav","layout":{"type":"flex","orientation":"vertical"}} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:heading {"level":2,"className":"site-footer__heading"} -->
			<h2 class="wp-block-heading site-footer__heading"><?php esc_html_e( 'Contacts', 'zernyar' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Kyiv, Ukraine', 'zernyar' ); ?><br>hello@zernyar.example<br><?php esc_html_e( 'Orders are shipped on weekdays', 'zernyar' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:group {"align":"wide","className":"site-footer__bottom","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide site-footer__bottom">
		<!-- wp:paragraph {"className":"site-footer__demo"} -->
		<p class="site-footer__demo"><?php esc_html_e( 'A demo store for a portfolio: the roastery is made up, payments run in test mode and no order is shipped.', 'zernyar' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph -->
		<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php esc_html_e( 'Zernyar', 'zernyar' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</footer>
<!-- /wp:group -->
