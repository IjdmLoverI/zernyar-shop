<?php
/**
 * Imports the demo catalog, pages and menu.
 *
 * @package ZernyarDemo
 */

defined( 'ABSPATH' ) || exit;

/**
 * Idempotent import in small steps, so a host with a short request time limit
 * can run it over several requests.
 */
final class Zernyar_Demo_Seeder {

	private const STATE_OPTION = 'zernyar_demo_seed_state';
	private const STEPS        = array( 'settings', 'attributes', 'categories', 'pages', 'products', 'menu' );
	private const GRAMS        = array(
		'250g' => '0.27',
		'1kg'  => '1.05',
	);

	/**
	 * Decoded data/catalog.json.
	 *
	 * @var array<string, mixed>
	 */
	private array $catalog;

	/**
	 * Current step and item within it.
	 *
	 * @var array{step: int, item: int}
	 */
	private array $state = array(
		'step' => 0,
		'item' => 0,
	);

	/**
	 * Loads the catalog.
	 *
	 * @param string $catalog_file Path to catalog.json.
	 */
	public function __construct( private readonly string $catalog_file ) {
		$json          = (string) file_get_contents( $this->catalog_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
		$this->catalog = (array) json_decode( $json, true );
	}

	/**
	 * Runs steps until done or until the time budget is spent.
	 *
	 * @param float $budget Seconds; 0 runs everything.
	 * @return bool Whether the import finished.
	 */
	public function run( float $budget = 0 ): bool {
		$started     = microtime( true );
		$this->state = $this->saved_state();

		$steps = count( self::STEPS );

		while ( $this->state['step'] < $steps ) {
			$method = 'step_' . self::STEPS[ $this->state['step'] ];
			if ( $this->$method() ) {
				++$this->state['step'];
				$this->state['item'] = 0;
			}
			update_option( self::STATE_OPTION, $this->state, false );

			if ( $budget > 0 && microtime( true ) - $started > $budget ) {
				return false;
			}
		}

		delete_option( self::STATE_OPTION );
		return true;
	}

	/**
	 * What the import is doing, for the admin screen.
	 *
	 * @return array{running: bool, step: string, item: int, products: int}
	 */
	public function progress(): array {
		$saved = get_option( self::STATE_OPTION );
		$state = $this->saved_state();
		return array(
			'running'  => is_array( $saved ),
			'step'     => self::STEPS[ min( $state['step'], count( self::STEPS ) - 1 ) ],
			'item'     => $state['item'],
			'products' => count( $this->catalog['products'] ?? array() ),
		);
	}

	/**
	 * Saved progress.
	 *
	 * @return array{step: int, item: int}
	 */
	private function saved_state(): array {
		$state = get_option( self::STATE_OPTION );
		return array(
			'step' => (int) ( is_array( $state ) ? ( $state['step'] ?? 0 ) : 0 ),
			'item' => (int) ( is_array( $state ) ? ( $state['item'] ?? 0 ) : 0 ),
		);
	}

	/**
	 * Store settings.
	 */
	private function step_settings(): bool {
		update_option( 'blogname', 'Зерняр' );
		update_option( 'blogdescription', 'Свіжообсмажена кава з доставкою по Україні' );
		update_option( 'woocommerce_store_city', 'Київ' );
		update_option( 'woocommerce_store_postcode', '01001' );
		update_option( 'woocommerce_weight_unit', 'kg' );
		update_option( 'woocommerce_dimension_unit', 'cm' );
		update_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
		update_option( 'woocommerce_enable_reviews', 'no' );
		update_option( 'woocommerce_attribute_lookup_direct_updates', 'yes' );
		update_option(
			'woocommerce_cod_settings',
			array(
				'enabled'            => 'yes',
				'title'              => 'Накладений платіж',
				'description'        => 'Оплата готівкою або карткою у відділенні Нової Пошти під час отримання.',
				'instructions'       => 'Оплатіть замовлення у відділенні, коли забиратимете посилку.',
				'enable_for_methods' => array(),
				'enable_for_virtual' => 'yes',
			)
		);
		return true;
	}

	/**
	 * Global attributes and their terms.
	 *
	 * @throws RuntimeException When WooCommerce refuses an attribute.
	 */
	private function step_attributes(): bool {
		foreach ( $this->catalog['attributes'] as $slug => $attribute ) {
			$taxonomy = wc_attribute_taxonomy_name( $slug );
			$args     = array(
				'name'         => $attribute['name'],
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => ! empty( $attribute['archives'] ),
			);
			$id       = wc_attribute_taxonomy_id_by_name( $slug );
			$saved    = $id ? wc_update_attribute( $id, $args ) : wc_create_attribute( $args );
			if ( is_wp_error( $saved ) ) {
				throw new RuntimeException( esc_html( $saved->get_error_message() ) );
			}
			if ( ! taxonomy_exists( $taxonomy ) ) {
				register_taxonomy( $taxonomy, array( 'product', 'product_variation' ), array( 'hierarchical' => false ) );
			}

			$order = 0;
			foreach ( $attribute['terms'] as $term_slug => $name ) {
				$term = get_term_by( 'slug', (string) $term_slug, $taxonomy );
				$id   = $term ? $term->term_id : $this->saved_term( wp_insert_term( $name, $taxonomy, array( 'slug' => (string) $term_slug ) ) );
				update_term_meta( $id, 'order', $order++ );
			}
		}//end foreach
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
		return true;
	}

	/**
	 * Product categories.
	 */
	private function step_categories(): bool {
		$order = 0;
		foreach ( $this->catalog['categories'] as $category ) {
			$parent = isset( $category['parent'] ) ? $this->term_ids( 'product_cat', array( $category['parent'] ) ) : array();
			$args   = array(
				'slug'        => (string) $category['slug'],
				'description' => (string) $category['description'],
				'parent'      => absint( $parent[0] ?? 0 ),
			);
			$term   = get_term_by( 'slug', $category['slug'], 'product_cat' );
			$id     = $this->saved_term(
				$term
					? wp_update_term( $term->term_id, 'product_cat', $args + array( 'name' => (string) $category['name'] ) )
					: wp_insert_term( (string) $category['name'], 'product_cat', $args )
			);
			update_term_meta( $id, 'order', $order++ );
		}
		return true;
	}

	/**
	 * Content pages and Ukrainian titles for the WooCommerce pages.
	 */
	private function step_pages(): bool {
		$content = require __DIR__ . '/../data/pages.php';
		foreach ( $this->catalog['pages'] as $page ) {
			$existing = get_page_by_path( $page['slug'] );
			$postarr  = array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $page['title'],
				'post_name'    => $page['slug'],
				'post_content' => $content[ $page['content'] ],
			);
			if ( $existing ) {
				$postarr['ID'] = $existing->ID;
			}
			wp_insert_post( wp_slash( $postarr ), true );
		}

		foreach ( $this->catalog['wc_pages'] as $page => $title ) {
			$id = wc_get_page_id( $page );
			if ( $id > 0 ) {
				wp_update_post(
					array(
						'ID'         => $id,
						'post_title' => $title,
					)
				);
			}
		}
		return true;
	}

