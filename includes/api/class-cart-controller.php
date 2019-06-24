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
            'methods'             => WP_REST_Server::READABLE,
            // 'callback'            => array( $this, 'get_cart' ),
            // 'permission_callback' => array( $this, 'rest_edit_user_callback' ),
			'callback' => __CLASS__ . '::get_cart',
			'permission_callback' => __CLASS__ . '::rest_edit_user_callback',
        ) );
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
        global $WCFM, $wpdb;

		$id        = (int) $request['id'];
		$user_data = get_userdata( $id );

		if ( empty( $id ) || empty( $user_data->ID ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}
        $customer    = new WC_Customer( $user_data->ID );
        
        $cart = get_user_meta( $user_data->ID, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );
        
		// if ( $this->get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
		// 	return new WP_REST_Response( array(), 200 );
        // }
        
        $show_thumb = ! empty( $request['thumb'] ) ? $request['thumb'] : false;
        
	
		$cart = $cart['cart'];

        
        // Reset the packages
        $packages_reset = array();

        foreach(array_keys( $cart ) as $field) {
            $cart_item = $cart[$field];
            $product_id = $cart_item['product_id'];

            $_product = wc_get_product($product_id); //apply_filters( 'wc_cart_rest_api_cart_item_product', $cart_item['data'], $cart_item, $item_key );
                
            // Adds the product name as a new variable.
            $cart_item['product_name'] = $_product->get_name();
                
            // If main product thumbnail is requested then add it to each item in cart.
            if ( $show_thumb ) {
                $thumbnail_id = apply_filters( 'wc_cart_rest_api_cart_item_thumbnail', $_product->get_image_id(), $cart_item, $field );
                $thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, 'woocommerce_thumbnail' );
                
                // Add main product image as a new variable.
                $cart_item['product_image'] = esc_url( $thumbnail_src[0] );
            }

            $author_id = get_post_field( 'post_author', $product_id );

            $packages_reset[$author_id]['contents'][] = $cart_item;


            $store = litchi()->vendor->get( $author_id );
            $store_logo = $WCFM->wcfm_vendor_support->wcfm_get_vendor_logo_by_vendor( $author_id );
            
            $cart_item['store'] = array(
                'id'        => $store->get_id(),
                'name'      => $store->get_name(),
                'shop_name' => $store->get_shop_name(),
                'url'       => $store->get_shop_url(),
                'address'   => $store->get_address(),
                'logo'      => $store_logo
            );
            
            $packages_reset[$author_id]['store'] = array(
                'id'        => $store->get_id(),
                'name'      => $store->get_name(),
                'shop_name' => $store->get_shop_name(),
                'url'       => $store->get_shop_url(),
                'address'   => $store->get_address(),
                'logo'      => $store_logo
            );
            // $cart[$field] = $cart_item;
        }

    //$response->data['cart']       =  $packages_reset;        
		return new WP_REST_Response( $packages_reset, 200 );
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
    
    public static function rest_edit_user_callback( $data ) {
		return current_user_can( 'edit_user', $data['user_id'] );
	}
}