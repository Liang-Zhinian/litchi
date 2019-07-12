<?php

defined( 'ABSPATH' ) || exit;

/////////////////////////////////
////////////////////////////// shipping /////////////////////////////////////////////
/* add phone attribute to shipping meta data */
add_action( 'rest_api_init', 'slug_register_order_fields' );
function slug_register_order_fields() {

        register_rest_field( 'shop_order', 'shipping',
            array(
                'get_callback'    => 'get_shippingMeta',
                'update_callback' => 'update_shippingMeta',
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

        register_rest_field( 'shop_order', 'customer',
            array(
                'get_callback'    => 'get_customerMetaX',
                'schema'          => array(
                    'customer' => array (
                        'id'                 => array(
                            'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
                            'type'        => 'integer',
                            'context'     => array( 'view', 'edit' ),
                            'readonly'    => true,
                        ),
                        'email'              => array(
                            'description' => __( 'The email address for the customer.', 'woocommerce' ),
                            'type'        => 'string',
                            'format'      => 'email',
                            'context'     => array( 'view', 'edit' ),
                        ),
                        'first_name'         => array(
                            'description' => __( 'Customer first name.', 'woocommerce' ),
                            'type'        => 'string',
                            'context'     => array( 'view', 'edit' ),
                            'arg_options' => array(
                                'sanitize_callback' => 'sanitize_text_field',
                            ),
                        ),
                        'last_name'          => array(
                            'description' => __( 'Customer last name.', 'woocommerce' ),
                            'type'        => 'string',
                            'context'     => array( 'view', 'edit' ),
                            'arg_options' => array(
                                'sanitize_callback' => 'sanitize_text_field',
                            ),
                        ),
                        'username'           => array(
                            'description' => __( 'Customer login name.', 'woocommerce' ),
                            'type'        => 'string',
                            'context'     => array( 'view', 'edit' ),
                            'arg_options' => array(
                                'sanitize_callback' => 'sanitize_user',
                            ),
                        ),
                        'avatar_url'         => array(
                            'description' => __( 'Avatar URL.', 'woocommerce' ),
                            'type'        => 'string',
                            'context'     => array( 'view', 'edit' ),
                            'readonly'    => true,
                        ),
                        'meta_data'          => array(
                            'description' => __( 'Meta data.', 'woocommerce' ),
                            'type'        => 'array',
                            'context'     => array( 'view', 'edit' ),
                            'items'       => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'id'    => array(
                                        'description' => __( 'Meta ID.', 'woocommerce' ),
                                        'type'        => 'integer',
                                        'context'     => array( 'view', 'edit' ),
                                        'readonly'    => true,
                                    ),
                                    'key'   => array(
                                        'description' => __( 'Meta key.', 'woocommerce' ),
                                        'type'        => 'string',
                                        'context'     => array( 'view', 'edit' ),
                                    ),
                                    'value' => array(
                                        'description' => __( 'Meta value.', 'woocommerce' ),
                                        'type'        => 'mixed',
                                        'context'     => array( 'view', 'edit' ),
                                    ),
                                ),
                            ),
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

    if( empty( $response->data ) )
        return $response;

    $response->data['shipping']['phone'] = get_post_meta( $post->ID, '_shipping_phone', true);
    
    $customer    = new WC_Customer( $response->data['customer_id'] );
    $_data       = $customer->get_data();
    //$response->data['customer'] = $_data;


    return $response;

}

function get_customerMetaX($data,$field_name,$request) {

    $customer    = new WC_Customer( $data['customer_id'] );
    $_data       = $customer->get_data();
    $data['customer'] = $_data;

    return $data['customer'];
};

function get_shippingMeta($data,$field_name,$request) {

    $shipping = $data['shipping'];
    $shipping['phone'] = get_post_meta( $data[ 'id' ], '_'.$field_name."_phone", true );

    return $shipping;
};
function update_shippingMeta($value,$data,$field_name) {

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

/* end add phone attribute to shipping meta data */


/////////////////////////////////
////////////////////////////// order status /////////////////////////////////////////////
/* add custom order status */

// Register new status
function register_additional_order_status() {
    register_post_status( 'wc-paid', array(
        'label'                     => __('Paid', 'litchi'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => __( 'Paid (%s)', 'litchi' )
    ) );

    register_post_status( 'wc-awaiting-shipment', array(
        'label'                     => 'Awaiting shipment',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => __( 'Awaiting shipment (%s)', 'litchi' )
    ) );

    register_post_status( 'wc-shipped', array(
        'label'                     => __('Shipped', 'litchi'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => __( 'Shipped (%s)', 'litchi' )
    ) );

    register_post_status( 'wc-arrival-shipment', array(
        'label'                     => __( 'Shipment Arrival'),
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => __( 'Shipment Arrival <span class="count">(%s)</span>', 'litchi' )
    ) );
}
add_action( 'init', 'register_additional_order_status' );

// Add to list of WC Order statuses
function add_additional_order_statuses( $order_statuses ) {
 
    $new_order_statuses = array();
 
    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {
 
        $new_order_statuses[ $key ] = $status;
 
        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-paid'] = __('Paid', 'litchi');
            $new_order_statuses['wc-awaiting-shipment'] = __('Awaiting shipment', 'litchi');
            $new_order_statuses['wc-shipped'] = __('Shipped', 'litchi');
            $new_order_statuses['wc-arrival-shipment'] = __('Shipment Arrival', 'litchi');
        }
 
        // if ( 'wc-awaiting-shipment' === $key ) {
        //     $new_order_statuses['wc-shipped'] = 'Shipped';
        // }
    }
 
    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_additional_order_statuses' );

function sv_add_my_account_order_actions( $actions, $order ) {

    $actions['help'] = array(
        // adjust URL as needed
        'url'  => '/contact/?&order=' . $order->get_order_number(),
        'name' => __( 'Get Help', 'my-textdomain' ),
    );

    return $actions;
}
add_filter( 'woocommerce_my_account_my_orders_actions', 'sv_add_my_account_order_actions', 10, 2 );


///////////////////////////////////////////

/* Add Custom Meta to the Shop Order API Response */
add_filter( 'wcfmapi_rest_prepare_shop_order_object',  'my_wcfmapi_rest_prepare_shop_order_object', 10, 3 );
/**
 * Add extra fields in orders response.
 *
 * @param WP_REST_Response $response The response object.
 * @param WP_Post          $post     Order object used to create response.
 *
 * @return WP_REST_Response
 */
function my_wcfmapi_rest_prepare_shop_order_object( $response, $post, $request ) {
     if( empty( $response->data ) )
        return $response;

    $data=[];

    foreach ( $response->get_data() as $order ) {
        $customer    = new WC_Customer( $order['customer_id'] );
        $_data       = $customer->get_data();
        $order['customer'] = $_data;

        $order['shipping']['phone'] = get_post_meta( $order[ 'id' ], '_shipping_phone', true );

        $data[] = $order;
    }
    $response->set_data($data);

    
    return $response;

}


///////////////////////////////////////////

/* Add Custom Meta to the Shop Order API Response */
add_filter( 'wcfmapi_rest_prepare_shop_order_objects',  'my_wcfmapi_rest_prepare_shop_order_objects', 10, 3 );
/**
 * Add extra fields in orders response.
 *
 * @param WP_REST_Response $response The response object.
 * @param WP_Post          $post     Order object used to create response.
 *
 * @return WP_REST_Response
 */
function my_wcfmapi_rest_prepare_shop_order_objects( $response, $post, $request ) {
    global $WCFM, $WCFMmp, $wp, $theorder, $wpdb;
    if( empty( $response->data ) )
       return $response;

   $data=[];
   
   $admin_fee_mode = apply_filters( 'wcfm_is_admin_fee_mode', false );

   foreach ( $response->get_data() as $order ) {
        $customer    = new WC_Customer( $order['customer_id'] );
        $_data       = $customer->get_data();
        $order['customer'] = $_data;
        $order['shipping']['phone'] = get_post_meta( $order[ 'id' ], '_shipping_phone', true );
       
        $theorder = wc_get_order( $order['id'] );

        $commission = $WCFM->wcfm_vendor_support->wcfm_get_commission_by_order( $order['id'] );
        if( $commission ) {
            $gross_sales  = (float) $theorder->get_total();
            $total_refund = (float) $theorder->get_total_refunded();
            if( $admin_fee_mode ) {
                $commission = $gross_sales - $total_refund - $commission;
            }
            //$commission =  wc_price( $commission, array( 'currency' => $theorder->get_currency() ) );
        } else {
            $commission =  'N/A'; //__( 'N/A', 'wc-frontend-manager' );
        }
                                    
        $order['commission']=$commission;
        $data[] = $order;
    }
    $response->set_data($data);

   
    return $response;
}
