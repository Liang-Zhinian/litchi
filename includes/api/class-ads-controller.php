<?php


/**
* Ads API Controller
*
* @package litchi
*
* @author 
*/

defined( 'ABSPATH' ) || exit;

//require_once ABSPATH . 'wp-includes/rest-api/endpoints/class-wp-rest-attachments-controller.php';

/**
 * REST API Ads controller class.
 *
 * @package Litchi/API
 * @extends WP_REST_Controller
 */
class Litchi_REST_Ads_Controller extends WP_REST_Controller {

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
	protected $base = 'ads';

	/**
     * Post type
     *
     * @var string
     */
	protected $post_type = 'ads';

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


		register_rest_route( $this->namespace, '/' . $this->base, array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_items' ),
			'permission_callback' => array( $this, 'get_items_permissions_check' ),
			'args' => array(
			)
		) );
	} // register_routes()

	public function get_items_permissions_check(){ return true; }

	public function get_items( WP_REST_Request $request ) {
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

}
