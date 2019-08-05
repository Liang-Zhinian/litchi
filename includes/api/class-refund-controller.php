<?php

defined( 'ABSPATH' ) || exit;

class Litchi_REST_Refund_Controller extends WP_REST_Controller {

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
    protected $base = 'refunds';

    /**
     * Post type
     *
     * @var string
     */
	protected $post_type = 'refund';

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
				//'permission_callback' => array( $this, 'get_refunds_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				//'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args' => array(
            
                'order_id' => array(
                    'description' => __( 'order_id', 'litchi' ),
                    'required' => true,
                    'type'     => 'integer',
                )
            )
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}
	
	public function get_items ( WP_REST_Request $request ) {
		$current_user_id = get_current_user_id();

		if ( empty( $current_user_id ) ) {
			return new WP_Error( 'rest_not_logged_in', __( 'You are not currently logged in.' ), array( 'status' => 401 ) );
		}
															
		global $WCFM, $wpdb, $_POST, $WCFMmp;

		$params = $request->get_params();
		
		$length = sanitize_text_field( $params['length'] );
		$offset = sanitize_text_field( $params['start'] );
		
		$the_orderby = ! empty( $params['orderby'] ) ? sanitize_text_field( $params['orderby'] ) : 'ID';
		$the_order   = ( ! empty( $params['order'] ) && 'asc' === $params['order'] ) ? 'ASC' : 'DESC';
		
		$transaction_id = ! empty( $params['transaction_id'] ) ? sanitize_text_field( $params['transaction_id'] ) : '';
		
		$refund_vendor_filter = '';
		if ( ! empty( $params['refund_vendor'] ) ) {
			$refund_vendor = esc_sql( $params['refund_vendor'] );
			$refund_vendor_filter = " AND commission.`vendor_id` = {$refund_vendor}";
		}
		
		// $status_filter = 'requested';
		$status_filter = '';
		if( isset($params['status_type']) ) {
			$status_filter = sanitize_text_field( $params['status_type'] );
		}
		if( $status_filter ) {
			$status_filter = " AND commission.refund_status = '" . $status_filter . "'";
		}
															
		$requester_filter = " AND commission.`requested_by` = {$current_user_id}";

		$sql = 'SELECT COUNT(commission.ID) FROM ' . $wpdb->prefix . 'wcfm_marketplace_refund_request AS commission';
		$sql .= ' WHERE 1=1';
		if( $transaction_id ) $sql .= " AND commission.ID = $transaction_id";
		$sql .= $status_filter;
		$sql .= $refund_vendor_filter;
		$sql .= $requester_filter;
		
		$filtered_refund_requests_count = $wpdb->get_var( $sql );
		if( !$filtered_refund_requests_count ) $filtered_refund_requests_count = 0;

		$sql = 'SELECT * FROM ' . $wpdb->prefix . 'wcfm_marketplace_refund_request AS commission';
		$sql .= ' WHERE 1=1';
		if( $transaction_id ) $sql .= " AND commission.ID = $transaction_id";
		$sql .= $status_filter;
		$sql .= $refund_vendor_filter;
		$sql .= $requester_filter;
		$sql .= " ORDER BY `{$the_orderby}` {$the_order}";
		$sql .= " LIMIT {$length}";
		$sql .= " OFFSET {$offset}";
		
		$objects = $wpdb->get_results( $sql );

		$data_objects = array();
        foreach ( $objects as $object ) {
            $data           = $this->prepare_data_for_response( $object, $request );
            $data_objects[] = $this->prepare_response_for_collection( $object );
        }
        $response = rest_ensure_response( $data_objects );
        $response = $this->format_collection_response( $response, $request, $filtered_refund_requests_count );

        return $response;

	}

	public function create_item ( WP_REST_Request $request ) {
		global $WCFM, $WCFMmp, $wpdb;

		$body = $request->get_json_params();

		$order_id = $body['order_id'];
		$refund_item_id = $body['refund_item_id'];
		$refund_reason = $body['refund_reason'];
		$refund_request = $body['refund_request'];
		$refunded_amount = $body['refunded_amount'];

		$response = $this -> processing($order_id, $refund_item_id, $refunded_amount, $refund_request, $refund_reason);

		return $response;
	}

    public function processing($order_id, $refund_item_id, $refunded_amount, $refund_request, $refund_reason) {
		global $WCFM, $WCFMmp, $wpdb;
			  
		$wcfm_refund_messages = get_wcfm_refund_requests_messages();
		$has_error = false;
			
		
		if(isset($refund_reason) && !empty($refund_reason)) {
	  	
			$refund_reason    = wcfm_stripe_newline( $refund_reason );
			$refund_reason    = esc_sql( $refund_reason );
			$order_id         = absint( $order_id );
			$commission_id    = 0;
			$refund_item_id   = absint( $refund_item_id );
			$refund_request   = $refund_request;
			$refunded_amount  = $refunded_amount;
			$refund_status    = 'pending';
			
			$product_id       = 0;
			$vendor_id        = 0;
			$item_total       = 0;
			$old_refunds      = 0;
			
			$sql = 'SELECT ID, product_id, vendor_id, item_total, refunded_amount FROM ' . $wpdb->prefix . 'wcfm_marketplace_orders AS commission';
			$sql .= ' WHERE 1=1';
			$sql .= " AND `order_id` = " . $order_id;
			$sql .= " AND `item_id`  = " . $refund_item_id;
			$commissions = $wpdb->get_results( $sql );
			if( !empty( $commissions ) ) {
				foreach( $commissions as $commission ) {
					$commission_id    = $commission->ID;
					$product_id       = $commission->product_id;
					$vendor_id        = $commission->vendor_id;
					$item_total       = $commission->item_total;
					$old_refunds      = $commission->refunded_amount;
				}
			}
			
			$order                = wc_get_order( $order_id );
			
			if( !$vendor_id ) {
				$line_item  = new WC_Order_Item_Product( $refund_item_id );
				$item_total = $line_item->get_total();
			}
			
			if( $refund_request == 'full' ) {
				$refunded_amount = $item_total - (float)$old_refunds;
			} elseif( (float)$refunded_amount > ((float)$item_total - (float)$old_refunds) ) {
				echo '{"status": false, "message": "' . __('Refund request amount more than item value.', 'wc-multivendor-marketplace') . '"}';
				die;
			}
			
			$refund_request_id = $WCFMmp->wcfmmp_refund->wcfmmp_refund_processed( $vendor_id, $order_id, $commission_id, $refund_item_id, $refund_reason, $refunded_amount, $refund_request );
	  	
			if( $refund_request_id && !is_wp_error( $refund_request_id ) ) {
				// Update Commissions Table Refund Status
				$wpdb->update("{$wpdb->prefix}wcfm_marketplace_orders", array('refund_status' => 'requested'), array('ID' => $commission_id), array('%s'), array('%d'));
				
				$refund_auto_approve = isset( $WCFMmp->wcfmmp_refund_options['refund_auto_approve'] ) ? $WCFMmp->wcfmmp_refund_options['refund_auto_approve'] : 'no';
				if( ( $refund_auto_approve == 'yes' ) && $vendor_id && wcfm_is_vendor() ) {
					
					// Update refund status
					$refund_update_status = $WCFMmp->wcfmmp_refund->wcfmmp_refund_status_update_by_refund( $refund_request_id );
					
					if( $refund_update_status ) {
						// Admin Notification
						$wcfm_messages = sprintf( __( 'Refund <b>%s</b> has been processed for Order <b>%s</b> item <b>%s</b> by <b>%s</b>', 'wc-multivendor-marketplace' ), '<a target="_blank" class="wcfm_dashboard_item_title" href="' . add_query_arg( 'request_id', $refund_request_id, wcfm_refund_requests_url() ) . '">#' . $refund_request_id . '</a>', '<a target="_blank" class="wcfm_dashboard_item_title" href="' . get_wcfm_view_order_url( $order_id ) . '">#' . $order->get_order_number() . '</a>', get_the_title( $product_id ), $WCFM->wcfm_vendor_support->wcfm_get_vendor_store_by_vendor( $vendor_id ) );
						$WCFM->wcfm_notification->wcfm_send_direct_message( -2, 0, 1, 0, $wcfm_messages, 'refund-request' );
						
						// Order Note
						$order->add_order_note( $wcfm_messages, 0 );
						
						do_action( 'wcfmmp_refund_request_approved', $refund_request_id );
						
						echo '{"status": true, "message": "' . __('Refund requests successfully processed.', 'wc-multivendor-marketplace') . ' #' . $refund_request_id . '"}';
					} else {
						echo '{"status": false, "message": "' . __('Refund processing failed, please contact site admin.', 'wc-multivendor-marketplace') . ' #' . $refund_request_id . '"}';
					}
				} else {
					// Admin Notification
					$wcfm_messages = sprintf( __( 'You have recently received a Refund Request <b>%s</b> for Order <b>%s</b> item <b>%s</b>', 'wc-multivendor-marketplace' ), '<a target="_blank" class="wcfm_dashboard_item_title" href="' . add_query_arg( 'request_id', $refund_request_id, wcfm_refund_requests_url() ) . '">#' . $refund_request_id . '</a>', '<a target="_blank" class="wcfm_dashboard_item_title" href="' . get_wcfm_view_order_url( $order_id ) . '">#' . $order->get_order_number() . '</a>', get_the_title( $product_id ) );
					$WCFM->wcfm_notification->wcfm_send_direct_message( -2, 0, 1, 0, $wcfm_messages, 'refund-request' );
					
					// Send Vendor Notification
					if( $vendor_id && !wcfm_is_vendor() ) {
						$is_allow_refund = $WCFM->wcfm_vendor_support->wcfm_vendor_has_capability( $vendor_id, 'refund-request' );
						if( $is_allow_refund && apply_filters( 'wcfm_is_allow_refund_vendor_notification', true ) ) {
							$WCFM->wcfm_notification->wcfm_send_direct_message( -1, $vendor_id, 1, 0, $wcfm_messages, 'refund-request' );
						}
					}
					
					// Order Note
					$order->add_order_note( $wcfm_messages, 0 );
					
					echo '{"status": true, "message": "' . $wcfm_refund_messages['refund_requests_saved'] . ' #' . $refund_request_id . '", "refund_request_id": ' . $refund_request_id . '}';
					
					
				}
				
				do_action( 'wcfm_after_refund_request',  $refund_request_id, $order_id, $commission_id, $refund_item_id, $vendor_id, $refund_reason );
				
			} else {
				echo '{"status": false, "message": "' . $wcfm_refund_messages['refund_requests_failed'] . '"}';
			}
		} else {
			echo '{"status": false, "message": "' . $wcfm_refund_messages['no_refund_reason'] . '"}';
		}
		
		die;
	}
	
	public function get_refunds_permissions_check() {
		return true;
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

	protected function prepare_data_for_response( $object, $request ) {
        return new WP_Error( 'invalid-method', sprintf( __( "Method '%s' not implemented. Must be overridden in subclass.", 'wcfm-marketplace-rest-api' ), __METHOD__ ), array( 'status' => 405 ) );
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
}