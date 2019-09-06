<?php



/**
 * Returngoods API Controller
 *
 * @package litchi
 *
 * @author
 */

defined('ABSPATH') || exit;

//require_once ABSPATH . 'wp-includes/rest-api/endpoints/class-wp-rest-attachments-controller.php';

/**
 * REST API Information controller class.
 *
 * @package Litchi/API
 * @extends WP_REST_Controller
 */

class Litchi_REST_Returngoods_Controller extends WP_REST_Controller
{

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
	protected $base = 'returngoods';

	/**
     * Post type
     *
     * @var string
     */
	protected $post_type = 'returngoods';

	/**
     * Constructor function
     *
     * @return void
     * @since 2.7.0
     *
     */
	public function __construct()
	{
		# code...
		/*
        $inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ;
        require_once $inc_dir. 'log.php';
        $this->logger = Logger::Init( Logger::DefaultLogFileHandler(), 15);*/
	}

	/**
     * Register all routes releated with media
     *
     * @return void
     */
	public function register_routes()
	{

		// POST: /wp-json/litchi/v1/products/add
		register_rest_route($this->namespace, '/' . $this->base . '/add_returngoods', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array($this, 'add_returngoods'),
			'permission_callback' => array($this, 'add_returngoods_permissions_check'),
			'args' => array(
				//                'order_id' => array(
				//                    'description' => __( 'Unique identifier for the social account.', 'litchi' ),
				//                    'type'        => 'integer',
				//                    'required'    => true,
				//                ),
				//                'vendor_id' => array(
				//                    'description' => __( 'Unique identifier for the social account.', 'litchi' ),
				//                    'type'        => 'integer',
				//                    'required'    => true,
				//                ),
				//                'customer_id' => array(
				//                    'description' => __( 'Unique identifier for the social account.', 'litchi' ),
				//                    'type'        => 'integer',
				//                    'required'    => true,
				//                ),
				//                'type' => array(
				//                    'description' => __( 'Email address for the social account.', 'litchi' ),
				//                    'type'        => 'string',
				//                    'required'    => true,
				//                ),
				//                'status' => array(
				//                    'description' => __( 'Email address for the social account.', 'litchi' ),
				//                    'type'        => 'string',
				//                    'required'    => true,
				//                ),
				//                'reasons' => array(
				//                    'description' => __( 'Email address for the social account.', 'litchi' ),
				//                    'type'        => 'string',
				//                    'required'    => true,
				//                ),
				//                'details' => array(
				//                    'description' => __( 'Email address for the social account.', 'litchi' ),
				//                    'type'        => 'string',
				//                    'required'    => true,
				//                ),
				//                'note' => array(
				//                    'description' => __( 'Email address for the social account.', 'litchi' ),
				//                    'type'        => 'string',
				//                    'required'    => true,
				//                ),
				//                'items' => array(
				//                    'product_id' => array(
				//                        'description' => __( 'Unique identifier for the social account.', 'litchi' ),
				//                        'type'        => 'integer',
				//                        'required'    => true,
				//                    ),
				//                    'quantity' => array(
				//                        'description' => __( 'Unique identifier for the social account.', 'litchi' ),
				//                        'type'        => 'integer',
				//                        'required'    => true,
				//                    ),
				//                    'item_id' => array(
				//                        'description' => __( 'Unique identifier for the social account.', 'litchi' ),
				//                        'type'        => 'integer',
				//                        'required'    => true,
				//                    ),
				//                 )
			)
		));

		//        'product_id' => $data['product_id'],
		//                    'quantity'   => $data['quantity'],
		//                    'item_id'    => $data['item_id']

		//        'order_id'    => $data['order_id'],
		//                'vendor_id'   => $data['vendor_id'],
		//                'customer_id' => $data['customer_id'],
		//                'type'        => $data['type'],
		//                'status'      => $data['status'],
		//                'reasons'     => $data['reasons'],
		//                'details'     => wp_kses_post( $data['details'] ),
		//                'note'        => $data['note'],
		//                'created_at'  => $data['created_at'],

