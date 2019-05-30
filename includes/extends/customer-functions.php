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

/* add shopping cart attribute to additional_shipping meta data */
add_filter( 'woocommerce_rest_prepare_customer',  'prepare_customers_response' );
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

    $cart = get_user_meta( $data[ 'id' ], '_woocommerce_persistent_cart_' . get_current_blog_id(), true );

    $response->data['cart']       =  $cart;

    return $response;
}
/* end add shopping cart attribute to additional_shipping meta data */