<?php

defined( 'ABSPATH' ) || exit;

class Litchi_REST_Warranty_Controller extends WP_REST_Controller {

	/**
     * Endpoint namespace
     *
     * @var string
     */
	protected $namespace = 'litchi/v1';

	/**
     * Route name
     *
     * @var string
     */
	protected $base = 'warranties';

	/**
     * Post type
     *
     * @var string
     */
	protected $post_type = 'warranty';

	/**
     * Constructor function
     *
     * @since 2.7.0
     *
     * @return void
     */
	public function __construct()
	{
		# code...
	}

	/**
     * Register all routes releated with stores
     *
     * @return void
     */
	public function register_routes()
	{
		register_rest_route( $this->namespace, '/' . $this->base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => array(

				),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), 
													 array(
														 'order_id' => array(
															 'description' => __( 'order_id', 'litchi' ),
															 'required' => true,
															 'type'     => 'integer',
														 ),
														 'items'          => array(
															 'description' => __( 'Items data.', 'litchi' ),
															 'type'        => 'array',
															 'context'     => array( 'view', 'edit' ),
															 'items'       => array(
																 'type'       => 'object',
																 'properties' => array(
																	 'product_id'    => array(
																		 'description' => __( 'Product ID.', 'litchi' ),
																		 'type'        => 'integer',
																		 'context'     => array( 'view', 'edit' ),
																		 'readonly'    => true,
																	 ),
																	 'item_id'   => array(
																		 'description' => __( 'Order Item ID.', 'litchi' ),
																		 'type'        => 'integer',
																		 'context'     => array( 'view', 'edit' ),
																	 ),
																	 'quantity' => array(
																		 'description' => __( 'Quantity.', 'litchi' ),
																		 'type'        => 'integer',
																		 'context'     => array( 'view', 'edit' ),
																	 ),
																 ),
															 ),
														 ),    
														 "type" => array(
															 'description' => __( 'order_id', 'litchi' ),
															 'required' => true,
															 'type'     => 'string',
														 ),
														 "reasons" => array(
															 'description' => __( 'order_id', 'litchi' ),
															 'required' => true,
															 'type'     => 'string',
														 )

													 ) 
													)
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		register_rest_route( $this->namespace, '/' . $this->base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => array(
						'default' => 'view',
					),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( false ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default' => false,
					),
				),
			),
		) );
		register_rest_route( $this->namespace, '/' . $this->base . '/schema', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $this, 'get_public_item_schema' ),
		) );
	}

	public function get_items ( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
		}

		global $WCFM, $wpdb, $_POST, $WCFMmp;

		$params = $request->get_params();

		$count_params = array('count' => 'true');
		$count = $this -> wcrw_get_warranty_request( $count_params );

		$objects = $this -> wcrw_get_warranty_request( $params );

		$data_objects = array();
		foreach ( $objects as $object ) {
			$data           = $this->prepare_item_for_response( $object, $request );
			$data_objects[] = $this->prepare_response_for_collection( $data );
		}
		$response = rest_ensure_response( $data_objects );
		$response = $this->format_collection_response( $response, $request, $count['total_count'] );

		return $response;

	}

	/**
     * Get one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
	public function get_item( $request ) {
		//get parameters from request
		$params = $request->get_params();

		$id = $params['id'];

		$item = $this -> wcrw_get_warranty_request( array('id' => $id) );

		if( empty( $item ) ) {
			return new WP_Error( 'litchi_rest_warranty_invalid_id', __( 'Invalid Warranty Request ID', 'litchi' ), array( 'status' => 404 ) );
		}

		$data = $this->prepare_item_for_response( $item, $request );

		return $data;
	}

	public function create_item ( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
		}

		global $WCFM, $WCFMmp, $wpdb;

		$body = $request->get_json_params();

		//return $body;

		//require_once ABSPATH . 'wp-content/plugins/wc-return-warrranty/includes/functions.php';

		$data = $this -> create_warranty_request($body);

		if ( ! is_wp_error( $data ) && $data != false ) {
			$id = $data;
			$item = $this -> wcrw_get_warranty_request( array('id' => $id) );

			return new WP_REST_Response( $item, 200 );
		}

		return new WP_Error( 'cant-create', __( 'message', 'litchi' ), array( 'status' => 500 ) );
	}

	/**
     * Update one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
	public function update_item( $request ) {		

		$args = $this->prepare_item_for_database( $request );

		$item = $this -> wcrw_parse_args( $request->get_json_params(), $args );

		require_once ABSPATH . 'wp-content/plugins/wc-return-warrranty/includes/functions.php';
		if ( function_exists( 'wcrw_update_warranty_request' ) ) {
			$data = wcrw_update_warranty_request( $item );
			if ( ! is_wp_error( $data ) ) {
				$id = $item['id'];

				$item = $this -> wcrw_get_warranty_request( array('id' => $id) );
				$data = $this->prepare_item_for_response( $item, $request );

				return new WP_REST_Response( $data, 200 );
			} else {
				return $data;
			}
		}

		return new WP_Error( 'cant-update', __( 'message', 'litchi' ), array( 'status' => 500 ) );
	}

	/**
     * Delete one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Request
     */
	public function delete_item( $request ) {
		$item = $this->prepare_item_for_database( $request );

		if ( function_exists( 'slug_some_function_to_delete_item' ) ) {
			$deleted = slug_some_function_to_delete_item( $item );
			if ( $deleted ) {
				return new WP_REST_Response( true, 200 );
			}
		}

		return new WP_Error( 'cant-delete', __( 'message', 'text-domain' ), array( 'status' => 500 ) );
	}

	/**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
	public function get_items_permissions_check( $request ) {
		//return true; <--use to make readable by all
		return true;
	}

	/**
     * Check if a given request has access to get a specific item
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
	public function get_item_permissions_check( $request ) {
		return $this->get_items_permissions_check( $request );
	}

	/**
     * Check if a given request has access to create items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
	public function create_item_permissions_check( $request ) {
		return true;
	}

	/**
     * Check if a given request has access to update a specific item
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
	public function update_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	/**
     * Check if a given request has access to delete a specific item
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
	public function delete_item_permissions_check( $request ) {
		return $this->create_item_permissions_check( $request );
	}

	protected function get_object( $id ) {
		return new WP_Error( 'invalid-method', sprintf( __( "Method '%s' not implemented. Must be overridden in subclass.", 'litchi' ), __METHOD__ ), array( 'status' => 405 ) );
	}

	public function prepare_response_for_collection( $response ) {
		if ( ! ( $response instanceof WP_REST_Response ) ) {
			return $response;
		}

		$data   = (array) $response->get_data();
		$server = rest_get_server();

		if ( method_exists( $server, 'get_compact_response_links' ) ) {
			$links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
		} else {
			$links = call_user_func( array( $server, 'get_response_links' ), $response );
		}

		if ( ! empty( $links ) ) {
			$data['_links'] = $links;
		}

		return $data;
	}

	public function prepare_item_for_database( $request ) {
		$params = $request->get_params();

		return $params;
	}

	public function prepare_item_for_response( $item, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data = array(
			'id'                    => $item['id'],
		);

		$response = rest_ensure_response( $item );
		return apply_filters( "litchi_rest_prepare_{$this->post_type}_object", $response, $item, $request );
	}

	public function format_collection_response( $response, $request, $total_items ) {
		if ( $total_items === 0 ) {
			return $response;
		}

		// Store pagation values for headers then unset for count query.
		$per_page = (int) ( ! empty( $request['per_page'] ) ? $request['per_page'] : 20 );
		$page     = (int) ( ! empty( $request['page'] ) ? $request['page'] : 1 );

		$response->header( 'X-WP-Total', (int) $total_items );

		$max_pages = ceil( $total_items / $per_page );

		$response->header( 'X-WP-TotalPages', (int) $max_pages );
		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {

			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	function create_warranty_request( $postdata = [] ) {
		global $wpdb;

		require_once ABSPATH . 'wp-content/plugins/wc-return-warrranty/includes/functions.php';

		$request_table     = $wpdb->prefix . 'wcrw_warranty_requests';
		$request_map_table = $wpdb->prefix . 'wcrw_request_product_map';

		$default = [
			'items'       => [],
			'order_id'    => 0,
			'customer_id' => get_current_user_id(),
			'type'        => '',
			'reasons'     => '',
			'status'      => 'new',
			'meta'        => [],
			'created_at'  => current_time( 'mysql' )
		];

		$args = $this -> wcrw_parse_args( $postdata, $default );

		// If have any order
		if ( empty( $args['order_id'] ) ) {
			return new WP_Error( 'no-order-id', __( 'No order found', 'wc-return-warranty-management' ) );
		}

		// Checking if customer select any items for sending request
		if ( empty( $args['items'] ) ) {
			return new WP_Error( 'no-items', __( 'Please select any item for sending request', 'wc-return-warranty-management' ) );
		}

		// Check if type exist or not
		if ( empty( $args['type'] ) ) {
			return new WP_Error( 'no-type', __( 'Request type must be required', 'wc-return-warranty-management' ) );
		}

		$args = apply_filters( 'wcrw_warranty_request_postdata', $args, $postdata );

		$wpdb->insert(
			$request_table,
			[
				'order_id'    => $args['order_id'],
				'customer_id' => $args['customer_id'],
				'type'        => $args['type'],
				'status'      => $args['status'],
				'reasons'     => $args['reasons'],
				'meta'        => maybe_serialize( $args['meta'] ),
				'created_at'  => $args['created_at'],
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		$request_id = $wpdb->insert_id;

		foreach ( $args['items'] as $item ) {
			$wpdb->insert(
				$request_map_table,
				[
					'request_id' => $request_id,
					'product_id' => $item['product_id'],
					'quantity'   => $item['quantity'],
					'item_id'    => $item['item_id']
				],
				[ '%d', '%d', '%d' ]
			);
		}

		if ( $request_id ) {
			do_action( 'wcrw_created_warranty_request', $request_id, $args, $postdata );
			return $request_id;
		}

		return false;
	}

	function wcrw_get_warranty_request( $data = [] ) {
		global $wpdb;

		$default = [
			'id'      => 0,
			'per_page' => 20,
			'page'  => 0,
			'orderby' => 'created_at',
			'order'   => 'desc',
			'count'   => false,
		];

		$data              = $this -> wcrw_parse_args( $data, $default );
		$request_table     = $wpdb->prefix . 'wcrw_warranty_requests';
		$request_map_table = $wpdb->prefix . 'wcrw_request_product_map';
		$response          = [];

		if ( $data['count'] ) {
			$sql = "SELECT count('id') as total_count FROM {$request_table} as rt WHERE 1=1";
		} else {
			$sql = "SELECT rt.*, GROUP_CONCAT( rit.product_id SEPARATOR ',') AS 'products', GROUP_CONCAT( rit.quantity SEPARATOR ', ') AS 'quantity', GROUP_CONCAT( rit.item_id SEPARATOR ', ') AS 'item_id' FROM {$request_table} as rt INNER JOIN {$request_map_table} as rit ON rt.id=rit.request_id WHERE 1=1";
		}

		$current_user_id = get_current_user_id();
		$sql .= " AND rt.customer_id={$current_user_id}";

		if ( ! empty( $data['type'] ) ) {
			$sql .= " AND rt.type='{$data['type']}'";
		}

		if ( ! empty( $data['customer_id'] ) ) {
			$sql .= " AND rt.customer_id='{$data['customer_id']}'";
		}

		if ( ! empty( $data['order_id'] ) ) {
			$sql .= " AND rt.order_id='{$data['order_id']}'";
		}

		if ( ! empty( $data['reasons'] ) ) {
			$sql .= " AND rt.reasons='{$data['reasons']}'";
		}

		if ( ! empty( $data['status'] ) ) {
			$sql .= " AND rt.status='{$data['status']}'";
		}

		if ( $data['id'] ) {
			$sql .= " AND rt.id='{$data['id']}'";
		}

		if ( ! $data['count'] ) {
			$sql .= " GROUP BY rt.id ORDER BY {$data['orderby']} {$data['order']} LIMIT {$data['page']}, {$data['per_page']}";
		}

		if ( $data['count'] || $data['id'] ) {
			$result = $wpdb->get_row( $sql, ARRAY_A );

			if ( $result ) {
				if ( ! $data['count'] ) {
					return $this -> wcrw_transformer_warranty_request( $result );
				}
				return $result;
			}
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		if ( ! empty( $results ) ) {
			foreach ( $results as $key => $result ) {
				$response[] = $this -> wcrw_transformer_warranty_request( $result );
			}
		}

		return $response;
	}

	function wcrw_transformer_warranty_request( $data ) {
		$items       = [];
		$product_ids = explode( ',', $data['products'] );
		$quantites   = explode( ',', $data['quantity'] );
		$item_ids    = explode( ',', $data['item_id'] );
		$order       = wc_get_order( $data['order_id'] );

		foreach ( $item_ids as $key => $item_id ) {
			$item = new WC_Order_Item_Product( $item_id );
			$product = wc_get_product( $item->get_product_id() );
			$image = wp_get_attachment_url( $product->get_image_id() );

			$items[] = [
				'id'             => $product->get_id(),
				'title'          => $product->get_title(),
				'thumbnail'      => $image ? $image : wc_placeholder_img_src(),
				'quantity'       => $quantites[$key],
				'url'            => $product->get_permalink(),
				'price'          => $order->get_item_subtotal( $item, false ),
				'item_id'        => $item_id,
				'order_quantity' => $item->get_quantity(),
			];
		}

		if ( ! empty( $data['customer_id'] ) ) {
			$customer = get_user_by( 'id', $data['customer_id'] );
		} else {
			$customer = false;
		}

		return apply_filters( 'wcrw_get_warranty_single_data', [
			'id'          => $data['id'],
			'order_id'    => $data['order_id'],
			'customer' => [
				'billing' => [
					'first_name' => $order->get_billing_first_name(),
					'last_name' => $order->get_billing_last_name(),
					'email' => $order->get_billing_email(),
					'address' => $order->get_formatted_billing_address()
				],
				'first_name' => $customer ? $customer->first_name : '',
				'last_name' => $customer ? $customer->last_name: '',
				'email' => $customer ? $customer->user_email: '',
				'id'   => $order->get_customer_id(),
				'ip_address'   => $order->get_customer_ip_address(),
				'user_agent' => $order->get_customer_user_agent()
			],
			'items'       => $items,
			'type'        => $data['type'],
			'status'      => $data['status'],
			'reasons'     => $data['reasons'],
			'meta'        => maybe_unserialize( $data['meta'] ),
			'created_at'  => $data['created_at']
		] );
	}

	function wcrw_parse_args( &$args, $defaults = [] ) {
		$args     = (array) $args;
		$defaults = (array) $defaults;
		$r        = $defaults;

		foreach ( $args as $k => &$v ) {
			if ( is_array( $v ) && isset( $r[ $k ] ) ) {
				$r[ $k ] = $this -> wcrw_parse_args( $v, $r[ $k ] );
			} else {
				$r[ $k ] = $v;
			}
		}

		return $r;
	}
}
