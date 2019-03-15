<?php

defined( 'ABSPATH' ) || exit;


add_filter( 'pre_get_posts', 'my_modify_main_query' );
function my_modify_main_query( $query ) {
    

    $meta_query_args = $query->get('meta_query'); //array();
    if ( empty( $meta_query_args ) ) {
        $meta_query_args = array();
    }

    $request = $_GET['filter'];
    // my_log_file($request, 'my_modify_main_query: $request');

    // Price filter.

    if ( ! empty( $request['price'] ) ) {
        $meta_query_args = add_meta_query( $meta_query_args, array(
            'key' => '_price',
            'value' => esc_attr( $request['price'] ),
            'compare' => '=',
        ) );  // WPCS: slow query ok.
    }

    $price_decimals = wc_get_price_decimals();
    // my_log_file($price_decimals, 'my_modify_main_query: $price_decimals');


    if ( ! empty( $request['min_price'] ) || ! empty( $request['max_price'] ) ) {
        // $price_meta_query = array(
        //     'key'     => '_price',
        //     'value'   => array( $request['min_price'], $request['max_price'] ),
        //     'compare' => 'BETWEEN',
        //     'type'    => 'DECIMAL(10,' . wc_get_price_decimals() . ')',
        // );
        $meta_query_args['meta_query'] = add_meta_query( $meta_query_args, wc_get_min_max_price_meta_query( $request ) );  // WPCS: slow query ok.
        // my_log_file($price_meta_query, 'my_modify_main_query: $price_meta_query');
        $meta_query_args['meta_query'] = add_meta_query( $meta_query_args, $price_meta_query );  // WPCS: slow query ok.
    }
    my_log_file($meta_query_args, 'my_modify_main_query: $meta_query_args _price');

    // Average rating filter
    if ( ! empty( $request['min_average_rating'] ) || ! empty( $request['max_average_rating'] ) ) {
        $average_rating_meta_query = array(
            'key' => '_wc_average_rating',
            'value'   => array( $request['min_average_rating'], $request['max_average_rating'] ),
            'compare' => 'BETWEEN',
            'type'    => 'DECIMAL(2,2)',
        );  // WPCS: slow query ok.
        // my_log_file($average_rating_meta_query, 'my_modify_main_query: $average_rating_meta_query');
        $meta_query_args['meta_query'] = add_meta_query( $meta_query_args, $average_rating_meta_query );  // WPCS: slow query ok.
    }
    
    
    my_log_file($meta_query_args, 'my_modify_main_query: $meta_query_args _final');
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