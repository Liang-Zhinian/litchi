<?php

/**
 * Cart API Controller
 *
 * @package litchi
 *
 * @author
 */

/** Requiere the JWT library. */
use \Firebase\JWT\JWT;

global $wpdb;

class Litchi_REST_Social_Controller extends WP_REST_Controller
{

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
	protected $base = 'social';

	/**
     * Post type
     *
     * @var string
     */
	protected $post_type = 'social';

	/**
     * Constructor function
     *
     * @since 2.7.0
     *
     * @return void
     */
	public function __construct()
	{
		# code...
	}

	/**
     * Register all routes releated with stores
     *
     * @return void
     */
	public function register_routes()
	{
		// POST: /wp-json/litchi/v1/social/login
		register_rest_route( $this->namespace, '/' . $this->base . '/login', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, '__social_login' ),
			'args'     => array(
				'social_id' => array(
					'description' => __( 'Unique identifier for the social account.', 'litchi' ),
					'type'        => 'string',
					'required'    => true,
				),
				'user_email' => array(
					'description' => __( 'Email address for the social account.', 'litchi' ),
					'type'        => 'string',
					'required'    => true,
				),
			),
		) );

		// POST: /wp-json/litchi/v1/social/register
		register_rest_route( $this->namespace, '/' . $this->base . '/register', array(
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => array( $this, '__social_registration' ),
			'args'     => array(
				'social_id' => array(
					'description' => __( 'Unique identifier for the social account.', 'litchi' ),
					'type'        => 'string',
					'required'    => true,
				),
				'user_email' => array(
					'description' => __( 'Email address for the social account.', 'litchi' ),
					'type'        => 'string',
					'required'    => true,
				),
				'first_name' => array(
					'description' => __( 'The user\'s first name.', 'litchi' ),
					'type'        => 'string',
					'required'    => false,
				),
				'last_name' => array(
					'description' => __( 'The user\'s last name.', 'litchi' ),
					'type'        => 'string',
					'required'    => false,
				),
				'nickname' => array(
					'description' => __( 'The user\'s nickname.', 'litchi' ),
					'type'        => 'string',
					'required'    => false,
				),
				'description' => array(
					'description' => __( 'Description.', 'litchi' ),
					'type'        => 'string',
					'required'    => false,
				),
			),
		) );
	} // register_routes()

	/**
     * Get cart.
     *
     * @access  public
     * @since   1.0.0
     * @version 1.0.6
     * @param   array $data
     * @return  WP_REST_Response
     */
	public function __social_login($data)
	{


		// Expects social_id or user_email
		if (!isset($data['user_email']) && !isset($data['social_id'])) {
			return new WP_Error( 'rest_arguments_invalid', __( 'Expects social_id or user_email.' ), array( 'status' => 401 ) );
		}


		$user = $this->__user_exists_check( $data );


		if (is_wp_error($user)) {
			return $user;
		}

		if( !$user['user'] ) {
			// return new WP_Error( 'No User', __( 'Not a valid user' ), array( 'status' => 401 ) );
			$this -> __social_registration($data);
			$user = $this->__user_exists_check( $data );
		}

		$user = $user['user'];

		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID );
		do_action( 'wp_login', $user->user_login, $user );

		$jwt = $this -> __generateJWT($user);

		if (is_wp_error($jwt)) {
			return new WP_Error('Login Error', __($jwt->get_error_message()), array('status' => 400));
		}

		$user_reset = array();

		/*
        foreach( array_keys( $user ) as $item_key => $item_value ){
        $user_reset[$item_key] = $item_value;
        }*/
		//$user_reset['user'] = $user;	

		foreach( $jwt as $item_key => $item_value ){
			$user_reset[$item_key] = $item_value;
		}

		//$user_reset['jwt'] = $jwt;

		return $this->create_response( $user_reset );
	}

	public function __social_registration($data)
	{
		// Expects social_id, user_email, and other user_info per WP user data


		$user = $this->__user_exists_check( $data );

		if( $user['user'] ) {
			return $this->create_response( $this->__social_login( $data ) );
		}

		$user_id = $this->__create_user( $data );

		if( is_wp_error( $user_id ) ) {
			return new WP_Error( 'Registration Error', __( $user_id->get_error_message() ), array( 'status' => 400 ) );
		}

		$user = get_user_by( 'id', $user_id );
		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID );
		do_action( 'wp_login', $user->user_login, $user );

		return $this->create_response( $user );
	}

	private function __user_exists_check($data)
	{
		// check if user exists in WP or DB
		$return = array('user' => false);

		if (isset($data['user_email'])) {
			$email_check = email_exists($data['user_email']);
			if ($email_check) {
				$db_user = $this->__user_db_check($data['social_id']);
				if (!$db_user) {
					$this->__create_user_db($email_check, $data['social_id']);
				}
				$return['user'] = get_user_by('id', $email_check);
			}
		}

		if (isset($data['social_id']) && $return['user'] == false) {
			$db_user = $this->__user_db_check($data['social_id']);
			if ($db_user) {
				$return['user'] = get_user_by('id', $db_user->wp_user_id);
			}
		}

		if (!isset($data['social_id']) && !isset($data['user_email'])) {
			return new WP_Error('No Data', __('Expecting social_id or user_email'), array('status' => 400));
		}

		return $return;

	}

	private function __user_db_check($social_id)
	{
		// Check wp_social_api table for user
		global $wpdb;
		$table_name = $wpdb->prefix . 'wp_api_social';

		$db_user_row = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table_name WHERE social_id = %s ",
			$social_id
		));

		return $db_user_row;

	}

	public function __create_user($data)
	{
		$random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);

		if (isset($data['nickname'])) {
			$username = str_replace(' ', '_', $data['nickname']);
		} else {
			$username = 'social_' . $data['social_id'];
		}
		$username = strtolower($username);

		if (isset($data['user_email'])) {
			$user_email = $data['user_email'];
		} else {
			$user_email = $data['social_id'] . '@' . $_SERVER['SERVER_NAME'];
		}

		$user_id = wp_create_user($username, $random_password, $user_email);

		if (is_wp_error($user_id)) {
			return new WP_Error('Create Error', __($user_id->get_error_message()), array('status' => 401));
		}

		$user_update = array('ID' => $user_id);

		if (isset($data['first_name'])) {
			$user_update['first_name'] = $data['first_name'];
		}

		if (isset($data['last_name'])) {
			$user_update['last_name'] = $data['last_name'];
		}

		if (isset($data['description'])) {
			$user_update['description'] = $data['description'];
		}

		if (isset($data['nickname'])) {
			$user_update['user_nicename'] = $data['nickname'];
		}

		$update_user = wp_update_user($user_update);

		if (is_wp_error($update_user)) {
			return new WP_Error('Update Error', __($update_user->get_error_message()), array('status' => 400));
		}

		$this->__create_user_db($user_id, $data['social_id']);

		return $user_id;
	}

	private function __create_user_db($user_id, $social_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'wp_api_social';

		$db = $wpdb->insert(
			$table_name,
			array(
				'created_time' => current_time('mysql'),
				'social_id' => $social_id,
				'wp_user_id' => $user_id,
			),
			array(
				'%s',
				'%s',
				'%d',
			));
	}

	public function __user_delete($user_id)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'wp_api_social';

		$db = $wpdb->delete(
			$table_name,
			array(
				'wp_user_id' => $user_id,
			),
			array(
				'%d',
			));

	}

	private function create_response($return)
	{
		$response = new WP_REST_Response();
		$response->set_data($return);
		return $response;
	}

	private function __generateJWT($user) {

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

	private function __generateBearerToken($user){

		require_once ABSPATH . 'wp-content/plugins/api-bearer-auth/db.php';
		$db = new API_Bearer_Auth_Db();
		// Update access en refresh tokens
		if (($result = $db->login($user->ID)) !== false) {

			return [
				'access_token' => $result['access_token'],
				'expires_in' => $result['expires_in'],
				'refresh_token' => $result['refresh_token'],
				//'issued_at' => time()
			];
		}

		return new WP_Error('api_api_bearer_auth_create_token',
							__('Error creating tokens.', 'api_api_bearer'));
	}
}
