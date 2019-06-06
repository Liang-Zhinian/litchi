<?php

/**
* Cart API Controller
*
* @package litchi
*
* @author 
*/

class Litchi_REST_Wcfmmp_Reports_Controller extends WP_REST_Controller {

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
        
        global $WCFMmp, $WCFM, $wpdb, $LITCHI_INC_DIR;
		/*
        include_once( $WCFM->plugin_path . 'includes/reports/class-wcfmmarketplace-report-sales-by-date.php' );
        $wcfm_report_sales_by_date = new WCFM_Marketplace_Report_Sales_By_Date( 'month' );
        $wcfm_report_sales_by_date->calculate_current_range( 'month' );
        
        $report_data   = $wcfm_report_sales_by_date->get_report_data();
        $chart_legend = $wcfm_report_sales_by_date->get_chart_legend();        
        $main_chart = $wcfm_report_sales_by_date->get_main_chart(0);
*/
        
        include_once( $LITCHI_INC_DIR . '/reports/class-wcfmmp-reports.php' );
        $wcfm_report_sales_by_date = new WCFMMP_Report_Sales_By_Date( 'month' );
        $wcfm_report_sales_by_date->calculate_current_range( 'month' );        
        $main_chart = $wcfm_report_sales_by_date->get_main_chart(0);
        
        return new WP_REST_Response( $main_chart, 200 );
        
    } // END get_sales_report_by_date()

    public function get_sales_report_by_date_permissions_check() {

        if( apply_filters( 'wcfm_is_allow_reports', true ) )
        return true; 
        return false;

    }

    
}