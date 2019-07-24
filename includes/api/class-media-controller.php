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
