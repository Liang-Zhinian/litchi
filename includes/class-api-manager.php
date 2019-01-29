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
            
        ) );

        // Init REST API routes.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
        add_filter( 'woocommerce_rest_prepare_product_object', array( $this, 'prepeare_product_response' ), 10, 3 );
        add_filter( 'woocommerce_api_product_response', array( $this, 'filter_woocommerce_api_product_response' ), 10, 4 );
        
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

    /**
     * Legacy: Prepare object for product response
     *
     * @since 1.2.0
     */
    function filter_woocommerce_api_product_response( $product_data, $product, $fields, $this_server ) { 
        // $product_data['vendor_id'] = get_post_field( 'post_author', $product->id);
        // $product_data['vendor_name'] = get_the_author_meta( 'display_name', $product_data['vendor_id']);

        $author_id = get_post_field( 'post_author', $product_data['id'] );

        $store = litchi()->vendor->get( $author_id );
        // $the_user = get_user_by( 'id', $author_id );;

        $product_data['store'] = array(
            'id'        => $store->get_id(),
            'name'      => $store->get_name(),
            'shop_name' => $store->get_shop_name(),
            'url'       => $store->get_shop_url(),
            'address'   => $store->get_address()
        );

        return $product_data;
    }

    /**
     * Prepare object for product response
     *
     * @since 2.8.0
     *
     * @return void
     */
    public function prepeare_product_response( $response, $object, $request ) {
        $data = $response->get_data();
        $author_id = get_post_field( 'post_author', $data['id'] );

        $store = litchi()->vendor->get( $author_id );
        // $the_user = get_user_by( 'id', $author_id );;

        $data['store'] = array(
            'id'        => $store->get_id(),
            'name'      => $store->get_name(),
            'shop_name' => $store->get_shop_name(),
            'url'       => $store->get_shop_url(),
            'address'   => $store->get_address()
        );

        $response->set_data( $data );
        return $response;
    }
}
