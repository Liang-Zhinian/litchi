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

	register_rest_field( 'shop_order', 'sub_orders', array(
		'get_callback' => 'get_sub_orders',
	));


	// 	register_rest_field( 'shop_order', 'store_x', array(
	// 		'get_callback' => 'get_store',
	// 	), 30);
}

add_filter( 'woocommerce_rest_orders_prepare_object_query', 'my_woocommerce_rest_orders_prepare_object_query', 10, 2 );
function my_woocommerce_rest_orders_prepare_object_query($args, $request){

	if ( !empty($request['customer']) ) {
		// donot show orders that have sub orders
		$args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key'     => 'has_sub_order',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key' => '_customer_user',
				'value' => $request['customer'],
				'compare' => '=',
			)
		);
	} else if ( !empty($request['show_parent']) && $request['show_parent'] == 'false' ) {
		// donot show orders that have sub orders
		$args['meta_query'] = array(
			'relation' => 'AND',
			array(
				'key'     => 'has_sub_order',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	return $args;
}



/* Add Custom Meta to the Shop Order API Response */
add_filter( 'woocommerce_rest_prepare_shop_order_object',  'prepare_shop_orders_response', 15, 3 );
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

	// 	$response->data['shipping']['phone'] = get_post_meta( $post->ID, '_shipping_phone', true);

	// 	$customer    = new WC_Customer( $response->data['customer_id'] );
	// 	$_data       = $customer->get_data();
	// 	
	$which_marketplace = which_marketplace();
	$store = $response->data['store'];
	if ($which_marketplace == 'dokan'){
		$store_user = dokan()->vendor->get( $response->data['store']['id'] );
		$store['im'] = get_im_profiles($store_user);
		$store['logo'] = $store_user->get_avatar();
		$store['social'] = $store_user->get_social_profiles();
		$store['phone'] = $store_user->get_phone();
		$store['email'] = $store_user->get_email();
	}
	$response->data['store'] = $store;

	$line_items = $response->data['line_items'];
	$line_items_reset = array();
	foreach($line_items as $line_item){
		$product = new WC_Product($line_item['product_id']);
		$line_item_reset = array();
		foreach($line_item as $key => $value){
			$line_item_reset[$key] = $value;
		}

		$attachment = wp_get_attachment_image_src( $product->get_image_id(), 'full' );
		$line_item_reset['image'] = current( $attachment );

		$variations = '';

		if ( !empty( $line_item['variation_id'] ) && $line_item['variation_id'] != 0 ) {
			$variation = wc_get_product( $line_item['variation_id'] );
			if ( ! $variation || ! $variation->exists() ) {
				continue;
			}


			$attachment = wp_get_attachment_image_src( $variation->get_image_id(), 'full' );
			$variations = current( $attachment );
		}
		$line_item_reset['variations'] = $variations;

		$line_items_reset[] = $line_item_reset;
	}
	$response->data['line_items'] = $line_items_reset;

	//try{
	// 		$query_args = array(
	// 			'parent' => $post->ID,
	// 			'post_type' => 'shop_order'
	// 		);
	// 		$query  = new WP_Query();
	// 		$result = $query->query( $query_args );

	// 		$total_posts = $query->found_posts;

	// 		$response->data['sub-orders'] = $result;
	// 	}catch(Exception $e){
	// 		return $e;
	// 	}

	return $response;

}

// function get_im_profiles($store) {
// 	return array(
// 		'qq' => $store->get_info_part( 'qq' ) ?? '',
// 	);
// }

function get_store($data,$field_name,$request){
	$which_marketplace = which_marketplace();
	$im = array();
	if ($which_marketplace == 'dokan'){
		$store = dokan()->vendor->get( $data['id'] );
		$im = get_im_profiles($store);
	}
	$store = $data['store'];
	$store['im'] = $im;
	return $store;
}

function get_sub_orders($data,$field_name,$request){
	// 	$args = array(
	// 		'parent' => $data['id'],
	// 	);
	// 	$orders = wc_get_orders( $args );

	// 	$query = new WC_Order_Query( array(
	// 		'parent' => $data['id'],
	// 		'return' => 'ids'
	// 	) );
	// 	$orders = $query->get_orders();
	// 	

	$query = new WC_Order_Query();
	$query->set( 'parent', $data['id'] );
	$orders = $query->get_orders();

	// 	$orders_controller = new WC_REST_Orders_V2_Controller();

	$sub_orders = array();
	foreach($orders as $order){
		$order_data = $order->get_data(); //new WC_Order($order -> ID);
		$tmp = array();
		foreach($order_data as $key => $value){
			$tmp[$key] = $value;
		}
		unset($tmp['line_items']);

		$line_items = $order->get_items();
		$li = array();
		foreach($line_items as $line_item){
			$line_item_data = $line_item->get_data();
			foreach($line_item_data as $key => $value){
				$li[$key] = $value;
			}
			$tmp['line_items'][] = $li;
		}

		$sub_orders[] = $tmp;
	}

	return $sub_orders;
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
		'label' => __( 'Phone', 'woocommerce' ),
		'description' => '',
	);
	$fields['shipping']['fields']['shipping_email'] = array(
		'label' => __( 'Email', 'woocommerce' ),
		'description' => '',
	);
	$fields['contact'] = array(
		'title'  => __( 'Customer contact information', 'woocommerce' ),
		'fields' => array(
			'Telephone' => array(
				'label' => __( 'Phone', 'woocommerce' ),
				'description' => '',
			),
		));
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


//////////////////////////////////////
//add_filter( 'wc_order_statuses', 'wc_renaming_order_status' );
function wc_renaming_order_status( $order_statuses ) {
	foreach ( $order_statuses as $key => $status ) {
		if ( 'wc-completed' === $key ) 
			$order_statuses['wc-completed'] = _x( 'Order Received', 'Order status', 'woocommerce' );
	}
	return $order_statuses;
}

/////////////////////////////////////////////
function when_order_status_pending($order_id) {
	write_log("$order_id set to PENDING");
}
function when_order_status_failed($order_id) {
	write_log("$order_id set to FAILED");
}
function when_order_status_hold($order_id) {
	write_log("$order_id set to ON HOLD");

	$order = new WC_Order( $order_id );
	$order_data = $order->get_data();
	$customer_id = $order_data['customer_id'];
	if(!empty($customer_id)) {
		$customer = new WC_Customer( $customer_id );
		$customer_data = $customer->get_data();
		write_log($customer_data);
	}





}
function when_order_status_processing($order_id) {
	write_log("$order_id set to PROCESSING");

	$seller_ids = dokan_get_seller_ids_by($order_id);
	            foreach ($seller_ids as $k=>$v){
                        $vendor = new Dokan_Vendor($v);
                        $mobile=$vendor->get_phone();


			$vendor = new Dokan_Vendor($v);
			$mobile= $vendor -> get_phone();

                        //向商家提醒发货
                        $inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ;
                        require_once $inc_dir. 'juhe/class-sms-sender.php';
                        $sms_sender = new Litchi_Sms_Sender();

                        $result = $sms_sender->sendMessage($mobile,'181065');
                        sleep(3);
                    }

}


function when_order_status_completed($order_id) {
	write_log("$order_id set to COMPLETED");

	// 	$sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL

	// 	$smsConf = array(
	// 		'key'   => '01785d4a4d9a4c3d56a2802eaeaaa52c', //您申请的APPKEY
	// 		'mobile'    => '13533550310', //接受短信的用户手机号码
	// 		'tpl_id'    => '179044', //您申请的短信模板ID，根据实际情况修改
	// 		'tpl_value' =>'#code#=1234&#company#=聚合数据' //您设置的模板变量，根据实际情况修改
	// 	);

	// 	$content = juhecurl($sendUrl,$smsConf,1); //请求发送短信

	// 	if($content){
	// 		$result = json_decode($content,true);
	// 		$error_code = $result['error_code'];
	// 		if($error_code == 0){
	// 			//状态为0，说明短信发送成功
	// 			echo "短信发送成功,短信ID：".$result['result']['sid'];
	// 		}else{
	// 			//状态非0，说明失败
	// 			$msg = $result['reason'];
	// 			echo "短信发送失败(".$error_code.")：".$msg;
	// 		}
	// 	}else{
	// 		//返回内容异常，以下可根据业务逻辑自行修改
	// 		echo "请求发送短信失败";
	// 	}
}
function when_order_status_refunded($order_id) {
	write_log("$order_id set to REFUNDED");
}
function when_order_status_cancelled($order_id) {
	write_log("$order_id set to CANCELLED");
}

add_action( 'woocommerce_order_status_pending', 'when_order_status_pending', 10, 1);
add_action( 'woocommerce_order_status_failed', 'when_order_status_failed', 10, 1);
add_action( 'woocommerce_order_status_on-hold', 'when_order_status_hold', 10, 1);
// Note that it's woocommerce_order_status_on-hold, and NOT on_hold.
add_action( 'woocommerce_order_status_processing', 'when_order_status_processing', 10, 1);
add_action( 'woocommerce_order_status_completed', 'when_order_status_completed', 10, 1);
add_action( 'woocommerce_order_status_refunded', 'when_order_status_refunded', 10, 1);
add_action( 'woocommerce_order_status_cancelled', 'when_order_status_cancelled', 10, 1);

function when_payment_complete( $order_id ) {
	error_log( "Payment has been received for order $order_id" );
  
}
add_action( 'woocommerce_payment_complete', 'when_payment_complete', 10, 1 );



/**
 * 请求接口返回内容
 * @param  string $url [请求的URL地址]
 * @param  string $params [请求的参数]
 * @param  int $ipost [是否采用POST形式]
 * @return  string
 */
function juhecurl($url,$params=false,$ispost=0){
	$httpInfo = array();
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1 );
	curl_setopt( $ch, CURLOPT_USERAGENT , 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22' );
	curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT , 30 );
	curl_setopt( $ch, CURLOPT_TIMEOUT , 30);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER , true );
	if( $ispost )
	{
		curl_setopt( $ch , CURLOPT_POST , true );
		curl_setopt( $ch , CURLOPT_POSTFIELDS , $params );
		curl_setopt( $ch , CURLOPT_URL , $url );
	}
	else
	{
		if($params){
			curl_setopt( $ch , CURLOPT_URL , $url.'?'.$params );
		}else{
			curl_setopt( $ch , CURLOPT_URL , $url);
		}
	}
	$response = curl_exec( $ch );
	if ($response === FALSE) {
		//echo "cURL Error: " . curl_error($ch);
		return false;
	}
	$httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
	$httpInfo = array_merge( $httpInfo , curl_getinfo( $ch ) );
	curl_close( $ch );
	return $response;
}