<?php
/**
 * Title: Hero
 * Slug: zernyar/hero
 * Categories: zernyar, banner
 *
 * @package Zernyar
 */

?>
<!-- wp:group {"align":"full","className":"zernyar-hero","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull zernyar-hero">
	<!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"zernyar-hero__columns"} -->
	<div class="wp-block-columns alignwide are-vertically-aligned-center zernyar-hero__columns">
		<!-- wp:column {"verticalAlignment":"center","width":"44%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:44%">
			<!-- wp:paragraph {"className":"zernyar-eyebrow"} -->
			<p class="zernyar-eyebrow"><?php esc_html_e( 'Specialty coffee from Kyiv', 'zernyar' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"className":"zernyar-hero__title"} -->
			<h1 class="wp-block-heading zernyar-hero__title"><?php esc_html_e( 'Coffee roasted this week', 'zernyar' ); ?></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"className":"zernyar-lead"} -->
			<p class="zernyar-lead"><?php esc_html_e( 'Single origins and blends for espresso, filter and the cezve. We grind for your brewer and ship by Nova Poshta.', 'zernyar' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons -->
			<div class="wp-block-buttons">
				<!-- wp:button -->
				<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( zernyar_shop_url() ); ?>"><?php esc_html_e( 'Choose coffee', 'zernyar' ); ?></a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#brew"><?php esc_html_e( 'How to brew', 'zernyar' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"56%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:56%">
			<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"zernyar-hero__image"} -->
			<figure class="wp-block-image size-full zernyar-hero__image"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/hero.webp' ) ); ?>" alt="<?php esc_attr_e( 'A roaster tips freshly roasted beans into the cooling tray', 'zernyar' ); ?>" width="1600" height="1068"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
