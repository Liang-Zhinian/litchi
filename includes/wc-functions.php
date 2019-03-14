<?php


/* Add additional shipping fields (email, phone) in FRONT END (i.e. My Account and Order Checkout) */
/* Note:  $fields keys (i.e. field names) must be in format: "shipping_" */
add_filter( 'woocommerce_shipping_fields' , 'my_additional_shipping_fields' );
function my_additional_shipping_fields( $fields ) {
    $fields['shipping_email'] = array(
        'label'         => __( 'Ship Email', 'woocommerce' ),
        'required'      => false,
        'class'         => array( 'form-row-first' ),
        'validate'      => array( 'email' ),
    );
    $fields['shipping_phone'] = array(
        'label'         => __( 'Ship Phone', 'woocommerce' ),
        'required'      => true,
        'class'         => array( 'form-row-last' ),
        'clear'         => true,
        'validate'      => array( 'phone' ),
    );
    return $fields;
}
/* Display additional shipping fields (email, phone) in ADMIN area (i.e. Order display ) */
/* Note:  $fields keys (i.e. field names) must be in format:  WITHOUT the "shipping_" prefix (it's added by the code) */
add_filter( 'woocommerce_admin_shipping_fields' , 'my_additional_admin_shipping_fields' );
function my_additional_admin_shipping_fields( $fields ) {
        $fields['email'] = array(
            'label' => __( 'Order Ship Email', 'woocommerce' ),
        );
        $fields['phone'] = array(
            'label' => __( 'Order Ship Phone', 'woocommerce' ),
        );
        return $fields;
}
/* Display additional shipping fields (email, phone) in USER area (i.e. Admin User/Customer display ) */
/* Note:  $fields keys (i.e. field names) must be in format: shipping_ */
add_filter( 'woocommerce_customer_meta_fields' , 'my_additional_customer_meta_fields' );
function my_additional_customer_meta_fields( $fields ) {
        $fields['shipping']['fields']['shipping_phone'] = array(
            'label' => __( 'Telephone', 'woocommerce' ),
            'description' => '',
        );
        $fields['shipping']['fields']['shipping_email'] = array(
            'label' => __( 'Email', 'woocommerce' ),
            'description' => '',
        );
        return $fields;
}
/* Add CSS for ADMIN area so that the additional shipping fields (email, phone) display on left and right side of edit shipping details */
add_action('admin_head', 'my_custom_admin_css');
function my_custom_admin_css() {
  echo '<style>
    #order_data .order_data_column ._shipping_email_field {
        clear: left;
        float: left;
    }
    #order_data .order_data_column ._shipping_phone_field {
        float: right;
    }
  </style>';
}


// add_filter( 'woocommerce_rest_prepare_customer',  'prepare_customers_response' );
/**
 * Add extra fields in customers response.
 *
 * @param WP_REST_Response $response The response object.
 * @param WP_User          $user     User object used to create response.
 *
 * @return WP_REST_Response
 */
function prepare_customers_response( $response/*, $user, $request*/ ) {
    $data = $response->get_data();

    $customer = new WC_Customer( $data['id'] );
    // WooCommerce 3.0 or later.
    if ( method_exists( $customer, 'get_meta' ) ) {
        // Shipping fields.
        $response->data['shipping']['phone']       = $customer->get_meta( 'shipping_phone' );
    } else {
        // Shipping fields.
        $response->data['shipping']['phone']       = $customer->shipping_phone;
    }

    // $response->set_data( $data );
    return $response;
}


// add_filter( 'woocommerce_rest_customer_schema', 'addresses_schema' );

/**
 * Addresses schena.
 *
 * @param  array $properties Default schema properties.
 *
 * @return array
 */
