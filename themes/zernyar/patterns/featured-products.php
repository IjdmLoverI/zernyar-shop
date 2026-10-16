<?php
/**
 * Title: Featured coffee
 * Slug: zernyar/featured-products
 * Categories: zernyar, woo-commerce
 *
 * @package Zernyar
 */

?>
<!-- wp:group {"align":"wide","className":"zernyar-section zernyar-featured","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide zernyar-section zernyar-featured">
	<!-- wp:group {"className":"zernyar-section__head","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
	<div class="wp-block-group zernyar-section__head">
		<!-- wp:heading -->
		<h2 class="wp-block-heading"><?php esc_html_e( 'Popular this week', 'zernyar' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"zernyar-more"} -->
		<p class="zernyar-more"><a href="<?php echo esc_url( zernyar_shop_url() ); ?>"><?php esc_html_e( 'All coffee', 'zernyar' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:woocommerce/product-collection {"queryId":10,"query":{"perPage":4,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"menu_order","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":true,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[]},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/featured","hideControls":["inherit","featured"],"queryContextIncludes":["collection"],"className":"zernyar-products"} -->
	<div class="wp-block-woocommerce-product-collection zernyar-products">
		<!-- wp:woocommerce/product-template -->
			<!-- wp:pattern {"slug":"zernyar/product-card"} /-->
		<!-- /wp:woocommerce/product-template -->
	</div>
	<!-- /wp:woocommerce/product-collection -->
</div>
<!-- /wp:group -->
