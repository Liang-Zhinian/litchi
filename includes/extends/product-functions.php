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


add_filter( 'woocommerce_rest_prepare_product_object', 'prepeare_product_response', 11, 3 );
add_filter( 'woocommerce_api_product_response', 'filter_woocommerce_api_product_response', 10, 4 );

/**
 * Legacy: Prepare object for product response
 *
 * @since 1.2.0
 */
function filter_woocommerce_api_product_response( $product_data, $product, $fields, $this_server ) { 
	$author_id = get_post_field( 'post_author', $product_data['id'] );
	$which_marketplace = which_marketplace();

	if ($which_marketplace == 'wcfmmarketplace') {

		global $WCFM, $WCFMmp;

		// $product_data['vendor_id'] = get_post_field( 'post_author', $product->id);
		// $product_data['vendor_name'] = get_the_author_meta( 'display_name', $product_data['vendor_id']);

		//$author_id = get_post_field( 'post_author', $product_data['id'] );

		$vendor = new Litchi_Vendor_Manager();
		$store = $vendor->get( $author_id );
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

	} else if ($which_marketplace == 'dokan') {
		$store               = dokan()->vendor->get( $author_id );
		$store_logo = $store->get_avatar();
		$store = array(
			'id'        => $store->get_id(),
			'name'      => $store->get_name(),
			'shop_name' => $store->get_shop_name(),
			'url'       => $store->get_shop_url(),
			'address'   => $store->get_address(),
			'logo'      => $store_logo,
			'social' => $store->get_social_profiles(),
			'phone' => $store->get_phone(),
			'email' => $store->get_email(),
			'im' => get_im_profiles($store)
		);

		$product_data['store'] = $store;
	}


	$post_id = $product_data['id'];
	$taq_review = array();
	$taq_review_title = get_post_meta( $post_id, 'taq_review_title' );
	if ($taq_review_title) {
		$taq_review['taq_review_title'] =  $taq_review_title;
	}
	$taq_review_criteria = get_post_meta( $post_id, 'taq_review_criteria' );
	if ($taq_review_criteria) {
		$taq_review['taq_review_criteria'] =  $taq_review_criteria;
	}
	$taq_review_score = get_post_meta( $post_id, 'taq_review_score' );
	if ($taq_review_score) {
		$score = (int)$taq_review_score[0];
		$taq_review['taq_review_score'] =  $score;
	}
	$product_data['taq_review'] =  $taq_review;

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
	$data = $response->get_data();
	$author_id = get_post_field( 'post_author', $data['id'] );
	$which_marketplace = which_marketplace();

	if ($which_marketplace == 'wcfmmarketplace') {
		global $WCFM, $WCFMmp;


		$vendor = new Litchi_Vendor_Manager();
		$store = $vendor->get( $author_id );

		$store_logo = $WCFM->wcfm_vendor_support->wcfm_get_vendor_logo_by_vendor( $author_id );

		$data['store'] = array(
			'id'        => $store->get_id(),
			'name'      => $store->get_name(),
			'shop_name' => $store->get_shop_name(),
			'url'       => $store->get_shop_url(),
			'address'   => $store->get_address(),
			'logo'      => $store_logo,
			'social' => $store->get_social_profiles()
		);

	} else if ($which_marketplace == 'dokan') {
		$store_user               = dokan()->vendor->get( $author_id );
		$store_logo = $store_user->get_avatar();
		$store = array();

		$store['logo'] = $store_logo;
		$store['social'] = $store_user->get_social_profiles();
		$store['phone'] = $store_user->get_phone();
		$store['email'] = $store_user->get_email();
		$store['im'] = get_im_profiles($store_user);
		foreach( $data['store'] as $key => $value ) {
			$store[$key] = $value;
		}
		$data['store'] = $store;
	}

	$post_id = $data['id'];
	$taq_review = array();
	$taq_review_title = get_post_meta( $post_id, 'taq_review_title' );
	if ($taq_review_title) {
		$taq_review['taq_review_title'] =  $taq_review_title;
	}
	$taq_review_criteria = get_post_meta( $post_id, 'taq_review_criteria' );
	if ($taq_review_criteria) {
		$taq_review['taq_review_criteria'] =  $taq_review_criteria;
	}
	$taq_review_score = get_post_meta( $post_id, 'taq_review_score' );
	if ($taq_review_score) {
		$score = (int)$taq_review_score[0];
		$taq_review['taq_review_score'] =  $score;
	}
	$data['taq_review'] =  $taq_review;

	$response->set_data( $data);

	$response->set_data( $data);
	return $response;
}

function get_im_profiles($store) {
	return array(
		'qq' => $store->get_info_part( 'qq' ) ?? '',
	);
}

//Adding Alphabetical sorting option to shop and product settings pages
function alphabetical_shop_ordering( $sort_args ) {
	$orderby_value = isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
	if ( 'taq_review_score' == $orderby_value ) {
		$sort_args['orderby'] = 'meta_value_num';
		// 		$sort_args['order'] = 'desc';
		$sort_args['meta_key'] = 'taq_review_score';
	}
	return $sort_args;
}
add_filter( 'woocommerce_get_catalog_ordering_args', 'alphabetical_shop_ordering' );

