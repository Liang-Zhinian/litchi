<?php


/**
 * Expert API Controller
 *
 * @package litchi
 *
 * @author
 */

defined('ABSPATH') || exit;

//require_once ABSPATH . 'wp-includes/rest-api/endpoints/class-wp-rest-attachments-controller.php';

/**
 * REST API Expert controller class.
 *
 * @package Litchi/API
 * @extends WP_REST_Controller
 */

class Litchi_REST_Expert_Controller extends WP_REST_Controller
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
    protected $base = 'expert';

    /**
     * Post type
     *
     * @var string
     */
    protected $post_type = 'expert';

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


        register_rest_route($this->namespace, '/' . $this->base . '/expert_list', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'expert_list'),
            'permission_callback' => array($this, 'expert_list_permissions_check'),
            'args' => array()
        ));
    } // register_routes()

    public function expert_list_permissions_check()
    {
        return true;
    }

    public function expert_list()
    {

        global $wpdb;
        //$body = $request->get_json_params();
        $taq_review_title = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = ".$_GET['post_id']." and meta_key = 'taq_review_title' LIMIT 1", '_transient_doing_cron' ) );
       // $data=$rowmaybe_unserialize( $row->meta_value );
        $taq_review_summary = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE post_id = ".$_GET['post_id']." and meta_key = 'taq_review_summary' LIMIT 1", '_transient_doing_cron' ) );

        $data['taq_review_title']=$taq_review_title->meta_value;
        $data['taq_review_summary']=$taq_review_summary->meta_value;
        return $data;

	}

}