function addresses_schema( $properties ) {
    $properties['shipping']['properties']['first_name'] = array(
    	'description' => __( 'first_name.', '' ),
    	'type'        => 'string',
    	'context'     => array( 'view', 'edit' ),
    );
    $properties['shipping']['properties']['last_name'] = array(
    	'description' => __( 'last_name.', '' ),
    	'type'        => 'string',
    	'context'     => array( 'view', 'edit' ),
    );
    $properties['shipping']['properties']['address_1'] = array(
    	'description' => __( 'address_1.', '' ),
    	'type'        => 'string',
    	'context'     => array( 'view', 'edit' ),
    );
    $properties['shipping']['properties']['address_2'] = array(
    	'description' => __( 'address_2.', '' ),
    	'type'        => 'string',
    	'context'     => array( 'view', 'edit' ),
    );
    $properties['shipping']['properties']['country'] = array(
    	'description' => __( 'country.', '' ),
    	'type'        => 'string',
    	'context'     => array( 'view', 'edit' ),
    );
    $properties['shipping']['properties']['city'] = array(
    	'description' => __( 'city.', '' ),
    	'type'        => 'string',
    	'context'     => array( 'view', 'edit' ),
    );
    $properties['shipping']['properties']['state'] = array(
    	'description' => __( 'state.', '' ),
    	'type'        => 'string',
    	'context'     => array( 'view', 'edit' ),
    );
    $properties['shipping']['properties']['postcode'] = array(
    	'description' => __( 'postcode.', '' ),
    	'type'        => 'string',
    	'context'     => array( 'view', 'edit' ),
    );
    $properties['shipping']['properties']['phone'] = array(
    	'description' => __( 'phone.', '' ),
    	'type'        => 'string',
    	'context'     => array( 'view', 'edit' ),
    );
    return $properties;
}

/////////////////////////

