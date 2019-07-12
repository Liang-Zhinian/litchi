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
use \Firebase\JWT\ExpiredException;

class Litchi_REST_JWT_Controller extends WP_REST_Controller {

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
    protected $base = 'jwt';

    /**
     * Post type
     *
     * @var string
     */
    protected $post_type = 'jwt';
    
    private static $TOKEN_BYTE_LENGTH = 32;

    /**
     * Constructor function
     *
     * @since 2.7.0
     *
     * @return void
     */
    public function __construct() {
        # code...
        
        //$inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ;                  
        //require_once $inc_dir. 'log.php';                  
        //$this->logger = Logger::Init( Logger::DefaultLogFileHandler(), 15);
    }

    /**
     * Register all routes releated with stores
     *
     * @return void
     */
    public function register_routes() {
        // POST: /wp-json/litchi/v1/jwt/refresh
        register_rest_route( $this->namespace, '/' . $this->base . '/refresh', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'refresh_token' ),
            'args' => array(
            
                'token' => array(
                    'description' => __( 'token', 'wordpress' ),
                    'required' => true,
                    'type'     => 'string',
                )
            )
        ) );

    } // register_routes()

    public function refresh_token(WP_REST_Request $request){  
        $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : false;
        /** First thing, check the secret key if not exist return a error*/
        if (!$secret_key) {
            return new WP_Error(
                'jwt_auth_bad_config',
                __('JWT is not configurated properly, please contact the admin', 'wp-api-jwt-auth'),
                array(
                    'status' => 403,
                )
            );
        } 
        $body = $request->get_json_params();

        $user_id = $this->get_user_id_from_refresh_token($body['token']);

        if (empty($user_id)) {
            return new WP_Error('api_api_bearer_auth_error_invalid_token',
            __('Invalid token.', 'api_api_bearer'), ['status' => 401]);
        }
        if (!is_user_member_of_blog($user_id)) {
            return new WP_Error('api_api_bearer_auth_wrong_blog',
            __('You are no member of this blog.', 'api_bearer_auth'), ['status' => 401]);
        }
        
        $user = get_user_by('id', $user_id);

        
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
                    'id' => $user_id,
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
            'refresh_token' => $body['token']
        );

        /** Let the user modify the data before send it back */
        return apply_filters('jwt_auth_token_before_dispatch', $data, $user);
    }

    private function get_user_id_from_refresh_token($token) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare('SELECT user_id
            FROM ' . $wpdb->base_prefix . 'user_tokens
            WHERE refresh_token = %s', $token));
    }

    private function generate_refresh_token(){
        return bin2hex(openssl_random_pseudo_bytes(32));
    
    }

}