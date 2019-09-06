<?php


/**
* Media API Controller
*
* @package litchi
*
* @author 
*/

defined( 'ABSPATH' ) || exit;

//require_once ABSPATH . 'wp-includes/rest-api/endpoints/class-wp-rest-attachments-controller.php';

/**
 * REST API Media controller class.
 *
 * @package Litchi/API
 * @extends WP_REST_Attachments_Controller
 */
class Litchi_REST_Media_Controller extends WP_REST_Attachments_Controller {

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
    protected $base = 'media';

    /**
     * Post type
     *
     * @var string
     */
    protected $post_type = 'media';

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

		// POST: /wp-json/litchi/v1/media/add-image-with-product

		register_rest_route( $this->namespace, '/' . $this->base . '/add-image-with-product', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_image_with_product' ),
			'permission_callback' => array( $this, 'create_item_permissions_check' ),
			'args' => array(
			)
		) );
		
		// POST: /wp-json/litchi/v1/media/add-image
		
		register_rest_route( $this->namespace, '/' . $this->base . '/add-image', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_image' ),
         'permission_callback' => array( $this, 'create_item_permissions_check' ),
			'args' => array(
			)
		) );
		
		
		// POST: /wp-json/litchi/v1/media/add-video
		
		register_rest_route( $this->namespace, '/' . $this->base . '/add-video', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_video' ),
         'permission_callback' => array( $this, 'create_item_permissions_check' ),
			'args' => array(
			)
		) );
    } // register_routes()

	public function create_image_with_product( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
		}

		$user     = wp_get_current_user();
		$attachment = parent::create_item($request);

		$attachment_id = $attachment->get_data()['id'];

		if ($attachment) {

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

			$product = get_post( $post_id );
			$response = rest_ensure_response( $product );
			$response->set_status( 201 );
			//$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $id ) ) );
			//$response -> set_data( $attachment );
			return $response;
		}
		
			
		if ( is_wp_error( $attachment ) ) {
			if ( 'db_update_error' === $attachment ->get_error_code() ) {
				$attachment ->add_data( array( 'status' => 500 ) );
			} else {
				$attachment ->add_data( array( 'status' => 400 ) );
			}
			return $attachment ;
		}
	}

	
	public function create_image( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
		}

		$user     = wp_get_current_user();
		return parent::create_item($request);
	}
	
	public function create_video( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
		}

		$files   = $request->get_file_params();
		$headers = $request->get_headers();
		
		$att = array();

		if ( ! empty( $files ) ) {
			$file['file'] = $files['cover'];
			$request->set_file_params($file);
			$cover = parent::create_item($request);
			
			
			$file['file'] = $files['video'];
			$request->set_file_params($file);
			$video = parent::create_item($request);
			
			$att['cover'] = $cover->get_data();
			$att['video'] = $video->get_data();
		}
		
		//$files   = $request->get_file_params();
		
		return $att;
	}
	
	public function create_item_permissions_check($request) {
		return true;
  }
}
