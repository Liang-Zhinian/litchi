<?php


defined( 'ABSPATH' ) || exit;


/**
 * REST API Products controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_CRUD_Controller
 */
class Litchi_REST_Product_Controller extends Litchi_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'litchi/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'product';

	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = true;

	/**
	 * Initialize product actions.
	 */
	public function __construct() {
		add_action( "woocommerce_rest_insert_{$this->post_type}_object", array( $this, 'clear_transients' ) );
	}

	/**
     * Register all routes releated with media
     *
     * @return void
     */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/top-rated/expert', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_top_rated_items' ),
			'permission_callback' => array( $this, 'get_items_permissions_check' ),
			'args' => array(
			)
		) );
	} // register_routes()

	public function get_items_permissions_check(){ return true; }


	public function get_top_rated_items( WP_REST_Request $request ) {

		$query_args = array(
			'posts_per_page'	=> 20,
			'no_found_rows'		=> 1,
			'post_status'    	=> 'publish',
			'post_type'      	=> 'product',
			'meta_key'       	=> 'taq_review_score',
			'orderby'        	=> 'meta_value_num',
			'order'				=> 'DESC',
			'meta_query'		=> WC()->query->get_meta_query(),
			'tax_query'      	=> WC()->query->get_tax_query()
		);

		$query = new WP_Query( $query_args );

		if ( $query && $query->have_posts() ) {
			$posts = $query -> posts;
			$data = array();

			foreach( $posts as $post ) {

				$product = wc_get_product( $post );
				$product_data    = $this->get_product_data( $product );

				$post_reset = array();
				foreach ( $product_data as $key => $value ) {
					$post_reset[$key] = $value;
				}

				//                $post_reset['meta'] = $post->get_meta_data();

				$post_id = $post -> ID;
				$taq_review = array();
				$taq_review_title = get_post_meta( $post_id, 'taq_review_title' );
				if ($taq_review_title) {
					$taq_review['taq_review_title'] =  $taq_review_title;
				}
				$taq_review_criteria = get_post_meta( $post_id, 'taq_review_criteria' );
				if ($taq_review_criteria) {
					$taq_review['taq_review_criteria'] =  $taq_review_criteria;
				}
				$taq_review_score = get_post_meta( $post_id, 'taq_review_score' );
				if ($taq_review_score) {
					$score = (int)$taq_review_score[0];
					$taq_review['taq_review_score'] =  $score;
				}
				$post_reset['taq_review'] =  $taq_review;

				$data[] = $post_reset;
			}
			return $data;
		}

		return false;
	}

	/**
	 * Get object.
	 *
	 * @param int $id Object ID.
	 *
	 * @since  3.0.0
	 * @return WC_Data
	 */
	protected function get_object( $id ) {
		return wc_get_product( $id );
	}


	/**
     * Get product data.
     *
     * @param WC_Product $product Product instance.
     * @param string     $context Request context.
     *                            Options: 'view' and 'edit'.
     * @return array
     */
	protected function prepare_data_for_response( $product, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data = array(
			'id'                    => $product->get_id(),
			'name'                  => $product->get_name( $context ),
			'slug'                  => $product->get_slug( $context ),
			'post_author'           => get_post_field( 'post_author', $product->get_id() ),
			'permalink'             => $product->get_permalink(),
			'date_created'          => wc_rest_prepare_date_response( $product->get_date_created( $context ), false ),
			'date_created_gmt'      => wc_rest_prepare_date_response( $product->get_date_created( $context ) ),
			'date_modified'         => wc_rest_prepare_date_response( $product->get_date_modified( $context ), false ),
			'date_modified_gmt'     => wc_rest_prepare_date_response( $product->get_date_modified( $context ) ),
			'type'                  => $product->get_type(),
			'status'                => $product->get_status( $context ),
			'featured'              => $product->is_featured(),
			'catalog_visibility'    => $product->get_catalog_visibility( $context ),
			'description'           => 'view' === $context ? wpautop( do_shortcode( $product->get_description() ) ) : $product->get_description( $context ),
			'short_description'     => 'view' === $context ? apply_filters( 'woocommerce_short_description', $product->get_short_description() ) : $product->get_short_description( $context ),
			'sku'                   => $product->get_sku( $context ),
			'price'                 => $product->get_price( $context ),
			'regular_price'         => $product->get_regular_price( $context ),
			'sale_price'            => $product->get_sale_price( $context ) ? $product->get_sale_price( $context ) : '',
			'date_on_sale_from'     => wc_rest_prepare_date_response( $product->get_date_on_sale_from( $context ), false ),
			'date_on_sale_from_gmt' => wc_rest_prepare_date_response( $product->get_date_on_sale_from( $context ) ),
			'date_on_sale_to'       => wc_rest_prepare_date_response( $product->get_date_on_sale_to( $context ), false ),
			'date_on_sale_to_gmt'   => wc_rest_prepare_date_response( $product->get_date_on_sale_to( $context ) ),
			'price_html'            => $product->get_price_html(),
			'on_sale'               => $product->is_on_sale( $context ),
			'purchasable'           => $product->is_purchasable(),
			'total_sales'           => $product->get_total_sales( $context ),
			'virtual'               => $product->is_virtual(),
			'downloadable'          => $product->is_downloadable(),
			'downloads'             => $this->get_downloads( $product ),
			'download_limit'        => $product->get_download_limit( $context ),
			'download_expiry'       => $product->get_download_expiry( $context ),
			'external_url'          => $product->is_type( 'external' ) ? $product->get_product_url( $context ) : '',
			'button_text'           => $product->is_type( 'external' ) ? $product->get_button_text( $context ) : '',
			'tax_status'            => $product->get_tax_status( $context ),
			'tax_class'             => $product->get_tax_class( $context ),
			'manage_stock'          => $product->managing_stock(),
			'stock_quantity'        => $product->get_stock_quantity( $context ),
			'low_stock_amount'      => version_compare( WC_VERSION, '3.4.7', '>' ) ? $product->get_low_stock_amount( $context ) : '',
			'in_stock'              => $product->is_in_stock(),
			'backorders'            => $product->get_backorders( $context ),
			'backorders_allowed'    => $product->backorders_allowed(),
			'backordered'           => $product->is_on_backorder(),
			'sold_individually'     => $product->is_sold_individually(),
			'weight'                => $product->get_weight( $context ),
			'dimensions'            => array(
				'length' => $product->get_length( $context ),
				'width'  => $product->get_width( $context ),
				'height' => $product->get_height( $context ),
			),
			'shipping_required'     => $product->needs_shipping(),
			'shipping_taxable'      => $product->is_shipping_taxable(),
			'shipping_class'        => $product->get_shipping_class(),
			'shipping_class_id'     => $product->get_shipping_class_id( $context ),
			'reviews_allowed'       => $product->get_reviews_allowed( $context ),
			'average_rating'        => 'view' === $context ? wc_format_decimal( $product->get_average_rating(), 2 ) : $product->get_average_rating( $context ),
			'rating_count'          => $product->get_rating_count(),
			'related_ids'           => array_map( 'absint', array_values( wc_get_related_products( $product->get_id() ) ) ),
			'upsell_ids'            => array_map( 'absint', $product->get_upsell_ids( $context ) ),
			'cross_sell_ids'        => array_map( 'absint', $product->get_cross_sell_ids( $context ) ),
			'parent_id'             => $product->get_parent_id( $context ),
			'purchase_note'         => 'view' === $context ? wpautop( do_shortcode( wp_kses_post( $product->get_purchase_note() ) ) ) : $product->get_purchase_note( $context ),
			'categories'            => $this->get_taxonomy_terms( $product ),
			'tags'                  => $this->get_taxonomy_terms( $product, 'tag' ),
			'images'                => $this->get_images( $product ),
			'attributes'            => $this->get_attributes( $product ),
			'default_attributes'    => $this->get_default_attributes( $product ),
			'variations'            => array(),
			'grouped_products'      => array(),
			'menu_order'            => $product->get_menu_order( $context ),
			'meta_data'             => $product->get_meta_data(),
		);

		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $product, $request ) );
		return apply_filters( "wcfmapi_rest_prepare_{$this->post_type}_object", $response, $product, $request );
	}

	/**
     * Prepare object for database mapping
     *
     * @param objec  $request
     * @param boolean $creating
     *
     * @return object
     */

	protected function prepare_object_for_database( $request, $creating = false ) {
		global $WCFM;
		$product_form_data = array();
		$_POST["controller"] = 'wcfm-products-manage';
		$_POST["excerpt"] = $request['short_description'];
		$_POST["description"]  = $request['description'];

		$map_product_form_data_with_request = array(
			'pro_title'                 =>  $request['name'],               // Product Name
			'sku'                   =>  $request['sku'],                // Product SKU
			'product_type'          =>  $request['type'],       // Product Type
			'is_downloadable'       =>  $request['downloadable'],       // Product Downloadable
			'downloadable_files'    =>  $request['downloadable_files'], // Downloadable Files
			'product_cats'          =>  $request['categories'],         // Product Categories
			'product_tags'          =>  $request['tags'],
			'product_custom_taxonomies' => $request['product_custom_taxonomies'],
			'product_tags'              => $request['product_tags'],
			'product_custom_taxonomies_flat' => $request['product_custom_taxonomies_flat'],
			'featured_img'          =>  $request['featured_image'],
			'gallery_img'           =>  $request['gallery_images'],
			'attributes'            =>  $request['attributes'],
			'default_attributes'    =>  $request['default_attributes'],
			'grouped_products'      =>  $request['grouped_products'],
			'virtual'            => $request['is_virtual'],
			'tax_status'         => $request['tax_status'],
			'tax_class'          => $request['tax_class'],
			'weight'             => $request['weight'],
			'length'             => $request['length'],
			'width'              => $request['width'],
			'height'             => $request['height'],
			'shipping_class_id'  => $request['shipping_class'],
			'sold_individually'  => $request['sold_individually'],
			'upsell_ids'         => $request['upsell_ids'],
			'cross_sell_ids'     => $request['crosssell_ids'],
			'regular_price'      => $request['regular_price'],
			'sale_price'         => $request['sale_price'],
			'date_on_sale_from'  => $request['sale_date_from'],
			'date_on_sale_to'    => $request['sale_date_upto'],
			'manage_stock'       => ( empty($request['manage_stock']) || $request['product_type'] === 'external' || $request['manage_stock'] === '' ) ? false : $request['manage_stock'],
			'backorders'         => ( empty($request['backorders']) || $request['product_type'] === 'external' || $request['backorders'] === '' ) ? 'no' : $request['backorders'],
			'stock_status'       => ( empty($request['stock_status']) || $request['product_type'] === 'external' || $request['stock_status'] === '' ) ? 'instock' : $request['stock_status'],
			'stock_quantity'     => $request['stock_qty'],
			'product_url'        => $request['product_url'],
			'button_text'        => $request['button_text'],
			'download_limit'     => empty( $request['download_limit'] ) ? '' : $request['download_limit'],
			'download_expiry'    => empty( $request['download_expiry'] ) ? '' : $request['download_expiry'],
			'reviews_allowed'    => true
		);
		$map_product_form_data_with_request = apply_filters( "wcfmapi_rest_pre_insert_{$this->post_type}_object", $map_product_form_data_with_request, $request, $creating );
		$_POST['wcfm_products_manage_form'] = $map_product_form_data_with_request;
		$_POST['wcfm_products_manage_form']['pro_id']  = isset( $request['id'] ) ? absint( $request['id'] ) : 0;
		//print_r($map_product_form_data_with_request);

		define('WCFM_REST_API_CALL', TRUE);
		$WCFM->init();
		$response = $WCFM->ajax->wcfm_ajax_controller();

		return json_decode( $response );
	}

	/**
     * Get taxonomy terms.
     *
     * @param WC_Product $product  Product instance.
     * @param string     $taxonomy Taxonomy slug.
     * @return array
     */
	protected function get_taxonomy_terms( $product, $taxonomy = 'cat' ) {
		$terms = array();

		foreach ( wc_get_object_terms( $product->get_id(), 'product_' . $taxonomy ) as $term ) {
			$terms[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}

		return $terms;
	}

	/**
     * Get the images for a product or product variation.
     *
     * @param WC_Product|WC_Product_Variation $product Product instance.
     * @return array
     */
	protected function get_images( $product ) {
		$images = array();
		$attachment_ids = array();

		// Add featured image.
		if ( has_post_thumbnail( $product->get_id() ) ) {
			$attachment_ids[] = $product->get_image_id();
		}

		// Add gallery images.
		$attachment_ids = array_merge( $attachment_ids, $product->get_gallery_image_ids() );

		// Build image data.
		foreach ( $attachment_ids as $position => $attachment_id ) {
			$attachment_post = get_post( $attachment_id );
			if ( is_null( $attachment_post ) ) {
				continue;
			}

			$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
			if ( ! is_array( $attachment ) ) {
				continue;
			}

			$images[] = array(
				'id'                => (int) $attachment_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment_post->post_date, false ),
				'date_created_gmt'  => wc_rest_prepare_date_response( strtotime( $attachment_post->post_date_gmt ) ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment_post->post_modified, false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( strtotime( $attachment_post->post_modified_gmt ) ),
				'src'               => current( $attachment ),
				'name'              => get_the_title( $attachment_id ),
				'alt'               => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
				'position'          => (int) $position,
			);
		}

		// Set a placeholder image if the product has no images set.
		if ( empty( $images ) ) {
			$images[] = array(
				'id'                => 0,
				'date_created'      => wc_rest_prepare_date_response( current_time( 'mysql' ), false ), // Default to now.
				'date_created_gmt'  => wc_rest_prepare_date_response( current_time( 'timestamp', true ) ), // Default to now.
				'date_modified'     => wc_rest_prepare_date_response( current_time( 'mysql' ), false ),
				'date_modified_gmt' => wc_rest_prepare_date_response( current_time( 'timestamp', true ) ),
				'src'               => wc_placeholder_img_src(),
				'name'              => __( 'Placeholder', 'wcfm-marketplace-rest-api' ),
				'alt'               => __( 'Placeholder', 'wcfm-marketplace-rest-api' ),
				'position'          => 0,
			);
		}

		return $images;
	}

	/**
     * Get product attribute taxonomy name.
     *
     * @since  1.0.0
     * @param  string     $slug    Taxonomy name.
     * @param  WC_Product $product Product data.
     * @return string
     */
	protected function get_attribute_taxonomy_name( $slug, $product ) {
		$attributes = $product->get_attributes();

		if ( ! isset( $attributes[ $slug ] ) ) {
			return str_replace( 'pa_', '', $slug );
		}

		$attribute = $attributes[ $slug ];

		// Taxonomy attribute name.
		if ( $attribute->is_taxonomy() ) {
			$taxonomy = $attribute->get_taxonomy_object();
			return $taxonomy->attribute_label;
		}

		// Custom product attribute name.
		return $attribute->get_name();
	}

	/**
     * Get default attributes.
     *
     * @param WC_Product $product Product instance.
     * @return array
     */
	protected function get_default_attributes( $product ) {
		$default = array();

		if ( $product->is_type( 'variable' ) ) {
			foreach ( array_filter( (array) $product->get_default_attributes(), 'strlen' ) as $key => $value ) {
				if ( 0 === strpos( $key, 'pa_' ) ) {
					$default[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $key ),
						'name'   => $this->get_attribute_taxonomy_name( $key, $product ),
						'option' => $value,
					);
				} else {
					$default[] = array(
						'id'     => 0,
						'name'   => $this->get_attribute_taxonomy_name( $key, $product ),
						'option' => $value,
					);
				}
			}
		}

		return $default;
	}

	/**
     * Get attribute options.
     *
     * @param int   $product_id Product ID.
     * @param array $attribute  Attribute data.
     * @return array
     */
	protected function get_attribute_options( $product_id, $attribute ) {
		if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {
			return wc_get_product_terms( $product_id, $attribute['name'], array(
				'fields' => 'names',
			) );
		} elseif ( isset( $attribute['value'] ) ) {
			return array_map( 'trim', explode( '|', $attribute['value'] ) );
		}

		return array();
	}

	/**
     * Get the attributes for a product or product variation.
     *
     * @param WC_Product|WC_Product_Variation $product Product instance.
     * @return array
     */
	protected function get_attributes( $product ) {
		$attributes = array();

		if ( $product->is_type( 'variation' ) ) {
			$_product = wc_get_product( $product->get_parent_id() );
			foreach ( $product->get_variation_attributes() as $attribute_name => $attribute ) {
				$name = str_replace( 'attribute_', '', $attribute_name );

				if ( ! $attribute ) {
					continue;
				}

				// Taxonomy-based attributes are prefixed with `pa_`, otherwise simply `attribute_`.
				if ( 0 === strpos( $attribute_name, 'attribute_pa_' ) ) {
					$option_term = get_term_by( 'slug', $attribute, $name );
					$attributes[] = array(
						'id'     => wc_attribute_taxonomy_id_by_name( $name ),
						'name'   => $this->get_attribute_taxonomy_name( $name, $_product ),
						'option' => $option_term && ! is_wp_error( $option_term ) ? $option_term->name : $attribute,
					);
				} else {
					$attributes[] = array(
						'id'     => 0,
						'name'   => $this->get_attribute_taxonomy_name( $name, $_product ),
						'option' => $attribute,
					);
				}
			}
		} else {
			foreach ( $product->get_attributes() as $attribute ) {
				$attributes[] = array(
					'id'        => $attribute['is_taxonomy'] ? wc_attribute_taxonomy_id_by_name( $attribute['name'] ) : 0,
					'name'      => $this->get_attribute_taxonomy_name( $attribute['name'], $product ),
					'position'  => (int) $attribute['position'],
					'visible'   => (bool) $attribute['is_visible'],
					'variation' => (bool) $attribute['is_variation'],
					'options'   => $this->get_attribute_options( $product->get_id(), $attribute ),
				);
			}
		}

		return $attributes;
	}

	/**
     * Get the downloads for a product or product variation.
     *
     * @param WC_Product|WC_Product_Variation $product Product instance.
     * @return array
     */
	protected function get_downloads( $product ) {
		$downloads = array();

		if ( $product->is_downloadable() ) {
			foreach ( $product->get_downloads() as $file_id => $file ) {
				$downloads[] = array(
					'id'   => $file_id, // MD5 hash.
					'name' => $file['name'],
					'file' => $file['file'],
				);
			}
		}

		return $downloads;
	}

	/**
     * Prepare links for the request.
     *
     * @param WC_Data         $object  Object data.
     * @param WP_REST_Request $request Request object.
     *
     * @return array                   Links for the given post.
     */
	protected function prepare_links( $object, $request ) {
		$links = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->base, $object->get_id() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->base ) ),
			),
		);

		if ( $object->get_parent_id() ) {
			$links['up'] = array(
				'href' => rest_url( sprintf( '/%s/products/%d', $this->namespace, $object->get_parent_id() ) ),
			);
		}

		return $links;
	}

	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		// Set post_status.
		$args['post_status'] = isset( $request['status'] ) ? $request['status'] : $request['post_status'];

		// Taxonomy query to filter products by type, category,
		// tag, shipping class, and attribute.
		$tax_query = array();

		// Map between taxonomy name and arg's key.
		$taxonomies = array(
			'product_cat'            => 'category',
			'product_tag'            => 'tag',
			'product_shipping_class' => 'shipping_class',
		);

		// Set tax_query for each passed arg.
		foreach ( $taxonomies as $taxonomy => $key ) {
			if ( ! empty( $request[ $key ] ) ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $request[ $key ],
				);
			}
		}

		// Filter product type by slug.
		if ( ! empty( $request['type'] ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => $request['type'],
			);
		}

		// Filter by attribute and term.
		if ( ! empty( $request['attribute'] ) && ! empty( $request['attribute_term'] ) ) {
			if ( in_array( $request['attribute'], wc_get_attribute_taxonomy_names(), true ) ) {
				$tax_query[] = array(
					'taxonomy' => $request['attribute'],
					'field'    => 'term_id',
					'terms'    => $request['attribute_term'],
				);
			}
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query; // WPCS: slow query ok.
		}

		// Filter featured.
		if ( is_bool( $request['featured'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'name',
				'terms'    => 'featured',
			);
		}

		// Filter by sku.
		if ( ! empty( $request['sku'] ) ) {
			$skus = explode( ',', $request['sku'] );
			// Include the current string as a SKU too.
			if ( 1 < count( $skus ) ) {
				$skus[] = $request['sku'];
			}

			$args['meta_query'] = $this->add_meta_query( // WPCS: slow query ok.
				$args, array(
					'key'     => '_sku',
					'value'   => $skus,
					'compare' => 'IN',
				)
			);
		}

		// Filter by tax class.
		if ( ! empty( $request['tax_class'] ) ) {
			$args['meta_query'] = $this->add_meta_query( // WPCS: slow query ok.
				$args, array(
					'key'   => '_tax_class',
					'value' => 'standard' !== $request['tax_class'] ? $request['tax_class'] : '',
				)
			);
		}

		// Price filter.
		if ( ! empty( $request['min_price'] ) || ! empty( $request['max_price'] ) ) {
			$args['meta_query'] = $this->add_meta_query( $args, wc_get_min_max_price_meta_query( $request ) );  // WPCS: slow query ok.
		}

		// Filter product in stock or out of stock.
		if ( is_bool( $request['in_stock'] ) ) {
			$args['meta_query'] = $this->add_meta_query( // WPCS: slow query ok.
				$args, array(
					'key'   => '_stock_status',
					'value' => true === $request['in_stock'] ? 'instock' : 'outofstock',
				)
			);
		}

		// Filter by on sale products.
		if ( is_bool( $request['on_sale'] ) ) {
			$on_sale_key = $request['on_sale'] ? 'post__in' : 'post__not_in';
			$on_sale_ids = wc_get_product_ids_on_sale();

			// Use 0 when there's no on sale products to avoid return all products.
			$on_sale_ids = empty( $on_sale_ids ) ? array( 0 ) : $on_sale_ids;

			$args[ $on_sale_key ] += $on_sale_ids;
		}

		// Force the post_type argument, since it's not a user input variable.
		if ( ! empty( $request['sku'] ) ) {
			$args['post_type'] = array( 'product', 'product_variation' );
		} else {
			$args['post_type'] = $this->post_type;
		}

		$args['meta_key'] = 'taq_review_score';
		$args['orderby'] =  'meta_value_num';
		$args['order'] = 'DESC';

		return $args;
	}
}

