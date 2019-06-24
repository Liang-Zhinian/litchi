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

        // require_once LITCHI_DIR . '/includes/api/admin/class-admin-controller.php';

        $this->class_map = apply_filters( 'litchi_rest_api_class_map', array(
            // LITCHI_DIR . '/includes/api/class-store-controller.php'                   => 'Litchi_REST_Store_Controller',
            // LITCHI_DIR . '/includes/api/class-product-controller.php'                 => 'Litchi_REST_Product_Controller',
            LITCHI_DIR . '/includes/api/class-cart-controller.php'                 => 'Litchi_REST_Product_Controller',
            LITCHI_DIR . '/includes/api/class-social-controller.php'                 => 'Litchi_REST_Social_Controller',
            // LITCHI_DIR . '/includes/api/class-customer-controller.php'                 => 'Litchi_REST_Customer_Controller',
            LITCHI_DIR . '/includes/api/class-wechat-controller.php'                 => 'Litchi_REST_WeChat_Controller',
            LITCHI_DIR . '/includes/api/class-wcfmmarketplace-reports-controller.php'                 => 'Litchi_REST_Wcfmmp_Reports_Controller',
            // LITCHI_DIR . '/includes/api/class-wcfmmarketplace-order-controller.php'                 => 'Litchi_REST_Wcfmmp_Order_Controller',
            
        ) );

        // Init REST API routes.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
        // add_filter( 'woocommerce_rest_prepare_customer', array( $this, 'prepeare_customer_response' ), 10, 3 );
        
        
        // add_filter( 'pre_get_posts', 'my_modify_main_query' );
        // add_filter( 'woocommerce_product_data_store_cpt_get_products_query', 'handle_custom_query_var', 10, 2 );
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
