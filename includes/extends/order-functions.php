<?php

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'slug_register_order_fields' );
function slug_register_order_fields() {

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


////////////////////////////////////////////

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