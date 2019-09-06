<?php


/**
 * Options API Controller
 *
 * @package litchi
 *
 * @author
 */

defined('ABSPATH') || exit;

//require_once ABSPATH . 'wp-includes/rest-api/endpoints/class-wp-rest-attachments-controller.php';

/**
 * REST API options controller class.
 *
 * @package Litchi/API
 * @extends WP_REST_Controller
 */

class Litchi_REST_Options_Controller extends WP_REST_Controller
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
    protected $base = 'options';

    /**
     * Post type
     *
     * @var string
     */
    protected $post_type = 'options';

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


        register_rest_route($this->namespace, '/' . $this->base . '/options_list', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'options_list'),
            'permission_callback' => array($this, 'options_list_permissions_check'),
            'args' => array()
        ));
    } // register_routes()

    public function options_list_permissions_check()
    {
        return true;
    }

    public function options_list()
    {


       // global $wpdb;
      //  $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM wp_options WHERE option_name = 'dokan_rma' ") );
        return 'x';
	}

}