	/**
	 * One product per call.
	 */
	private function step_products(): bool {
		$products = $this->catalog['products'];
		if ( $this->state['item'] >= count( $products ) ) {
			return true;
		}
		$this->save_product( $products[ $this->state['item'] ] );
		++$this->state['item'];
		return $this->state['item'] >= count( $products );
	}

	/**
	 * Header and footer menu.
	 */
	private function step_menu(): bool {
		$links = array(
			$this->page_link( wc_get_page_id( 'shop' ) ),
			$this->category_link( 'drip' ),
			$this->category_link( 'gear' ),
			$this->page_link( (int) get_page_by_path( 'about' )?->ID ),
			$this->page_link( (int) get_page_by_path( 'delivery' )?->ID ),
			$this->page_link( (int) get_page_by_path( 'contacts' )?->ID ),
		);

		$existing = get_page_by_path( 'zernyar-menu', OBJECT, 'wp_navigation' );
		wp_insert_post(
			wp_slash(
				array(
					'ID'           => $existing ? $existing->ID : 0,
					'post_type'    => 'wp_navigation',
					'post_status'  => 'publish',
					'post_title'   => 'Головне меню',
					'post_name'    => 'zernyar-menu',
					'post_content' => implode( "\n", array_filter( $links ) ),
				)
			),
			true
		);
		return true;
	}