		// POST: /wp-json/litchi/v1/products/add
		register_rest_route($this->namespace, '/' . $this->base . '/update_returngoods', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'update_returngoods'),
			'permission_callback' => array($this, 'update_returngoods_permissions_check'),
			'args' => array()
		));

		register_rest_route($this->namespace, '/' . $this->base . '/returngoods', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array($this, 'returngoods'),
			'permission_callback' => array($this, 'returngoods_permissions_check'),
			'args' => array(
			)
		));
	} // register_routes()

	public function add_returngoods_permissions_check(){ return true; }

	public function add_returngoods(WP_REST_Request $request){
		global $wpdb;
		$data = $request->get_json_params();

		$default = [
			'items'       => [],
			'order_id'    => 0,
			'vendor_id'   => 0,
			'customer_id' => get_current_user_id(),
			'type'        => 'replace',
			'reasons'     => '',
			'status'      => 'new',
			'details'     => '',
			'note'        => '',
			'created_at'  => current_time( 'mysql' )
		];

		$data = dokan_parse_args( $data, $default );

		// Check if not order id passed
		if ( empty( $data['order_id'] ) ) {
			return new WP_Error( 'no-order-id', __( 'No order found', 'dokan' ) );
		}

		// Check if not have any vendor
		if ( empty( $data['vendor_id'] ) ) {
			return new WP_Error( 'no-vendor-id', __( 'No vendor found', 'dokan' ) );
		}

		// Check if customer select any product or not. If not select any product we do not proceed to create request
		if ( empty( $data['items'] ) ) {
			return new WP_Error( 'no-product-id', __( 'Please select some item for sending request', 'dokan' ) );
		}

		if ( empty( $data['type'] ) ) {
			return new WP_Error( 'no-type', __( 'Request type must be required', 'dokan' ) );
		}

		$request_table      = $wpdb->prefix . 'dokan_rma_request';
		$request_item_table = $wpdb->prefix . 'dokan_rma_request_product';

		$wpdb->insert(
			$request_table,
			[
				'order_id'    => $data['order_id'],
				'vendor_id'   => $data['vendor_id'],
				'customer_id' => $data['customer_id'],
				'type'        => $data['type'],
				'status'      => $data['status'],
				'reasons'     => $data['reasons'],
				'details'     => wp_kses_post( $data['details'] ),
				'note'        => $data['note'],
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ]
		);

		$request_id = $wpdb->insert_id;

		foreach ( $data['items'] as $item ) {
			//return $item['product_id'];
			$wpdb->insert(
				$request_item_table,
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
			do_action( 'dokan_rma_save_warranty_request', $data );

			return true;
		}

		return false;
	}

	public function update_returngoods_permissions_check(){ return true; }


	public function update_returngoods(WP_REST_Request $request){
		global $wpdb;

		$data = $request->get_json_params();
		if ( empty( $data['id'] ) ) {
			return new WP_Error( 'no-request-id', __( 'No request id found', 'dokan' ) );
		}

		$statuses      = dokan_warranty_request_status();
		$request_table = $wpdb->prefix . 'dokan_rma_request';

		$request = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$request_table} WHERE `id`=%d", $data['id'] ), ARRAY_A );
		$data    = dokan_parse_args( $data, $request );

		if ( ! in_array( $data['status'], array_keys( $statuses ) ) ) {
			return new WP_Error( 'no-valid-status', __( 'Your status is not valid', 'dokan' ) );
		}

		$result = $wpdb->update( $request_table, $data, [ 'id' => $data['id'] ] );

		if ( ! $result ) {
			return new WP_Error( 'status-not-updated', __( 'Status not updated, Please try again', 'dokan' ) );
		}

		return $result;
	}



	public function returngoods_permissions_check()
	{
		return true;
	}

	public function returngoods()
	{
		
		$warrnty_requests = new Dokan_RMA_Warranty_Request();
		$data           = [];
		$pagination_html = '';


		$item_per_page  = isset( $_GET['numpage'] ) ? abs( (int) $_GET['numpage'] ) : 10;
		$total_count    = dokan_get_warranty_request( [ 'count' => true ] );
		$page           = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
		$offset         = ( $page * $item_per_page ) - $item_per_page;
		$total_page     = ceil( $total_count['total_count']/$item_per_page );

		if ( ! empty( $_GET['status'] ) ) {
			$data['status'] = $_GET['status'];
		}

		$data['customer_id'] = $_GET['customer_id'];
		$data['status'] = $_GET['status'];
		$data['type'] = $_GET['type'];
		$count=count($warrnty_requests->all( $data ));	
		$data['number']      = $item_per_page;
		$data['offset']      = $offset;
		


		if( $total_page > 1 ){
			$pagination_html = '<div class="pagination-wrap">';
			$page_links = paginate_links( array(
				'base'      => add_query_arg( 'cpage', '%#%' ),
				'format'    => '',
				'type'      => 'array',
				'prev_text' => __( '&laquo; Previous', 'dokan-lite' ),
				'next_text' => __( 'Next &raquo;', 'dokan-lite' ),
				'total'     => $total_page,
				'current'   => $page
			) );
			$pagination_html .= '<ul class="pagination"><li>';
			$pagination_html .= join( "</li>\n\t<li>", $page_links );
			$pagination_html .= "</li>\n</ul>\n";
			$pagination_html .= '</div>';
		};
		$datas['value'] = $warrnty_requests->all( $data );
		$datas['count'] = $count;

		return $datas;
	}

}
