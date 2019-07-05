<?php

/**
 * API_Registrar class
 */
class Litchi_API_Manager {

    /**
     * Class dir and class name mapping
     *
     * @var array
     */
    protected $class_map;

    /**
     * Constructor
     */
    public function __construct() {
        if ( ! class_exists( 'WP_REST_Server' ) ) {
            return;
        }

		$inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/';

        // require_once LITCHI_DIR . '/includes/api/admin/class-admin-controller.php';

        $this->class_map = apply_filters( 'litchi_rest_api_class_map', array(
            $inc_dir . 'api/class-cart-controller.php'                 => 'Litchi_REST_Product_Controller',
            $inc_dir . 'api/class-social-controller.php'                 => 'Litchi_REST_Social_Controller',
            $inc_dir . 'api/class-wechat-controller.php'                 => 'Litchi_REST_WeChat_Controller',
            $inc_dir . 'api/class-wcfmmarketplace-reports-controller.php'                 => 'Litchi_REST_Wcfmmp_Reports_Controller',
            $inc_dir . 'api/class-jwt-controller.php'                 => 'Litchi_REST_JWT_Controller',
            
        ) );

        // Init REST API routes.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
    }

    /**
     * Register REST API routes.
     *
     * @since 1.2.0
     */
    public function register_rest_routes() {

        foreach ( $this->class_map as $file_name => $controller ) {
            require_once $file_name;
            $controller = new $controller();
            $controller->register_routes();
        }
    }
    

}
