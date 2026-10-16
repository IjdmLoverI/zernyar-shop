<?php
/**
 * Title: Catalog with filters
 * Slug: zernyar/catalog
 * Categories: woo-commerce
 * Inserter: no
 *
 * The attribute filters are added only for attributes that exist, so the
 * template works before the demo content is imported.
 *
 * @package Zernyar
 */

$zernyar_attribute_filters = array(
	'roast'      => __( 'Roast', 'zernyar' ),
	'origin'     => __( 'Origin', 'zernyar' ),
	'processing' => __( 'Processing', 'zernyar' ),
);
?>
<!-- wp:columns {"align":"wide","className":"zernyar-catalog"} -->
<div class="wp-block-columns alignwide zernyar-catalog">
	<!-- wp:column {"width":"17rem","className":"zernyar-catalog__filters"} -->
	<div class="wp-block-column zernyar-catalog__filters" style="flex-basis:17rem">
		<!-- wp:woocommerce/product-filters {"overlayMode":"mobile"} -->
		<div class="wp-block-woocommerce-product-filters">
			<!-- wp:woocommerce/product-filter-active -->
			<div class="wp-block-woocommerce-product-filter-active">
				<!-- wp:woocommerce/product-filter-removable-chips /-->

				<!-- wp:woocommerce/product-filter-clear-button -->
				<div class="wp-block-woocommerce-product-filter-clear-button">
					<!-- wp:buttons -->
					<div class="wp-block-buttons">
						<!-- wp:button {"className":"is-style-outline zernyar-clear-filters"} -->
						<div class="wp-block-button is-style-outline zernyar-clear-filters"><a class="wp-block-button__link wp-element-button"><?php esc_html_e( 'Clear filters', 'zernyar' ); ?></a></div>
						<!-- /wp:button -->
					</div>
					<!-- /wp:buttons -->
				</div>
				<!-- /wp:woocommerce/product-filter-clear-button -->
			</div>
			<!-- /wp:woocommerce/product-filter-active -->

			<!-- wp:woocommerce/product-filter-taxonomy {"taxonomy":"product_cat","sortOrder":"name-asc"} -->
			<div class="wp-block-woocommerce-product-filter-taxonomy">
				<!-- wp:heading {"level":3,"className":"zernyar-filter__title"} -->
				<h3 class="wp-block-heading zernyar-filter__title"><?php esc_html_e( 'Category', 'zernyar' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:woocommerce/product-filter-checkbox-list /-->
			</div>
			<!-- /wp:woocommerce/product-filter-taxonomy -->

			<?php
			foreach ( $zernyar_attribute_filters as $zernyar_slug => $zernyar_label ) :
				$zernyar_attribute_id = function_exists( 'wc_attribute_taxonomy_id_by_name' ) ? wc_attribute_taxonomy_id_by_name( $zernyar_slug ) : 0;
				if ( ! $zernyar_attribute_id ) {
					continue;
				}
				?>
			<!-- wp:woocommerce/product-filter-attribute {"attributeId":<?php echo (int) $zernyar_attribute_id; ?>,"sortOrder":"name-asc"} -->
			<div class="wp-block-woocommerce-product-filter-attribute">
				<!-- wp:heading {"level":3,"className":"zernyar-filter__title"} -->
				<h3 class="wp-block-heading zernyar-filter__title"><?php echo esc_html( $zernyar_label ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:woocommerce/product-filter-checkbox-list /-->
			</div>
			<!-- /wp:woocommerce/product-filter-attribute -->
			<?php endforeach; ?>

			<!-- wp:woocommerce/product-filter-price -->
			<div class="wp-block-woocommerce-product-filter-price">
				<!-- wp:heading {"level":3,"className":"zernyar-filter__title"} -->
				<h3 class="wp-block-heading zernyar-filter__title"><?php esc_html_e( 'Price', 'zernyar' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:woocommerce/product-filter-price-slider /-->
			</div>
			<!-- /wp:woocommerce/product-filter-price -->
		</div>
		<!-- /wp:woocommerce/product-filters -->
	</div>
	<!-- /wp:column -->

	<!-- wp:column {"className":"zernyar-catalog__results"} -->
	<div class="wp-block-column zernyar-catalog__results">
		<!-- wp:group {"className":"zernyar-catalog__bar","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between"}} -->
		<div class="wp-block-group zernyar-catalog__bar">
			<!-- wp:woocommerce/product-results-count /-->

			<!-- wp:woocommerce/catalog-sorting /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:woocommerce/product-collection {"queryId":0,"query":{"woocommerceAttributes":[],"woocommerceStockStatus":["instock","outofstock","onbackorder"],"taxQuery":{},"isProductCollectionBlock":true,"perPage":12,"pages":0,"offset":0,"postType":"product","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":true},"tagName":"div","dimensions":{"widthType":"fill","fixedWidth":""},"displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"convertedFromProducts":false,"queryContextIncludes":["collection"],"className":"zernyar-products"} -->
		<div class="wp-block-woocommerce-product-collection zernyar-products">
			<!-- wp:woocommerce/product-template -->
				<!-- wp:pattern {"slug":"zernyar/product-card"} /-->
			<!-- /wp:woocommerce/product-template -->

			<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
				<!-- wp:query-pagination-previous /-->
				<!-- wp:query-pagination-numbers /-->
				<!-- wp:query-pagination-next /-->
			<!-- /wp:query-pagination -->

			<!-- wp:woocommerce/product-collection-no-results -->
				<!-- wp:pattern {"slug":"woocommerce/no-products-found-clear-filters"} /-->
			<!-- /wp:woocommerce/product-collection-no-results -->
		</div>
		<!-- /wp:woocommerce/product-collection -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