function custom_wc_catalog_orderby( $sortby ) {
	$sortby['taq_review_score'] = 'Sort by Name: Alphabetical';
	$sortby['taq_review_score'] = 'Sort by Name: Alphabetical';
	return $sortby;
}
// add_filter( 'woocommerce_default_catalog_orderby_options', 'custom_wc_catalog_orderby' );
// add_filter( 'woocommerce_catalog_orderby', 'custom_wc_catalog_orderby' );

// add new orderby options to product rest api
add_filter('rest_product_collection_params', 'rest_product_collection_params');
function rest_product_collection_params($params){
	$params['orderby']['enum'] = array_merge( $params['orderby']['enum'], array( 'taq_review_score' ) );

	return $params;
}


/**
 * add Product Creation via REST API to WordPress Website
 */
add_action('rest_api_init', 'wp_rest_product_endpoints');
/**
 * Create a new product
 *
 * @param  WP_REST_Request $request Full details about the request.
 * @return array $args.
 **/
function wp_rest_product_endpoints($request) {
  /**
   * Handle Add Product via camera request.
   */
  register_rest_route('wc/v3', 'products/add', array(
    'methods' => 'POST',
    'callback' => 'wc_rest_product_endpoint_handler',
  ));
}
function wc_rest_product_endpoint_handler($request = null) {
    $response = array();
    $parameters = $request->get_json_params();
    $username = sanitize_text_field($_POST['username']);
    $password = sanitize_text_field($_POST['password']);
    $error = new WP_Error();
    if (empty($username)) {
        $error->add(400, __("Username field 'username' is required.", 'wp-rest-user'), array('status' => 400));
        return $error;
    }
    if (empty($password)) {
        $error->add(404, __("Password field 'password' is required.", 'wp-rest-user'), array('status' => 400));
        return $error;
    }
    $user = wp_authenticate($username, $password);
        if (is_wp_error($user)) {
            return rest_ensure_response($user);
        }
    $post_id = wp_insert_post(array(
        'post_title' => 'Test Product',
        'post_type' => 'product'
    ));
    // set product is simple/variable/grouped
    wp_set_object_terms( $post_id, 'simple', 'product_type' );
    update_post_meta( $post_id, '_visibility', 'visible' );
    update_post_meta( $post_id, '_stock_status', 'instock');
    update_post_meta( $post_id, 'total_sales', '0' );
    update_post_meta( $post_id, '_downloadable', 'no' );
    update_post_meta( $post_id, '_virtual', 'yes' );
    update_post_meta( $post_id, '_regular_price', '' );
    update_post_meta( $post_id, '_sale_price', '' );
    update_post_meta( $post_id, '_purchase_note', '' );
    update_post_meta( $post_id, '_featured', 'no' );
    update_post_meta( $post_id, '_weight', '11' );
    update_post_meta( $post_id, '_length', '11' );
    update_post_meta( $post_id, '_width', '11' );
    update_post_meta( $post_id, '_height', '11' );
    update_post_meta( $post_id, '_sku', 'SKU11' );
    update_post_meta( $post_id, '_product_attributes', array() );
    update_post_meta( $post_id, '_sale_price_dates_from', '' );
    update_post_meta( $post_id, '_sale_price_dates_to', '' );
    update_post_meta( $post_id, '_price', '0' );
    update_post_meta( $post_id, '_sold_individually', '' );
    update_post_meta( $post_id, '_manage_stock', 'yes' );
    // wc_update_product_stock($post_id, $single['qty'], 'set');
    update_post_meta( $post_id, '_backorders', 'no' );
    // update_post_meta( $post_id, '_stock', $single['qty'] );
    // check if user has uploaded any files
    if ( ! empty( $_FILES['file'] ) ) {
        // upload attachment and get attachment_id
        $attachment_id = wc_product_upload_attachment( $_FILES['file'] );
        if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
            return wp_send_json_error( array(
                'message' => __( 'Invalid request. File is not updated.', 'litchi' )
            ) );
        }
		
        //set product feature image
        //attach_product_thumbnail($post_id, $_FILES['files'], 0);
        $response['attachment_id'] = $attachment_id;
        // And finally assign featured image to post
        set_post_thumbnail( $post_id, $attachment_id );
    }
    $response['post_id'] = $post_id;
    return new WP_REST_Response($response, 200);
}

/**
 * Upload file object.
 *
 * @since 1.8.0
 * @param array $file_obj
 * @return int|object
 */
function wc_product_upload_attachment( $file_obj ) {
	// these files need to be included as dependencies when on the front end
	require_once( ABSPATH . 'wp-admin/includes/image.php' );
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	require_once( ABSPATH . 'wp-admin/includes/media.php' );
	return media_handle_sideload( $file_obj, 0 );
}