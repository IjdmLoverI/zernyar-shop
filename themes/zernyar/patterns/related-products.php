<?php
/**
 * Title: Related coffee
 * Slug: zernyar/related-products
 * Categories: woo-commerce
 * Inserter: no
 *
 * @package Zernyar
 */

?>
<!-- wp:woocommerce/product-collection {"align":"wide","queryId":20,"query":{"perPage":4,"pages":1,"offset":0,"postType":"product","order":"asc","orderBy":"title","search":"","exclude":[],"inherit":false,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":false,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":4,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"collection":"woocommerce/product-collection/related","hideControls":["inherit"],"queryContextIncludes":["collection"],"className":"zernyar-products zernyar-related"} -->
<div class="wp-block-woocommerce-product-collection alignwide zernyar-products zernyar-related">
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'You may also like', 'zernyar' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:woocommerce/product-template -->
		<!-- wp:pattern {"slug":"zernyar/product-card"} /-->
	<!-- /wp:woocommerce/product-template -->
</div>
<!-- /wp:woocommerce/product-collection -->