add_action( 'rest_api_init', 'slug_register_myfields' );
function slug_register_myfields() {

        register_rest_field( 'customer', 'shipping',
            array(
                'get_callback'    => 'get_customerMeta',
                'update_callback' => 'update_customerMeta',
                'schema'          => array(
                    'shipping' => array(
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
                ),
            )
        );

        register_rest_field( 'shop_order', 'shipping',
            array(
                'get_callback'    => 'get_orderMeta',
                'update_callback' => 'update_orderMeta',
                'schema'          => array(
                    'shipping' => array(
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
                ),
            )
        );
}

function get_customerMeta($data,$field_name,$request) {
    my_log_file($data);

    $shipping = $data['shipping'];
    $shipping['phone'] = get_user_meta( $data[ 'id' ], $field_name."_phone", true );
    // $shipping = get_user_meta( $data[ 'id' ], $field_name, true );
    

    return $shipping;
};
function update_customerMeta($value,$data,$field_name) {
    //log_me(array('This is a message' => 'for debugging purposes'));
    // my_log_file($value['phone']);
    //my_log_file($data);
    //my_log_file($field_name);
    // if ( ! $value['phone'] || ! is_string( $value['phone'] ) ) return;
    update_user_meta( $data->ID, $field_name.'_first_name', sanitize_text_field( $value['first_name'] ) );
    update_user_meta( $data->ID, $field_name.'_last_name', sanitize_text_field( $value['last_name'] ) );
    update_user_meta( $data->ID, $field_name.'_company', sanitize_text_field( $value['company'] ) );    
    update_user_meta( $data->ID, $field_name.'_address_1', sanitize_text_field( $value['address_1'] ) );
    update_user_meta( $data->ID, $field_name.'_address_2', sanitize_text_field( $value['address_2'] ) );
    update_user_meta( $data->ID, $field_name.'_city', sanitize_text_field( $value['city'] ) );
    update_user_meta( $data->ID, $field_name.'_state', sanitize_text_field( $value['state'] ) );
    update_user_meta( $data->ID, $field_name.'_postcode', sanitize_text_field( $value['postcode'] ) );
    update_user_meta( $data->ID, $field_name.'_country', sanitize_text_field( $value['country'] ) );
    update_user_meta( $data->ID, $field_name.'_phone', sanitize_text_field( $value['phone'] ) );

    // $shipping = get_user_meta( $data->ID, $field_name, true );
    // $addresses = $shipping['addresses'];
    // $size = sizeof($addresses);
    // for ($i = 0; $i <= $size; $i++) {
    //     $current_address = $addresses[$i];
        
    //     foreach ( $value['addresses'] as $item => $new_address ) {

    //         if ( $current_address['id'] == $new_address['id'] ) {
    //             $addresses[$i] = $new_address;
    //         }
    //     }
    // }

    // $shipping['addresses'] = $addresses;
    // $shipping['default_id'] = $value['default_id'];
    
    //my_log_file($shipping);
    
    // update_user_meta( $data->ID, $field_name, $value );

};


/* Add Custom Meta to the Shop Order API Response */
add_filter( 'woocommerce_rest_prepare_shop_order',  'prepare_shop_orders_response', 10, 3 );
/**
 * Add extra fields in orders response.
 *
 * @param WP_REST_Response $response The response object.
 * @param WP_Post          $post     Order object used to create response.
 *
 * @return WP_REST_Response
 */
function prepare_shop_orders_response( $response, $post, $request ) {
        
    my_log_file($response->data);

    if( empty( $response->data ) )
        return $response;

    $response->data['shipping']['phone'] = get_post_meta( $post->ID, '_shipping_phone', true);

    return $response;

}

        

function get_orderMeta($data,$field_name,$request) {
    my_log_file($data);

    $shipping = $data['shipping'];
    $shipping['phone'] = get_post_meta( $data[ 'id' ], '_'.$field_name."_phone", true );
    

    return $shipping;
};
function update_orderMeta($value,$data,$field_name) {
    //log_me(array('This is a message' => 'for debugging purposes'));
    // my_log_file($value['phone']);
    //my_log_file($data);
    //my_log_file($field_name);
    // if ( ! $value['phone'] || ! is_string( $value['phone'] ) ) return;
    // update_post_meta( $data->ID, $field_name.'_phone', sanitize_text_field( $value['phone'] ) );

    update_post_meta( $data->ID, $field_name.'_first_name', sanitize_text_field( $value['first_name'] ) );
    update_post_meta( $data->ID, $field_name.'_last_name', sanitize_text_field( $value['last_name'] ) );
    update_post_meta( $data->ID, $field_name.'_company', sanitize_text_field( $value['company'] ) );    
    update_post_meta( $data->ID, $field_name.'_address_1', sanitize_text_field( $value['address_1'] ) );
    update_post_meta( $data->ID, $field_name.'_address_2', sanitize_text_field( $value['address_2'] ) );
    update_post_meta( $data->ID, $field_name.'_city', sanitize_text_field( $value['city'] ) );
    update_post_meta( $data->ID, $field_name.'_state', sanitize_text_field( $value['state'] ) );
    update_post_meta( $data->ID, $field_name.'_postcode', sanitize_text_field( $value['postcode'] ) );
    update_post_meta( $data->ID, $field_name.'_country', sanitize_text_field( $value['country'] ) );
    update_post_meta( $data->ID, $field_name.'_phone', sanitize_text_field( $value['phone'] ) );

};


// add_filter( 'woocommerce_attribute_taxonomies', 'my_woocommerce_attribute_taxonomies' );
function my_woocommerce_attribute_taxonomies($taxonomies){
    my_log_file($taxonomies);
    // my_log_file($arg2);

    // $price = array(
    //     'attribute_name' => 'price'
    // );
    // $price['attribute_name'] = 'price';
    // $taxonomies['price'] = $price;
    // my_log_file($taxonomies);

    return $taxonomies;
}

add_filter( 'pre_get_posts', 'my_modify_main_query' );
function my_modify_main_query( $query ) {
    

    $meta_query_args = $query->get('meta_query'); //array();
    if ( empty( $meta_query_args ) ) {
        $meta_query_args = array();
    }

    $request = $_GET['filter'];
    my_log_file($request, 'my_modify_main_query: $request');

    // Price filter.

    if ( ! empty( $request['price'] ) ) {
        $meta_query_args = add_meta_query( $meta_query_args, array(
            'key' => '_price',
            'value' => esc_attr( $request['price'] ),
            'compare' => '=',
        ) );  // WPCS: slow query ok.
    }

    if ( ! empty( $request['min_price'] ) || ! empty( $request['max_price'] ) ) {
        $price_meta_query = array(
            'key'     => '_price',
            'value'   => array( $request['min_price'], $request['max_price'] ),
            'compare' => 'BETWEEN',
            'type'    => 'DECIMAL(10,' . wc_get_price_decimals() . ')',
        );
        my_log_file($price_meta_query, 'my_modify_main_query: $price_meta_query');
        $meta_query_args = add_meta_query( $meta_query_args, $price_meta_query );  // WPCS: slow query ok.
    }
    
    $query->set('meta_query', $meta_query_args);
    my_log_file($query, 'my_modify_main_query: $query _after');

    return $query; ## <==== This was missing
}

function add_meta_query( $args, $meta_query ) {
    if ( empty( $args['meta_query'] ) ) {
        $args['meta_query'] = array();
    }

    $args['meta_query'][] = $meta_query;

    return $args['meta_query'];
}