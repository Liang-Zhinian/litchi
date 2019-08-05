<?php

defined( 'ABSPATH' ) || exit;



/////////////////////////////////
////////////////////////////// shipping /////////////////////////////////////////////
/* add phone attribute to additional_shipping meta data */
add_action( 'rest_api_init', 'slug_register_customer_fields' );
function slug_register_customer_fields() {

	register_rest_field( 'customer', 'shipping',
						array(
							'get_callback'    => 'get_customerMeta',
							'update_callback' => 'update_customerMeta',
							'schema'          => array(
								'shipping' => additional_shipping_fields()
							),
						)
					   );

	register_rest_field( 'customer', 'additional_shipping',
						array(
							'get_callback'    => 'get_customerShippingListMeta',
							'update_callback' => 'update_customerShippingListMeta',
							'schema'          => array(
								'additional_shipping' => array(
									'description' => __( 'Customer shipping data.', 'woocommerce' ),
									'type'        => 'object',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => false,
									'properties'  => get_shipping_schema()
								)
							),
						)
					   );

	/*
        register_rest_field( 'customer', 'cart',
            array(
                //'get_callback'    => 'get_customerCartData',
                'update_callback' => 'update_customerAddToCart',
                'schema'          => array(
                    'cart' => array(
                        'description' => __( 'Customer cart data.', 'woocommerce' ),
                        'type'        => 'object',
                        'context'     => array( 'view', 'edit' ),
                        'readonly'    => false,
                        'properties'  => array(
                            'product_id' => array (
                                'description' => __( 'product_id', 'woocommerce' ),
                                'type' => 'integer',
                                'context' => array( 'view', 'edit' ),
                            ),
                            'quantity' => array (
                                'description' => __( 'quantity', 'woocommerce' ),
                                'type' => 'integer',
                                'context' => array( 'view', 'edit' ),
                            ),
                            'variation_id' => array (
                                'description' => __( 'variation_id', 'woocommerce' ),
                                'type' => 'integer',
                                'context' => array( 'view', 'edit' ),
                            )
                        )
                    )
                ),
            )
        );*/
}

function update_customerAddToCart($value,$data,$field_name){
	$customerId = $data->ID;
	$cart = get_user_meta( $customerId, '_woocommerce_persistent_cart_' . get_current_blog_id(), true );

	// add_to_cart( $product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array() )
	$cart -> add_to_cart($value['product_id'], $value['quantity'], $value['variation_id'], $value['variation'], $value['cart_item_data']);

}

function additional_shipping_fields(){
	return array(
		'phone' => array (
			'description' => __( 'phone', 'woocommerce' ),
			'type' => 'string',
			'context' => array( 'view', 'edit' ),
		),
	);
}

function get_shipping_schema(){
	return array(
		'$schema'    => 'http://json-schema.org/draft-04/schema#',
		'title'      => 'shipping',
		'description' => __( 'List of shipping address data.', 'woocommerce' ),
		'type'        => 'object',
		'context'     => array( 'view', 'edit' ),
		'properties' => array(
			'first_name' => array (
				'description' => __( 'first_name', 'woocommerce' ),
				'type' => 'string',
				'context' => array( 'view', 'edit' ),
			),
			'last_name' => array (
				'description' => __( 'last_name', 'woocommerce' ),
				'type' => 'string',
				'context' => array( 'view', 'edit' ),
			),
			'company' => array (
				'description' => __( 'company', 'woocommerce' ),
				'type' => 'string',
				'context' => array( 'view', 'edit' ),
			),
			'address_1' => array (
				'description' => __( 'address_1', 'woocommerce' ),
				'type' => 'string',
				'context' => array( 'view', 'edit' ),
			),
			'address_2' => array (
				'description' => __( 'address_2', 'woocommerce' ),
				'type' => 'string',
				'context' => array( 'view', 'edit' ),
			),
			'city' => array (
				'description' => __( 'city', 'woocommerce' ),
				'type' => 'string',
				'context' => array( 'view', 'edit' ),
			),
			'state' => array (
				'description' => __( 'state', 'woocommerce' ),
				'type' => 'string',
				'context' => array( 'view', 'edit' ),
			),
			'postcode' => array (
				'description' => __( 'postcode', 'woocommerce' ),
				'type' => 'string',
				'context' => array( 'view', 'edit' ),
			),
			'country' => array (
				'description' => __( 'country', 'woocommerce' ),
				'type' => 'string',
				'context' => array( 'view', 'edit' ),
			),
			'phone' => array (
				'description' => __( 'phone', 'woocommerce' ),
				'type' => 'string',
				'context' => array( 'view', 'edit' ),
			),
		)
	);
}

function get_customerMeta( $data, $field_name, $request ) {
	global $wp_rest_additional_fields;

	$shipping = $data[$field_name];    

	// Customer shipping address.
	foreach ( array_keys( additional_shipping_fields() ) as $additional_field ) {
		$shipping[$additional_field] = get_user_meta( $data[ 'id' ], $field_name."_".$additional_field, true );
	}

	return $shipping;
};

function update_customerMeta($value,$data,$field_name) {

	$schema = get_shipping_schema();
	// Customer shipping address.
	foreach ( array_keys( $schema['properties'] ) as $field ) {
		update_user_meta( $data->ID, $field_name.'_'.$field, sanitize_text_field( $value[$field] ) );
	}

};

