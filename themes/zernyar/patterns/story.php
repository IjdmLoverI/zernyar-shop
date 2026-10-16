<?php
/**
 * Title: Roastery story
 * Slug: zernyar/story
 * Categories: zernyar, about
 *
 * @package Zernyar
 */

?>
<!-- wp:group {"align":"wide","className":"zernyar-section zernyar-story","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide zernyar-section zernyar-story">
	<!-- wp:columns {"verticalAlignment":"center"} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
			<!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"zernyar-story__image"} -->
			<figure class="wp-block-image size-large zernyar-story__image"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/story.webp' ) ); ?>" alt="<?php esc_attr_e( 'A handful of freshly roasted beans over the roaster tray', 'zernyar' ); ?>" width="1100" height="729" loading="lazy"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%">
			<!-- wp:paragraph {"className":"zernyar-eyebrow"} -->
			<p class="zernyar-eyebrow"><?php esc_html_e( 'About the roastery', 'zernyar' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading -->
			<h2 class="wp-block-heading"><?php esc_html_e( 'Small batches and a roast profile for every lot', 'zernyar' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'We buy green coffee from importers who know the farms, cup every lot and look for the roast that brings out its own taste. A batch is 12 kg, so nothing sits on a shelf for months.', 'zernyar' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:list {"className":"zernyar-facts"} -->
			<ul class="wp-block-list zernyar-facts">
				<!-- wp:list-item -->
				<li><strong>12</strong> <?php esc_html_e( 'coffees on the menu', 'zernyar' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><strong>1</strong> <?php esc_html_e( 'roast day a week', 'zernyar' ); ?></li>
				<!-- /wp:list-item -->

				<!-- wp:list-item -->
				<li><strong>84+</strong> <?php esc_html_e( 'cupping score of every lot', 'zernyar' ); ?></li>
				<!-- /wp:list-item -->
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
