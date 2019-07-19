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

    $vendor = new Litchi_Vendor_Manager();
    $store = $vendor->get( $author_id );
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

  register_rest_route('wc/v3', 'media/add', array(
    'methods' => 'POST',
    'callback' => 'wc_rest_media_endpoint_handler',
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


function wc_rest_media_endpoint_handler($request = null) {

    $response = new WP_REST_Response();

    $parameters = $request->get_json_params();
    $username = sanitize_text_field($_POST['username']);
    $password = sanitize_text_field($_POST['password']);

    $error = new WP_Error();
    if (empty($username)) {
        $error->add(400, __("Username field 'username' is required.", 'litchi'), array('status' => 400));
        return $error;
    }

    if (empty($password)) {
        $error->add(404, __("Password field 'password' is required.", 'litchi'), array('status' => 400));
        return $error;
    }

    if (empty( $_FILES['file'] )) {
        $error->add(404, __("File field 'file' is required.", 'litchi'), array('status' => 400));
        return $error;
    }

    $user = wp_authenticate($username, $password);
    if (is_wp_error($user)) {
    return rest_ensure_response($user);
    }


    // check if user has uploaded any files


    // upload attachment and get attachment_id
    $attachment_id = wc_product_upload_attachment( $_FILES['file'] );


    if ( is_wp_error( $attachment_id ) ) {
        if ( 'db_update_error' === $attachment_id ->get_error_code() ) {
            $attachment_id ->add_data( array( 'status' => 500 ) );
        } else {
            $attachment_id ->add_data( array( 'status' => 400 ) );
        }
        return $attachment_id ;
    }
		
    $attachment = get_post( $attachment_id );




    /**
     * Fires after a single attachment is created or updated via the REST API.
     *
     * @since 4.7.0
     *
     * @param WP_Post         $attachment Inserted or updated attachment
     *                                    object.
     * @param WP_REST_Request $request    The request sent to the API.
     * @param bool            $creating   True when creating an attachment, false when updating.
     */
    do_action( 'rest_insert_attachment', $attachment, $request, true );

    // Include admin function to get access to wp_generate_attachment_metadata().
    require_once ABSPATH . 'wp-admin/includes/media.php';



    if ( isset( $request['alt_text'] ) ) {
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $request['alt_text'] ) );
    }


    $request->set_param( 'context', 'edit' );

    /**
     * Fires after a single attachment is completely created or updated via the REST API.
     *
     * @since 5.0.0
     *
     * @param WP_Post         $attachment Inserted or updated attachment object.
     * @param WP_REST_Request $request    Request object.
     * @param bool            $creating   True when creating an attachment, false when updating.
     */
    do_action( 'rest_after_insert_attachment', $attachment, $request, true );

    //$response = $this->prepare_item_for_response( $attachment, $request );

    $response = rest_ensure_response( $response );
    $response->set_status( 201 );
    //$response->header( 'Location', rest_url( sprintf( '%s/%s/%d', $this->namespace, $this->rest_base, $id ) ) );

    $response -> set_data( $attachment );

    return $response;

}

function upload_from_file( $files, $headers ) {
		if ( empty( $files ) ) {
			return new WP_Error( 'rest_upload_no_data', __( 'No data supplied.' ), array( 'status' => 400 ) );
		}

		// Verify hash, if given.
		if ( ! empty( $headers['content_md5'] ) ) {
			$content_md5 = array_shift( $headers['content_md5'] );
			$expected    = trim( $content_md5 );
			$actual      = md5_file( $files['file']['tmp_name'] );

			if ( $expected !== $actual ) {
				return new WP_Error( 'rest_upload_hash_mismatch', __( 'Content hash did not match expected.' ), array( 'status' => 412 ) );
			}
		}

		// Pass off to WP to handle the actual upload.
		$overrides = array(
			'test_form' => false,
		);

		// Bypasses is_uploaded_file() when running unit tests.
		if ( defined( 'DIR_TESTDATA' ) && DIR_TESTDATA ) {
			$overrides['action'] = 'wp_handle_mock_upload';
		}

		$size_check = check_upload_size( $files['file'] );
		if ( is_wp_error( $size_check ) ) {
			return $size_check;
		}

		/** Include admin function to get access to wp_handle_upload(). */
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$file = wp_handle_upload( $files['file'], $overrides );

		if ( isset( $file['error'] ) ) {
			return new WP_Error( 'rest_upload_unknown_error', $file['error'], array( 'status' => 500 ) );
		}

		return $file;
	}

function check_upload_size( $file ) {
		if ( ! is_multisite() ) {
			return true;
		}

		if ( get_site_option( 'upload_space_check_disabled' ) ) {
			return true;
		}

		$space_left = get_upload_space_available();

		$file_size = filesize( $file['tmp_name'] );
		if ( $space_left < $file_size ) {
			/* translators: %s: required disk space in kilobytes */
			return new WP_Error( 'rest_upload_limited_space', sprintf( __( 'Not enough space to upload. %s KB needed.' ), number_format( ( $file_size - $space_left ) / KB_IN_BYTES ) ), array( 'status' => 400 ) );
		}

		if ( $file_size > ( KB_IN_BYTES * get_site_option( 'fileupload_maxk', 1500 ) ) ) {
			/* translators: %s: maximum allowed file size in kilobytes */
			return new WP_Error( 'rest_upload_file_too_big', sprintf( __( 'This file is too big. Files must be less than %s KB in size.' ), get_site_option( 'fileupload_maxk', 1500 ) ), array( 'status' => 400 ) );
		}

		// Include admin function to get access to upload_is_user_over_quota().
		require_once ABSPATH . 'wp-admin/includes/ms.php';

		if ( upload_is_user_over_quota( false ) ) {
			return new WP_Error( 'rest_upload_user_quota_exceeded', __( 'You have used your space quota. Please delete files before uploading.' ), array( 'status' => 400 ) );
		}
		return true;
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