function get_customerShippingListMeta( $data, $field_name, $request ) {
	global $wp_rest_additional_fields;

	$shippingData = get_user_meta( $data[ 'id' ], $field_name, true );  

	// Customer shipping address.
	$shipping = array();
	foreach ($shippingData as $addr) {
		$shipping[] = get_shipping_address($addr, $field);
	}

	return $shipping;
};

function get_shipping_address($data){
	$schema = get_shipping_schema();
	$addr = array();
	foreach ( array_keys( $schema['properties'] ) as $field ) {
		$addr[$field] = $data[$field];
	}

	return $addr;
};

function update_customerShippingListMeta($value,$data,$field_name) {
	$shipping = array();

	foreach ($value as $addr) {
		$shipping[] = get_shipping_address($addr);
	}

	update_user_meta( $data->ID, $field_name, $shipping/*$value*/ );

};
/* end add phone attribute to additional_shipping meta data */



/////////////////////////////////
////////////////////////////// shopping cart /////////////////////////////////////////////

/* add shopping cart attribute to customer response */
add_filter( 'woocommerce_rest_prepare_customer',  'prepare_customers_response', 10, 3 );
/**
 * Add extra fields in customers response.
 *
 * @param WP_REST_Response $response The response object.
 * @param WP_User          $user     User object used to create response.
 *
 * @return WP_REST_Response
 */
function prepare_customers_response( $response, $user, $request ) {
	global $WCFM, $wpdb;
	$data = $response->get_data();

	$cart = get_user_meta( $data[ 'id' ], '_woocommerce_persistent_cart_' . get_current_blog_id(), true );

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
		// if ( $show_thumb ) {
		$thumbnail_id = apply_filters( 'wc_cart_rest_api_cart_item_thumbnail', $_product->get_image_id(), $cart_item, $field );
		$thumbnail_src = wp_get_attachment_image_src( $thumbnail_id, 'woocommerce_thumbnail' );

		// Add main product image as a new variable.
		$cart_item['product_image'] = esc_url( $thumbnail_src[0] );
		// }

		$author_id = get_post_field( 'post_author', $product_id );

		$packages_reset[$author_id]['contents'][] = $cart_item;

		$which_marketplace = which_marketplace();
		$store = array();
		$store_logo = '';

		if ($which_marketplace == 'wcfmmarketplace') {

			$vendor = new Litchi_Vendor_Manager();
			$store = $vendor->get( $author_id );
			$store_logo = $WCFM->wcfm_vendor_support->wcfm_get_vendor_logo_by_vendor( $author_id );
		} else if ($which_marketplace == 'dokan') {
			$store               = dokan()->vendor->get( $author_id );
			$store_logo = $store->get_avatar();
		}


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

	$response->data['cart']       =  $packages_reset;
	$response->data['display_name'] = $user->display_name;

	return $response;
}
/* end add shopping cart attribute to customer response */

add_action('rest_api_init', 'wp_rest_user_endpoints');
/**
 * Register a new user
 *
 * @param  WP_REST_Request $request Full details about the request.
 * @return array $args.
 **/
function wp_rest_user_endpoints($request) {
	/**
   * Handle Register User request.
   */
	register_rest_route('wp/v2', 'users/register', array(
		'methods' => 'POST',
		'callback' => 'wc_rest_user_endpoint_handler',
	));
}
function wc_rest_user_endpoint_handler($request = null) {
	$response = array();
	$parameters = $request->get_json_params();
	$username = sanitize_text_field($parameters['username']);
	$email = sanitize_text_field($parameters['email']);
	$password = sanitize_text_field($parameters['password']);
	// $role = sanitize_text_field($parameters['role']);
	$error = new WP_Error();
	if (empty($username)) {
		$error->add(400, __("Username field 'username' is required.", 'wp-rest-user'), array('status' => 400));
		return $error;
	}
	if (empty($email)) {
		$error->add(401, __("Email field 'email' is required.", 'wp-rest-user'), array('status' => 400));
		return $error;
	}
	if (empty($password)) {
		$error->add(404, __("Password field 'password' is required.", 'wp-rest-user'), array('status' => 400));
		return $error;
	}
	// if (empty($role)) {
	//  $role = 'subscriber';
	// } else {
	//     if ($GLOBALS['wp_roles']->is_role($role)) {
	//      // Silence is gold
	//     } else {
	//    $error->add(405, __("Role field 'role' is not a valid. Check your User Roles from Dashboard.", 'wp_rest_user'), array('status' => 400));
	//    return $error;
	//     }
	// }
	$user_id = username_exists($username);
	if (!$user_id && email_exists($email) == false) {
		$user_id = wp_create_user($username, $password, $email);
		if (!is_wp_error($user_id)) {
			// Ger User Meta Data (Sensitive, Password included. DO NOT pass to front end.)
			$user = get_user_by('id', $user_id);
			// $user->set_role($role);
			$user->set_role('subscriber');
			// WooCommerce specific code
			if (class_exists('WooCommerce')) {
				$user->set_role('customer');
			}
			// Ger User Data (Non-Sensitive, Pass to front end.)
			$response['code'] = 200;
			$response['message'] = __("User '" . $username . "' Registration was Successful", "wp-rest-user");
		} else {
			return $user_id;
		}
	} else {
		$error->add(406, __("Email already exists, please try 'Reset Password'", 'wp-rest-user'), array('status' => 400));
		return $error;
	}
	return new WP_REST_Response($response, 123);
}