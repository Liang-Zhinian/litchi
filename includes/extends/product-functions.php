<?php

defined( 'ABSPATH' ) || exit;


/* 
    add price range, average rating range to meta_query for legacy rest api:
        /wc-api/v3/products
*/
add_filter( 'pre_get_posts', 'my_modify_main_query' );
function my_modify_main_query( $query ) {
    

    $meta_query_args = $query->query;
    if ( empty( $meta_query_args ) ) {
        $meta_query_args = array();
    }

    $request = $_GET['filter'];

    // Price filter.

    if ( ! empty( $request['price'] ) ) {
        $meta_query_args = add_meta_query( $meta_query_args, array(
            'key' => '_price',
            'value' => esc_attr( $request['price'] ),
            'compare' => '=',
        ) );  // WPCS: slow query ok.
    }

    $price_decimals = wc_get_price_decimals();


    if ( ! empty( $request['min_price'] ) || ! empty( $request['max_price'] ) ) {
        $meta_query_args['meta_query'] = add_meta_query( $meta_query_args, wc_get_min_max_price_meta_query( $request ) );  // WPCS: slow query ok.
    }

    // Average rating filter
    if ( ! empty( $request['min_average_rating'] ) || ! empty( $request['max_average_rating'] ) ) {
        $average_rating_meta_query = array(
            'key' => '_wc_average_rating',
            'value'   => array( $request['min_average_rating'], $request['max_average_rating'] ),
            'compare' => 'BETWEEN',
            'type'    => 'DECIMAL(2,2)',
        );  // WPCS: slow query ok.
        $meta_query_args['meta_query'] = add_meta_query( $meta_query_args, $average_rating_meta_query );  // WPCS: slow query ok.
    }
    
    
    $query->set('meta_query', $meta_query_args['meta_query']);

    return $query; ## <==== This was missing
}

function add_meta_query( $args, $meta_query ) {
    if ( empty( $args['meta_query'] ) ) {
        $args['meta_query'] = array();
    }

    $args['meta_query'][] = $meta_query;

    return $args['meta_query'];
}

//////////////////////////////////////


add_filter( 'woocommerce_rest_prepare_product_object', 'prepeare_product_response', 10, 3 );
add_filter( 'woocommerce_api_product_response', 'filter_woocommerce_api_product_response', 10, 4 );

/**
 * Legacy: Prepare object for product response
 *
 * @since 1.2.0
 */
function filter_woocommerce_api_product_response( $product_data, $product, $fields, $this_server ) { 
    
    global $WCFM, $WCFMmp;

    // $product_data['vendor_id'] = get_post_field( 'post_author', $product->id);
    // $product_data['vendor_name'] = get_the_author_meta( 'display_name', $product_data['vendor_id']);

    $author_id = get_post_field( 'post_author', $product_data['id'] );

    $store = litchi()->vendor->get( $author_id );
    // $the_user = get_user_by( 'id', $author_id );;

    
    $store_logo = $WCFM->wcfm_vendor_support->wcfm_get_vendor_logo_by_vendor( $author_id );
    
    $product_data['store'] = array(
        'id'        => $store->get_id(),
        'name'      => $store->get_name(),
        'shop_name' => $store->get_shop_name(),
        'url'       => $store->get_shop_url(),
        'address'   => $store->get_address(),
        'logo'      => $store_logo
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
function prepeare_product_response( $response, $object, $request ) {
    global $WCFM, $WCFMmp;
    $data = $response->get_data();
    $author_id = get_post_field( 'post_author', $data['id'] );

    $store = litchi()->vendor->get( $author_id );
    // $the_user = get_user_by( 'id', $author_id );;
    
    $store_logo = $WCFM->wcfm_vendor_support->wcfm_get_vendor_logo_by_vendor( $author_id );

    $data['store'] = array(
        'id'        => $store->get_id(),
        'name'      => $store->get_name(),
        'shop_name' => $store->get_shop_name(),
        'url'       => $store->get_shop_url(),
        'address'   => $store->get_address(),
        'logo'      => $store_logo
    );

    $response->set_data( $data );
    return $response;
}