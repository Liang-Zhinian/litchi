<?php


require_once dirname( __FILE__ ) . '/extends/customer-functions.php';
require_once dirname( __FILE__ ) . '/extends/order-functions.php';
require_once dirname( __FILE__ ) . '/extends/product-functions.php';
require_once dirname( __FILE__ ) . '/extends/comment-functions.php';
require_once dirname( __FILE__ ) . '/extends/cart-functions.php';
require_once dirname( __FILE__ ) . '/extends/jwt-functions.php';
require_once dirname( __FILE__ ) . '/extends/dokan-functions.php';


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