	/**
	 * Creates or updates one product with its variations.
	 *
	 * @param array<string, mixed> $data Product from the catalog.
	 */
	private function save_product( array $data ): void {
		$id       = wc_get_product_id_by_sku( $data['sku'] );
		$variable = 'variable' === $data['type'];
		$product  = $id ? $this->product( $id ) : ( $variable ? new WC_Product_Variable() : new WC_Product_Simple() );
		if ( ! $id ) {
			$product->set_sku( $data['sku'] );
		}

		$product->set_name( $data['name'] );
		$product->set_slug( $data['slug'] );
		$product->set_status( 'publish' );
		$product->set_short_description( $data['short'] );
		$product->set_description( $this->paragraphs( $data['description'] ) );
		$product->set_category_ids( $this->term_ids( 'product_cat', $data['categories'] ) );
		$product->set_featured( (bool) $data['featured'] );
		$product->set_menu_order( (int) $data['order'] );
		$product->set_reviews_allowed( false );
		$product->set_image_id( $this->image( $data ) );

		$attributes = array();
		$position   = 0;
		foreach ( (array) ( $data['attributes'] ?? array() ) as $slug => $terms ) {
			$attributes[] = $this->attribute( $slug, $terms, false, $position++ );
		}
		if ( ! empty( $data['notes'] ) && 'gifts' !== ( $data['categories'][0] ?? '' ) ) {
			$notes = new WC_Product_Attribute();
			$notes->set_name( 'Смакові ноти' );
			$notes->set_options( array( str_replace( ' · ', ', ', $data['notes'] ) ) );
			$notes->set_position( $position++ );
			$notes->set_visible( true );
			$attributes[] = $notes;
		}
		if ( $variable ) {
			$attributes[] = $this->attribute( 'weight', $this->keys( $data['prices'] ), true, $position++ );
			$attributes[] = $this->attribute( 'grind', $this->keys( $this->catalog['attributes']['grind']['terms'] ), true, $position++ );
			$product->set_default_attributes(
				array(
					'pa_weight' => '250g',
					'pa_grind'  => 'beans',
				)
			);
		} else {
			$product->set_regular_price( (string) $data['price'] );
			$product->set_weight( (string) $data['weight'] );
			$product->set_stock_status( 'instock' );
		}
		$product->set_attributes( $attributes );
		$id = $product->save();

		if ( $variable ) {
			$this->save_variations( $id, $data );
			WC_Product_Variable::sync( $id );
		}
		wc_delete_product_transients( $id );
	}

	/**
	 * An existing product.
	 *
	 * @param int $id Product ID.
	 * @throws RuntimeException When the product cannot be loaded.
	 */
	private function product( int $id ): WC_Product {
		$product = wc_get_product( $id );
		if ( ! $product instanceof WC_Product ) {
			throw new RuntimeException( esc_html( "Product {$id} cannot be loaded." ) );
		}
		return $product;
	}

	/**
	 * Term ID from wp_insert_term() or wp_update_term().
	 *
	 * @param array<string, mixed>|WP_Error $result Result.
	 * @throws RuntimeException When the term was not saved.
	 */
	private function saved_term( $result ): int {
		if ( is_wp_error( $result ) ) {
			throw new RuntimeException( esc_html( $result->get_error_message() ) );
		}
		return (int) $result['term_id'];
	}

	/**
	 * Keys of a JSON object as strings.
	 *
	 * @param array<string|int, mixed> $map Object.
	 * @return array<int, string>
	 */
	private function keys( array $map ): array {
		return array_map( 'strval', array_keys( $map ) );
	}

	/**
	 * Every weight and grind of a coffee.
	 *
	 * @param int                  $parent_id Variable product.
	 * @param array<string, mixed> $data      Product from the catalog.
	 */
	private function save_variations( int $parent_id, array $data ): void {
		foreach ( $data['prices'] as $weight => $price ) {
			foreach ( $this->keys( $this->catalog['attributes']['grind']['terms'] ) as $grind ) {
				$sku       = strtoupper( $data['sku'] . '-' . $weight . '-' . $grind );
				$id        = wc_get_product_id_by_sku( $sku );
				$variation = $id ? $this->product( $id ) : new WC_Product_Variation();
				if ( ! $id ) {
					$variation->set_parent_id( $parent_id );
					$variation->set_sku( $sku );
				}
				$variation->set_attributes(
					array(
						'pa_weight' => $weight,
						'pa_grind'  => $grind,
					)
				);
				$variation->set_regular_price( (string) $price );
				$variation->set_weight( self::GRAMS[ $weight ] );
				$variation->set_stock_status( 'instock' );
				$variation->save();
			}
		}//end foreach
	}

