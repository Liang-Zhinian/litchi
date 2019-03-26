<?php

/**
* Cart API Controller
*
* @package litchi
*
* @author 
*/

class Litchi_REST_Product_Controller extends WP_REST_Controller {

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
    protected $base = 'cart';

    /**
     * Post type
     *
     * @var string
     */
    protected $post_type = 'cart';

    /**
     * Constructor function
     *
     * @since 2.7.0
     *
     * @return void
     */
    public function __construct() {
        # code...
    }

    /**
     * Register all routes releated with stores
     *
     * @return void
     */
    public function register_routes() {
        // GET: /wp-json/litchi/v1/cart
        register_rest_route( $this->namespace, '/customers'.'/(?P<id>[\d]+)/' . $this->base, array(
			'args' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
				),
				'thumb' => array(
					'default' => null
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_cart' ),
				// 'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
        ) );

        // register_rest_route( $this->namespace, '/' . $this->base . '/(?P<id>[\d]+)/', array(
        //     'args' => array(
        //         'id' => array(
        //             'description' => __( 'Unique identifier for the object.', 'litchi' ),
        //             'type'        => 'integer',
        //         ),
        //     ),
        //     array(
        //         'methods'             => WP_REST_Server::READABLE,
        //         'callback'            => array( $this, 'get_item' ),
        //         'args'                => $this->get_collection_params(),
        //         'permission_callback' => array( $this, 'get_single_product_permissions_check' ),
        //     ),
        //     array(
        //         'methods'             => WP_REST_Server::EDITABLE,
        //         'callback'            => array( $this, 'update_item' ),
        //         'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
        //         'permission_callback' => array( $this, 'update_product_permissions_check' ),
        //     ),
        //     array(
        //         'methods'             => WP_REST_Server::DELETABLE,
        //         'callback'            => array( $this, 'delete_item' ),
        //         'permission_callback' => array( $this, 'delete_product_permissions_check' ),
        //         'args'                => array(
        //             'force' => array(
        //                 'type'        => 'boolean',
        //                 'default'     => false,
        //                 'description' => __( 'Whether to bypass trash and force deletion.', 'litchi' ),
        //             ),
        //         ),
        //     )
        // ) );

        // register_rest_route( $this->namespace, '/' . $this->base . '/summary', array(
        //     array(
        //         'methods'             => WP_REST_Server::READABLE,
        //         'callback'            => array( $this, 'get_product_summary' ),
        //         'permission_callback' => array( $this, 'get_product_summary_permissions_check' ),
        //     ),
        // ) );
    } // register_routes()


    
	/**
	 * Get cart.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @version 1.0.6
	 * @param   array $request
	 * @return  WP_REST_Response
	 */
	public function get_cart( $request = array() ) {
		$id        = (int) $request['id'];
		$user_data = get_userdata( $id );

		if ( empty( $id ) || empty( $user_data->ID ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		$customer    = new WC_Customer( $user_data->ID );
        // $cart = WC()->cart->get_cart();
        $cart = get_user_meta( $user_data->ID, '_woocommerce_persistent_cart_' . get_current_blog_id(), true )['cart'];
        
		// if ( $this->get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
		// 	return new WP_REST_Response( array(), 200 );
        // }
        
        $show_thumb = ! empty( $request['thumb'] ) ? $request['thumb'] : false;
        
		foreach ( $cart as $item_key => $cart_item ) {
            my_log_file($item_key, 'get_cart: $item_key');
            my_log_file($cart_item, 'get_cart: $cart_item');
            $_product = wc_get_product($cart_item['product_id']); //apply_filters( 'wc_cart_rest_api_cart_item_product', $cart_item['data'], $cart_item, $item_key );
            
			// Adds the product name as a new variable.
            $cart[$item_key]['product_name'] = $_product->get_name();
            
			// If main product thumbnail is requested then add it to each item in cart.
			if ( $show_thumb ) {
				$thumbnail_id = apply_filters( 'wc_cart_rest_api_cart_item_thumbnail', $_product->get_image_id(), $cart_item, $item_key );
                $thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, 'woocommerce_thumbnail' );
                
				// Add main product image as a new variable.
                $cart[$item_key]['product_image'] = esc_url( $thumbnail_src[0] );
            }
            
        //     $cart[$item_key]['customer'] = $customer;
        }
        
		return new WP_REST_Response( $cart, 200 );
    } // END get_cart()
    
	/**
	 * Get cart contents count.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $data
	 * @return string|WP_REST_Response
	 */
	public function get_cart_contents_count( $data = array() ) {
		$count = WC()->cart->get_cart_contents_count();
		$return = ! empty( $data['return'] ) ? $data['return'] : '';
		if ( $return != 'numeric' && $count <= 0 ) {
			return new WP_REST_Response( __( 'There are no items in the cart!', 'litchi' ), 200 );
		}
		return $count;
	} // END get_cart_contents_count()
}