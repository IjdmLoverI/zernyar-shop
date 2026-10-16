<?php
/**
 * Title: Benefits
 * Slug: zernyar/benefits
 * Categories: zernyar, featured
 *
 * @package Zernyar
 */

$zernyar_benefits = array(
	array(
		'icon'  => 'roast',
		'title' => __( 'Freshly roasted', 'zernyar' ),
		'text'  => __( 'We roast on Tuesday and ship from Wednesday, so the beans reach you at their best.', 'zernyar' ),
	),
	array(
		'icon'  => 'grind',
		'title' => __( 'Ground for your brewer', 'zernyar' ),
		'text'  => __( 'Whole beans, or a grind for espresso, filter or the cezve.', 'zernyar' ),
	),
	array(
		'icon'  => 'delivery',
		'title' => __( 'Nova Poshta in 1–3 days', 'zernyar' ),
		'text'  => __( 'To any branch or parcel locker in Ukraine.', 'zernyar' ),
	),
	array(
		'icon'  => 'card',
		'title' => __( 'Pay as you like', 'zernyar' ),
		'text'  => __( 'By card through WayForPay or in cash on delivery.', 'zernyar' ),
	),
);
?>
<!-- wp:group {"align":"wide","className":"zernyar-benefits","layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"13rem"}} -->
<div class="wp-block-group alignwide zernyar-benefits">
	<?php foreach ( $zernyar_benefits as $zernyar_benefit ) : ?>
	<!-- wp:group {"className":"zernyar-benefit zernyar-benefit--<?php echo esc_attr( $zernyar_benefit['icon'] ); ?>","layout":{"type":"default"}} -->
	<div class="wp-block-group zernyar-benefit zernyar-benefit--<?php echo esc_attr( $zernyar_benefit['icon'] ); ?>">
		<!-- wp:heading {"level":3,"className":"zernyar-benefit__title"} -->
		<h3 class="wp-block-heading zernyar-benefit__title"><?php echo esc_html( $zernyar_benefit['title'] ); ?></h3>
		<!-- /wp:heading -->

		<!-- wp:paragraph -->
		<p><?php echo esc_html( $zernyar_benefit['text'] ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
	<?php endforeach; ?>
</div>
<!-- /wp:group -->