	/**
	 * A global attribute with some of its terms.
	 *
	 * @param string        $slug      Attribute slug without pa_.
	 * @param array<string> $terms     Term slugs.
	 * @param bool          $variation Used for variations.
	 * @param int           $position  Order on the product page.
	 */
	private function attribute( string $slug, array $terms, bool $variation, int $position ): WC_Product_Attribute {
		$taxonomy  = wc_attribute_taxonomy_name( $slug );
		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( $slug ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( $this->term_ids( $taxonomy, $terms ) );
		$attribute->set_position( $position );
		$attribute->set_visible( true );
		$attribute->set_variation( $variation );
		return $attribute;
	}

	/**
	 * Term IDs for slugs.
	 *
	 * @param string        $taxonomy Taxonomy.
	 * @param array<string> $slugs    Term slugs.
	 * @return array<int>
	 */
	private function term_ids( string $taxonomy, array $slugs ): array {
		$ids = array();
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term ) {
				$ids[] = (int) $term->term_id;
			}
		}
		return $ids;
	}

	/**
	 * The product image, added to the media library once.
	 *
	 * @param array<string, mixed> $data Product from the catalog.
	 * @throws RuntimeException When the image cannot be copied to the uploads folder.
	 */
	private function image( array $data ): int {
		$file     = $data['image']['file'] ?? $data['slug'] . '.webp';
		$existing = get_posts(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'meta_key'    => '_zernyar_demo_image', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Runs once per product during the import.
				'meta_value'  => $file, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- Same.
				'fields'      => 'ids',
				'numberposts' => 1,
			)
		);
		if ( $existing ) {
			return (int) $existing[0];
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		$source = dirname( __DIR__ ) . '/assets/products/' . $file;
		$upload = wp_upload_bits( $file, null, (string) file_get_contents( $source ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
		if ( $upload['error'] ) {
			throw new RuntimeException( esc_html( $upload['error'] ) );
		}

		$id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/webp',
				'post_title'     => $data['name'],
				'post_status'    => 'inherit',
			),
			$upload['file']
		);
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
		update_post_meta( $id, '_zernyar_demo_image', $file );
		update_post_meta( $id, '_wp_attachment_image_alt', $data['name'] );
		return $id;
	}

	/**
	 * Plain text with blank lines to paragraph blocks.
	 *
	 * @param string $text Description.
	 */
	private function paragraphs( string $text ): string {
		$blocks = array();
		foreach ( explode( "\n\n", trim( $text ) ) as $paragraph ) {
			$blocks[] = "<!-- wp:paragraph -->\n<p>" . esc_html( trim( $paragraph ) ) . "</p>\n<!-- /wp:paragraph -->";
		}
		return implode( "\n\n", $blocks );
	}

	/**
	 * Menu link to a page.
	 *
	 * @param int $id Page ID.
	 */
	private function page_link( int $id ): string {
		if ( $id <= 0 ) {
			return '';
		}
		return $this->link( get_the_title( $id ), 'page', $id, (string) get_permalink( $id ), 'post-type' );
	}

	/**
	 * Menu link to a product category.
	 *
	 * @param string $slug Category slug.
	 */
	private function category_link( string $slug ): string {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		$url  = $term ? get_term_link( $term ) : '';
		if ( ! $term || ! is_string( $url ) ) {
			return '';
		}
		return $this->link( $term->name, 'product_cat', $term->term_id, $url, 'taxonomy' );
	}

	/**
	 * Navigation link block.
	 *
	 * @param string $label Link text.
	 * @param string $type  Object type.
	 * @param int    $id    Object ID.
	 * @param string $url   Address.
	 * @param string $kind  post-type or taxonomy.
	 */
	private function link( string $label, string $type, int $id, string $url, string $kind ): string {
		$attributes = array(
			'label' => $label,
			'type'  => $type,
			'id'    => $id,
			'url'   => $url,
			'kind'  => $kind,
		);
		return '<!-- wp:navigation-link ' . wp_json_encode( $attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' /-->';
	}
}
