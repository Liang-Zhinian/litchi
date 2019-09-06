<?php


/**
 * Information API Controller
 *
 * @package litchi
 *
 * @author
 */

defined('ABSPATH') || exit;

//require_once ABSPATH . 'wp-includes/rest-api/endpoints/class-wp-rest-attachments-controller.php';

/**
 * REST API Information controller class.
 *
 * @package Litchi/API
 * @extends WP_REST_Controller
 */

class Litchi_REST_Information_Controller extends WP_REST_Controller
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
    protected $base = 'information';

    /**
     * Post type
     *
     * @var string
     */
    protected $post_type = 'information';

    /**
     * Constructor function
     *
     * @return void
     * @since 2.7.0
     *
     */
    public function __construct()
    {
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
    public function register_routes()
    {

        // POST: /wp-json/litchi/v1/products/add


        register_rest_route($this->namespace, '/' . $this->base . '/create_information', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'create_information'),
            'permission_callback' => array($this, 'create_information_permissions_check'),
            'args' => array()
        ));
    } // register_routes()

    public function create_information_permissions_check()
    {
        return true;
    }

    public function create_information(WP_REST_Request $request)
    {

        $body = $request->get_json_params();

        global $wpdb;
	if(empty($body['user_id'])){$user_id=0;}else{$user_id=$body['user_id'];}
        $eqno=$body['value']['DeviceInfo']['Serial'];
        $action=$body['action'];
        $longitude=$body['value']['coords']['longitude'];
        $latitude=$body['value']['coords']['latitude'];
        $table = "wp_wp_api_information";
        $data_array = array(
            'ip'=> $body['value']['ip'],  //ip
            'longitude'=>$longitude,  //经度
            'latitude'=>$latitude,  //纬度
            'eqno'=>$eqno,
            'action'=>$action,
            'wp_user_id'=>$user_id,
            'created_time'=>date("Y-m-d H:i:s")
        );



        return $wpdb->insert($table,$data_array);
	}

}
