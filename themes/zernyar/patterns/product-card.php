<?php
/**
 * Title: Product card
 * Slug: zernyar/product-card
 * Categories: woo-commerce
 * Inserter: no
 *
 * @package Zernyar
 */

?>
<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"aspectRatio":"1","scale":"cover","className":"zernyar-card__image"} -->
	<!-- wp:woocommerce/product-sale-badge {"isDescendentOfQueryLoop":true,"align":"right"} /-->
<!-- /wp:woocommerce/product-image -->

<!-- wp:post-title {"level":3,"isLink":true,"className":"zernyar-card__title","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

<!-- wp:post-terms {"term":"pa_roast","separator":" · ","className":"zernyar-card__meta"} /-->

<!-- wp:group {"className":"zernyar-card__footer","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group zernyar-card__footer">
	<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"className":"zernyar-card__price"} /-->

	<!-- wp:woocommerce/product-button {"isDescendentOfQueryLoop":true,"className":"zernyar-card__button"} /-->
</div>
<!-- /wp:group -->
