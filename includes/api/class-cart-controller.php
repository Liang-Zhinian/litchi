<?php

/**
* Cart API Controller
*
* @package litchi
*
* @author 
*/

class Litchi_REST_Cart_Controller extends WP_REST_Controller {

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
        register_rest_route( $this->namespace, '/' . $this->base, array(
			'args' => array(
				'thumb' => array(
					'default' => null
				),
			),
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_cart' ),
        ) );

        
		// Remove Items - /wp-json/litchi/v1/cart/cart-items (DELETE)
		register_rest_route( $this->namespace, '/' . $this->base . '/cart-items', array(
			'args' => array(
				'cart_item_keys' => array(
					'description' => __( 'The cart item key is what identifies the item in the cart.', 'cart-rest-api-for-woocommerce' ),
					'type'        => 'string',
				),
			),
			array(
				'methods'  => WP_REST_Server::DELETABLE,
				'callback' => array( $this, 'remove_items' ),
			),
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

        $cart = WC()->cart->get_cart();
        
		// if ( $this->get_cart_contents_count( array( 'return' => 'numeric' ) ) <= 0 ) {
		// 	return new WP_REST_Response( array(), 200 );
        // }
        
        $show_thumb = ! empty( $request['thumb'] ) ? $request['thumb'] : false;
        
	


        // Reset the packages
        $packages_reset = array();

        foreach ( $cart as $item_key => $cart_item ) {

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


            $vendor = new Litchi_Vendor_Manager();
            $store = $vendor->get( $author_id );
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
        }
      
		return new WP_REST_Response( $packages_reset, 200 );

    } // END get_cart()

    
	/**
	 * Remove Item in Cart.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @version 1.0.3
	 * @param   array $data
	 * @return  WP_Error|WP_REST_Response
	 */
	public function remove_items( $data = array() ) {
		$cart_item_keys = ! isset( $data['cart_item_keys'] ) ? '0' : wc_clean( $data['cart_item_keys'] );

		if ( $cart_item_keys != '0' ) {
            $key_array = explode(',', $cart_item_keys);

            foreach($key_array as $cart_item_key) {

                if ( WC()->cart->remove_cart_item( $cart_item_key ) ) {
                    continue;
                } else {
                    return new WP_ERROR( 'wc_cart_rest_can_not_remove_item', __( 'Unable to remove item from cart.', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
                }
            }

            return new WP_REST_Response( __( 'Item has been removed from cart.', 'cart-rest-api-for-woocommerce' ), 200 );
		} else {
			return new WP_ERROR( 'wc_cart_rest_cart_item_key_required', __( 'Cart item key is required!', 'cart-rest-api-for-woocommerce' ), array( 'status' => 500 ) );
		}
	} // END remove_item()
    
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