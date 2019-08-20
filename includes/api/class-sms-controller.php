<?php

/**
* SMS API Controller
*
* @package litchi
*
* @author 
*/

/** Requiere the JWT library. */
use \Firebase\JWT\JWT;

class Litchi_REST_Sms_Controller extends WP_REST_Controller {

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
	protected $base = 'sms';

	/**
     * Post type
     *
     * @var string
     */
	protected $post_type = 'sms';


	//     protected $sms_sender = null;

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

	}

	/**
     * Register all routes releated with stores
     *
     * @return void
     */
	public function register_routes() {
		// POST: /wp-json/litchi/v1/sms/send-message
		register_rest_route( $this->namespace, '/' . $this->base . '/send-message', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'sendMessage' ),
			'args' => array(
				'mobile' => array(
					'description' => __( 'mobile number', 'woocommerce' ),
					'required' => true,
					'type'     => 'string',
				), 
				'message' => array(
					'description' => __( 'message', 'woocommerce' ),
					'required' => true,
					'type'     => 'string',
				),
			)

		) );


		// POST: /wp-json/litchi/v1/sms/send-vercode
		register_rest_route( $this->namespace, '/' . $this->base . '/send-vercode', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'sendVerCode' ),
			'args' => array(
				'mobile' => array(
					'description' => __( 'mobile number', 'woocommerce' ),
					'required' => true,
					'type'     => 'string',
				), 
				'vercode' => array(
					'description' => __( 'vercode', 'woocommerce' ),
					'required' => true,
					'type'     => 'string',
				),
			)

		) );


		// POST: /wp-json/litchi/v1/sms/users
		register_rest_route( $this->namespace, '/' . $this->base . '/users', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_users' ),
			'args' => array(
				'mobile' => array(
					'description' => __( 'mobile number', 'woocommerce' ),
					'required' => true,
					'type'     => 'string',
				), 
			)

		) );

		// POST: /wp-json/litchi/v1/sms/register
		register_rest_route( $this->namespace, '/' . $this->base . '/register', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'register' ),
			'args' => array(
				'mobile' => array(
					'description' => __( 'mobile number', 'woocommerce' ),
					'required' => true,
					'type'     => 'string',
				), 
			)

		) );

		// POST: /wp-json/litchi/v1/sms/login
			register_rest_route( $this->namespace, '/' . $this->base . '/login', array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'login' ),
				'args' => array(
					'mobile' => array(
						'description' => __( 'mobile number', 'woocommerce' ),
						'required' => true,
						'type'     => 'string',
					), 
				)

			) );

	} // register_routes()


	/**
	 * sendMessage.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @version 1.0.6
	 * @param   array $request
	 * @return  WP_REST_Response
	 */
	public function sendMessage( WP_REST_Request $request ) {

		$body = $request->get_json_params();                 
		// 		return $body['mobile'];
		$inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ; 
		require_once $inc_dir. 'class-sms-sender.php';   
		$sms_sender = new Litchi_Sms_Sender();

		$result = $sms_sender->sendMessage($body['mobile']);

		return new WP_REST_Response( $result, 200 );
	} // END sendMessage()



	/**
	 * sendVerCode.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @version 1.0.6
	 * @param   array $request
	 * @return  WP_REST_Response
	 */
	public function sendVerCode( WP_REST_Request $request ) {

		$body = $request->get_json_params();                 
		// 		return $body['mobile'];
		$inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ; 
		require_once $inc_dir. 'class-sms-sender.php';   
		$sms_sender = new Litchi_Sms_Sender();

		$result = $sms_sender->sendVerCode($body['mobile'], '179045', $body['vercode']);
		

		return new WP_REST_Response( $result, 200 );
	} // END sendVerCode()

	public function get_users( WP_REST_Request $request ){


		$meta_key = "Telephone";
		$meta_value = $request['mobile'];


		$user = reset(
			get_users(
				array(
					'meta_key' => $meta_key,
					'meta_value' => $meta_value,
					'number' => 1,
					'count_total' => false
				)
			)
		);

		return $user;
	}

	public function get_user_by_mobile( $mobile = null ){

		$meta_key = "Telephone";
		$meta_value = $mobile;

		$user = reset(
			get_users(
				array(
					'meta_key' => $meta_key,
					'meta_value' => $meta_value,
					'number' => 1,
					'count_total' => false
				)
			)
		);

		return $user;
	}

	public function register( WP_REST_Request $request ) {
		$response = array();
		$parameters = $request->get_json_params();
		$mobile = sanitize_text_field($parameters['mobile']);
		$username = 'user_'.$mobile; //sanitize_text_field($parameters['username']);

		$error = new WP_Error();
		if (empty($mobile)) {
			$error->add(400, __("Mobile field 'username' is required.", 'wp-rest-user'), array('status' => 400));
			return $error;
		}

		$user = $this->get_user_by_mobile($mobile);
		if (!$user || !($user->ID)) {
			$random_password = wp_generate_password( $length = 12, $include_standard_special_chars = false );
			$dummy_email = $mobile.'@invalid.com';
			$user_id = wp_create_user($username, $password, $dummy_email);
			if (!is_wp_error($user_id)) {
				// Ger User Meta Data (Sensitive, Password included. DO NOT pass to front end.)
				$user = get_user_by('id', $user_id);
				// $user->set_role($role);
				$user->set_role('subscriber');
				// WooCommerce specific code
				if (class_exists('WooCommerce')) {
					$user->set_role('customer');
				}
				// Ger User Data (Non-Sensitive, Pass to front end.)
				$response['code'] = 200;
				$response['message'] = __("User '" . $username . "' Registration was Successful", "wp-rest-user");
			} else {
				return $user_id;
			}
		} else {
			$error->add(406, __("Mobile already exists, please try 'Reset Password'", 'wp-rest-user'), array('status' => 400));
			return $error;
		}
		return new WP_REST_Response($response, 123);
	}

	public function login( WP_REST_Request $request ) {
		$parameters = $request->get_json_params();
		$mobile = sanitize_text_field($parameters['mobile']);
		$vercode = sanitize_text_field($parameters['vercode']);
		
		// Expects social_id or user_email
		if (empty($mobile)) {
			return new WP_Error( 'rest_arguments_invalid', __( 'Expects mobile.' ), array( 'status' => 401 ) );
		}		
		if (empty($vercode)) {
			return new WP_Error( 'rest_arguments_invalid', __( 'Expects vercode.' ), array( 'status' => 401 ) );
		}

		$inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ; 
		require_once $inc_dir. 'class-sms-sender.php';   
		$sms_sender = new Litchi_Sms_Sender();
		$sms = $sms_sender->get_sms_db($mobile, $vercode, 'vercode');

		if ( !$sms ) {
			return new WP_Error( 'authentication_failed', __( 'Invalid vercode.', 'litchi' ), array( 'status' => 401 ) );
		}
		$vercode_time = strtotime($sms->created_time);
		$now = strtotime(current_time('mysql'));
		$diff = ceil(($now - $vercode_time) / 60);
		//return $sms->created_time . ' | ' . current_time('mysql') . ' | ' . $diff;
		if ( $diff > 4 ) {
			return new WP_Error( 'authentication_failed', __( 'Expired vercode.', 'litchi' ), array( 'status' => 401 ) );
		}
			
		$user = $this->get_user_by_mobile($mobile);
		
		if (!$user){
			return new WP_Error( 'authentication_failed', __( 'Invalid mobile.', 'litchi' ), array( 'status' => 401 ) );
		}

		if (is_wp_error($user)) {
			return $user;
		}


		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID );
		do_action( 'wp_login', $user->user_login, $user );

		$jwt = $this -> generateJWT($user);

		if (is_wp_error($jwt)) {
			return new WP_Error('Login Error', __($jwt->get_error_message()), array('status' => 400));
		}

		$user_reset = array();

		foreach( $jwt as $item_key => $item_value ){
			$user_reset[$item_key] = $item_value;
		}

		return $this->create_response( $user_reset );
	}

	private function create_response($return)
	{
		$response = new WP_REST_Response();
		$response->set_data($return);
		return $response;
	}
	
	private function generateJWT($user) {

		// generate & return jwt
		$secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;


		/** If the authentication fails return a error*/
		if (is_wp_error($user)) {
			$error_code = $user->get_error_code();
			return new WP_Error(
				'[jwt_auth] ' . $error_code,
				$user->get_error_message($error_code),
				array(
					'status' => 403,
				)
			);
		}

		/** Valid credentials, the user exists create the according Token */
		$issuedAt = time();
		$notBefore = apply_filters('jwt_auth_not_before', $issuedAt, $issuedAt);
		$expire = apply_filters('jwt_auth_expire', $issuedAt + (DAY_IN_SECONDS * 7), $issuedAt);

		$token = array(
			'iss' => get_bloginfo('url'),
			'iat' => $issuedAt,
			'nbf' => $notBefore,
			'exp' => $expire,
			'data' => array(
				'user' => array(
					'id' => $user->data->ID,
				),
			),
		);

		/** Let the user modify the token data before the sign. */
		$token = JWT::encode(apply_filters('jwt_auth_token_before_sign', $token, $user), $secret_key);


		/** The token is signed, now create the object with no sensible user data to the client*/
		$data = array(
			'token' => $token,
			'user_email' => $user->data->user_email,
			'user_nicename' => $user->data->user_nicename,
			'user_display_name' => $user->data->display_name,
		);

		/** Let the user modify the data before send it back */
		$jwt = apply_filters('jwt_auth_token_before_dispatch', $data, $user);

		return $jwt;
	}

	function send_sms_permissions_check(){
		return true;
	}
}