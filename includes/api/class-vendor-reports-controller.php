<?php

/**
* Cart API Controller
*
* @package litchi
*
* @author 
*/

class Litchi_REST_Vendor_Reports_Controller extends WP_REST_Controller {

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
	protected $base = 'reports';

	/**
     * Post type
     *
     * @var string
     */
	protected $post_type = 'wcfmmp_reports';

	/**
     * Constructor function
     *
     * @since 2.7.0
     *
     * @return void
     */
	public function __construct() {
		# code...
	}

	/**
     * Register all routes releated with stores
     *
     * @return void
     */
	public function register_routes() {
		// GET: /wp-json/litchi/v1/reports/sales_report_by_date
		register_rest_route( $this->namespace, $this->base.'/sales_report_by_date', array(
			'args' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
				),
				'thumb' => array(
					'default' => null
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_sales_report_by_date' ),
				'permission_callback' => array( $this, 'get_sales_report_by_date_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
		) );


	} // register_routes()



	/**
	 * Get cart.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @version 1.0.6
	 * @param   array $request
	 * @return  WP_REST_Response
	 */
	public function get_sales_report_by_date( $request = array() ) {
		$which_marketplace = which_marketplace();

		if ($which_marketplace == 'wcfmmarketplace') {
			global $WCFMmp, $WCFM, $wpdb, $LITCHI_INC_DIR;
			/*
        include_once( $WCFM->plugin_path . 'includes/reports/class-wcfmmarketplace-report-sales-by-date.php' );
        $wcfm_report_sales_by_date = new WCFM_Marketplace_Report_Sales_By_Date( 'month' );
        $wcfm_report_sales_by_date->calculate_current_range( 'month' );

        $report_data   = $wcfm_report_sales_by_date->get_report_data();
        $chart_legend = $wcfm_report_sales_by_date->get_chart_legend();        
        $main_chart = $wcfm_report_sales_by_date->get_main_chart(0);
*/


			$inc_dir     = plugin_dir_path( dirname( __FILE__ ) ) ;                  
			require_once $inc_dir. 'class-wcfmmp-reports.php';  

			$wcfm_report_sales_by_date = new WCFMMP_Report_Sales_By_Date( 'month' );
			$wcfm_report_sales_by_date->calculate_current_range( 'month' );        
			$main_chart = $wcfm_report_sales_by_date->get_main_chart(0);

			return new WP_REST_Response( $main_chart, 200 );
		}else if ($which_marketplace == 'dokan') {
			if ( function_exists( 'dokan_dashboard_sales_overview' ) ) {
				global $wp_locale;
				
				$start_date = date( 'Y-m-01', current_time('timestamp') );
				$end_date   = date( 'Y-m-d', strtotime( 'midnight', current_time( 'timestamp' ) ) );
				$group_by = 'day';

				$start_date_to_time = strtotime( $start_date );
				$end_date_to_time   = strtotime( $end_date );

				if ( $group_by == 'day' ) {
					$group_by_query       = 'YEAR(post_date), MONTH(post_date), DAY(post_date)';
					$chart_interval       = ceil( max( 0, ( $end_date_to_time - $start_date_to_time ) / ( 60 * 60 * 24 ) ) );
					$barwidth             = 60 * 60 * 24 * 1000;
				} else {
					$group_by_query = 'YEAR(post_date), MONTH(post_date)';
					$chart_interval = 0;
					$min_date             = $start_date_to_time;

					while ( ( $min_date   = strtotime( "+1 MONTH", $min_date ) ) <= $end_date_to_time ) {
						$chart_interval ++;
					}

					$barwidth             = 60 * 60 * 24 * 7 * 4 * 1000;
				}

				// Get orders and dates in range - we want the SUM of order totals, COUNT of order items, COUNT of orders, and the date
				$orders = dokan_get_order_report_data( array(
					'data' => array(
						'_order_total' => array(
							'type'     => 'meta',
							'function' => 'SUM',
							'name'     => 'total_sales'
						),
						'ID' => array(
							'type'     => 'post_data',
							'function' => 'COUNT',
							'name'     => 'total_orders',
							'distinct' => true,
						),
						'post_date' => array(
							'type'     => 'post_data',
							'function' => '',
							'name'     => 'post_date'
						),
					),
					'group_by'     => $group_by_query,
					'order_by'     => 'post_date ASC',
					'query_type'   => 'get_results',
					'filter_range' => true,
					'debug' => false
				), $start_date, $end_date );

				// Prepare data for report
				$order_counts      = dokan_prepare_chart_data( $orders, 'post_date', 'total_orders', $chart_interval, $start_date_to_time, $group_by );
				$order_amounts     = dokan_prepare_chart_data( $orders, 'post_date', 'total_sales', $chart_interval, $start_date_to_time, $group_by );

				// Encode in json format
				$chart_data = array(
					'order_counts'      => array_values( $order_counts ),
					'order_amounts'     => array_values( $order_amounts )
				);
				return $chart_data;
			}
		}


	} // END get_sales_report_by_date()

	public function get_sales_report_by_date_permissions_check() {

		if( apply_filters( 'wcfm_is_allow_reports', true ) )
			return true; 
		return false;

	}


}