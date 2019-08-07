<?php


/**
* Products API Controller
*
* @package litchi
*
* @author 
*/

defined( 'ABSPATH' ) || exit;

//require_once ABSPATH . 'wp-includes/rest-api/endpoints/class-wp-rest-attachments-controller.php';

/**
 * REST API Products controller class.
 *
 * @package Litchi/API
 * @extends WP_REST_Controller
 */
class Litchi_REST_Product_Controller extends WP_REST_Controller {

	/**
     * Endpoint namespace
     *
     * @var string
     */
	protected $namespace = 'litchi/v1';

	/**
     * Route name
     *
     * @var string
     */
	protected $base = 'products';

	/**
     * Post type
     *
     * @var string
     */
	protected $post_type = 'product';

	/**
     * Constructor function
     *
     * @since 2.7.0
     *
     * @return void
     */
	public function __construct() {
		# code...
		/*
        $inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ;                  
        require_once $inc_dir. 'log.php';                  
        $this->logger = Logger::Init( Logger::DefaultLogFileHandler(), 15);*/
	}

	/**
     * Register all routes releated with media
     *
     * @return void
     */
	public function register_routes() {

		// POST: /wp-json/litchi/v1/products/add

		register_rest_route( $this->namespace, '/' . $this->base . '/add', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_item' ),
			'permission_callback' => array( $this, 'create_item_permissions_check' ),
			'args' => array(
			)
		) );


		register_rest_route( $this->namespace, '/' . $this->base . '/top-rated', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_top_rated_items' ),
			'permission_callback' => array( $this, 'get_top_rated_items_permissions_check' ),
			'args' => array(
			)
		) );
	} // register_routes()

	public function get_top_rated_items_permissions_check(){ return true; }

	public function get_top_rated_items( WP_REST_Request $request ) {
		$query_args = array(
			'posts_per_page' => 20,
			'no_found_rows'  => 1,
			// 			'post_status'    => 'publish',
			'post_type'      => 'dokan_slider',
			// 			'meta_key'       => '_wc_average_rating',
			// 			'orderby'        => 'meta_value_num',
			// 			'order'          => 'DESC',
			'meta_query'     => WC()->query->get_meta_query(),
			'tax_query'      => WC()->query->get_tax_query(),
		); // WPCS: slow query ok.


		$query = new WP_Query( $query_args );
		$sliders = array();

		if ( $query->have_posts() ) {

			$posts = $query -> posts;
			foreach( $posts as $post ) {
				$slider = array();
// 				$slider['data'] = $post;
				foreach ( $post as $key => $value ) {
					$slider[$key] = $value;
				}
				$slider_id = $post -> ID;
				$slides = get_post_meta( $slider_id, 'slide_detail' );
				$slider['slides'] = $slides;
				$sliders[] = $slider;

			}
		}

		return $sliders;
	}

	public function create_item( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
		}

		$post_id = wp_insert_post(array(
			'post_title' => 'Uploaded Product by Customer',
			'post_type' => 'product',
			'post_status' => 'private',
			'post_content' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
			'post_excerpt' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.'
		));

		// set product is simple/variable/grouped
		wp_set_object_terms( $post_id, 'simple', 'product_type' );
		//update_post_meta( $post_id, 'status', 'private' );
		update_post_meta( $post_id, '_visibility', 'visible' );
		update_post_meta( $post_id, '_stock_status', 'instock');
		update_post_meta( $post_id, 'total_sales', '0' );
		update_post_meta( $post_id, '_downloadable', 'no' );
		update_post_meta( $post_id, '_virtual', 'yes' );
		update_post_meta( $post_id, '_regular_price', '' );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_purchase_note', '' );
		update_post_meta( $post_id, '_featured', 'no' );
		update_post_meta( $post_id, '_weight', '11' );
		update_post_meta( $post_id, '_length', '11' );
		update_post_meta( $post_id, '_width', '11' );
		update_post_meta( $post_id, '_height', '11' );
		update_post_meta( $post_id, '_sku', 'SKU11' );
		update_post_meta( $post_id, '_product_attributes', array() );
		update_post_meta( $post_id, '_sale_price_dates_from', '' );
		update_post_meta( $post_id, '_sale_price_dates_to', '' );
		update_post_meta( $post_id, '_price', '0' );
		update_post_meta( $post_id, '_sold_individually', '' );
		update_post_meta( $post_id, '_manage_stock', 'yes' );
		// wc_update_product_stock($post_id, $single['qty'], 'set');
		update_post_meta( $post_id, '_backorders', 'no' );
		// update_post_meta( $post_id, '_stock', $single['qty'] );


		// check if user has uploaded any files
		if ( ! empty( $_FILES['file'] ) ) {

			// upload attachment and get attachment_id
			$attachment_id = $this -> wc_product_upload_attachment( $_FILES['file'] );

			if ( is_wp_error( $attachment_id ) ) {
				if ( 'db_update_error' === $attachment_id ->get_error_code() ) {
					$attachment_id ->add_data( array( 'status' => 500 ) );
				} else {
					$attachment_id ->add_data( array( 'status' => 400 ) );
				}
				return $attachment_id ;
			}

			$attachment = get_post( $attachment_id );


			do_action( 'rest_insert_attachment', $attachment, $request, true );

			// Include admin function to get access to wp_generate_attachment_metadata().
			require_once ABSPATH . 'wp-admin/includes/media.php';



			if ( isset( $request['alt_text'] ) ) {
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $request['alt_text'] ) );
			}


			$request->set_param( 'context', 'edit' );

			do_action( 'rest_after_insert_attachment', $attachment, $request, true );

			// And finally assign featured image to post
			set_post_thumbnail( $post_id, $attachment_id );

		}

		$product = get_post( $post_id );
		$response = rest_ensure_response( $product );
		$response->set_status( 201 );
		//$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $id ) ) );

		//$response -> set_data( $attachment );

		return $response;
	}

	private function wc_product_upload_attachment( $file_obj ) {

		// these files need to be included as dependencies when on the front end
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );

		return media_handle_sideload( $file_obj, 0 );
	}

	public function create_item_permissions_check($request) {
		return true;
	}
}
