<?php

/**
* Express API Controller
*
* @package litchi
*
* @author 
*/

/** Requiere the JWT library. */
use \Firebase\JWT\JWT;

class Litchi_REST_Exp_Controller extends WP_REST_Controller {

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
	protected $base = 'exp';

	/**
     * Post type
     *
     * @var string
     */
	protected $post_type = 'exp';


	/**
     * Constructor function
     *
     * @since 2.7.0
     *
     * @return void
     */
	public function __construct() {
		# code...

		//         $inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ;                  
		//         require_once $inc_dir. 'log.php';                  
		//         $this->logger = Logger::Init( Logger::DefaultLogFileHandler(), 15);

		$inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ;                  
		require_once $inc_dir. 'juhe/settings.php';                       
		require_once $inc_dir. 'juhe/class-exp.php';
	}

	/**
     * Register all routes releated with stores
     *
     * @return void
     */
	public function register_routes() {
		// GET: /wp-json/litchi/v1/exp
		register_rest_route( $this->namespace, '/' . $this->base, array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_tracking_details' ),
			'permission_callback' => array( $this, 'get_tracking_details_permissions_check' ),
			'args' => array(
				'provider' => array(
					'description' => __( 'mobile number', 'woocommerce' ),
					'required' => true,
					'type'     => 'string',
				), 
				'number' => array(
					'description' => __( 'message', 'woocommerce' ),
					'required' => true,
					'type'     => 'string',
				),
			)

		) );
		
		// GET: /wp-json/litchi/v1/exp
		register_rest_route( $this->namespace, '/' . $this->base . '/providers', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_shipping_providers' ),
			'permission_callback' => array( $this, 'get_tracking_details_permissions_check' ),
			'args' => array(
// 				'provider' => array(
// 					'description' => __( 'mobile number', 'woocommerce' ),
// 					'required' => true,
// 					'type'     => 'string',
// 				), 
// 				'number' => array(
// 					'description' => __( 'message', 'woocommerce' ),
// 					'required' => true,
// 					'type'     => 'string',
// 				),
			)

		) );

	} // register_routes()

	public function get_tracking_details( WP_REST_Request $request ){
		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
		}

		$settings = new WP_Plugin_Juhe_Template_Settings();
		$api_key = $settings->get_exp_api_key();
		$provider = $request['provider'];
		$number = $request['number'];

		$exp = new Litchi_Juhe_Express($api_key);

		$result = $exp->query($provider,$number); //执行查询

		// 		if($result['error_code'] == 0){//查询成功
		// 			$list = $result['result']['list'];
		// 			print_r($list);
		// 		}else{
		// 			echo "获取失败，原因：".$result['reason'];
		// 		}
		if($result['error_code'] == 0){//查询成功
			$list = $result['result'];
			return new WP_REST_Response( $list, 200 );
		}else{ 
			$result['status'] = 400;
			return new WP_Error(
					'invalid_tracking_details',
					$result['reason'],
					$result
				);
		}

		return $result;
	}

	public function get_shipping_providers( WP_REST_Request $request ){
		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
		}

		$settings = new WP_Plugin_Juhe_Template_Settings();
		$api_key = $settings->get_exp_api_key();
// 		$provider = $request['provider'];
// 		$number = $request['number'];

		$exp = new Litchi_Juhe_Express($api_key);

		$result = $exp->getComs(); //执行查询

		if($result['error_code'] == 0){//查询成功
			$list = $result['result'];
			return new WP_REST_Response( $list, 200 );
		}else{
			$result['status'] = 400;
			return new WP_Error(
					'invalid_shipping_providers',
					$result['reason'],
					$result
				);
		}

// 		return $result;
	}

	private function create_response($return)
	{
		$response = new WP_REST_Response();
		$response->set_data($return);
		return $response;
	}

	function get_tracking_details_permissions_check( $request ) {

		return true;
	}
}