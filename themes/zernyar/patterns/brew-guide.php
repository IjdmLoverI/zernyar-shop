<?php
/**
 * Title: Brew guide
 * Slug: zernyar/brew-guide
 * Categories: zernyar, text
 *
 * @package Zernyar
 */

$zernyar_methods = array(
	array(
		'image' => 'brew-espresso.webp',
		'alt'   => __( 'Espresso pours from a machine into a white cup', 'zernyar' ),
		'title' => __( 'Espresso', 'zernyar' ),
		'text'  => __( 'Dense blends with chocolate and nuts. 18 g of fine grind, 36 g in the cup, 25–30 seconds.', 'zernyar' ),
		'link'  => __( 'Coffee for espresso', 'zernyar' ),
		'slug'  => 'espresso',
	),
	array(
		'image' => 'brew-filter.webp',
		'alt'   => __( 'A pour-over dripper with a bloom of coffee', 'zernyar' ),
		'title' => __( 'Filter', 'zernyar' ),
		'text'  => __( 'Light single origins with fruit and flowers. 15 g of medium grind to 250 ml of water at 94 °C.', 'zernyar' ),
		'link'  => __( 'Coffee for filter', 'zernyar' ),
		'slug'  => 'filter',
	),
	array(
		'image' => 'brew-cezve.webp',
		'alt'   => __( 'A copper cezve on a dark table', 'zernyar' ),
		'title' => __( 'Cezve', 'zernyar' ),
		'text'  => __( 'Darker roasts ground to powder. 8 g to 80 ml of cold water, heat slowly and take it off at the first rise.', 'zernyar' ),
		'link'  => __( 'Coffee for the cezve', 'zernyar' ),
		'slug'  => 'cezve',
	),
);
?>
<!-- wp:group {"align":"full","anchor":"brew","className":"zernyar-section zernyar-brew","backgroundColor":"base-2","layout":{"type":"constrained"}} -->
<div id="brew" class="wp-block-group alignfull zernyar-section zernyar-brew has-base-2-background-color has-background">
	<!-- wp:heading {"align":"wide"} -->
	<h2 class="wp-block-heading alignwide"><?php esc_html_e( 'How to brew', 'zernyar' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"align":"wide","className":"zernyar-brew__grid","layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"15rem"}} -->
	<div class="wp-block-group alignwide zernyar-brew__grid">
		<?php foreach ( $zernyar_methods as $zernyar_method ) : ?>
		<!-- wp:group {"className":"zernyar-brew__card","layout":{"type":"default"}} -->
		<div class="wp-block-group zernyar-brew__card">
			<!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"zernyar-brew__image"} -->
			<figure class="wp-block-image size-large zernyar-brew__image"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/' . $zernyar_method['image'] ) ); ?>" alt="<?php echo esc_attr( $zernyar_method['alt'] ); ?>" width="900" height="600" loading="lazy"/></figure>
			<!-- /wp:image -->

			<!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading"><?php echo esc_html( $zernyar_method['title'] ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:paragraph -->
			<p><?php echo esc_html( $zernyar_method['text'] ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"className":"zernyar-more"} -->
			<p class="zernyar-more"><a href="<?php echo esc_url( zernyar_category_url( $zernyar_method['slug'] ) ); ?>"><?php echo esc_html( $zernyar_method['link'] ); ?></a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
		<?php endforeach; ?>
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
