<?php
/**
 * Report class responsible for handling sales by date reports.
 *
 * @since      2.1.0
 *
 * @package    WooCommerce Frontend Manager
 * @subpackage wcfm/includes/reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

include_once( WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php' );

class WCFMMP_Report_Sales_By_Date extends WC_Admin_Report {
	public $chart_colors = array();
	public $current_range;
	private $report_data;

	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.1.0
	 * @version 2.1.0
	 * @return bool
	 */
	public function __construct( $current_range = '' ) {
		global $WCFM;
		
		if( !$current_range ) {
			$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
	
			if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) ) {
				$current_range = '7day';
			}
		}

		$this->current_range = $current_range;
	}

	/**
	 * Get the report data
	 *
	 * @access public
	 * @since 2.1.0
	 * @version 2.1.0
	 * @return array of objects
	 */
	public function get_report_data() {
		global $WCFM;
		if ( empty( $this->report_data ) ) {
			$this->query_report_data();
		}

		return $this->report_data;
	}

	/**
	 * Get the report based on parameters
	 *
	 * @access public
	 * @since 2.1.0
	 * @version 2.1.0
	 * @return array of objects
	 */
	public function query_report_data() {
		global $wpdb, $WCFM, $WCFMmp;

		$this->report_data = new stdClass;
		
		$vendor_id = $WCFMmp->vendor_id; //apply_filters( 'wcfm_current_vendor_id', get_current_user_id() );

		$sql = "SELECT * FROM {$wpdb->prefix}wcfm_marketplace_orders AS commission";

		$sql .= " WHERE 1=1";
		$sql .= " AND commission.vendor_id = %d";
		//$status = get_wcfm_marketplace_active_withdrwal_order_status_in_comma();
		//$sql .= " AND commission.order_status IN ({$status})";
		$sql .= apply_filters( 'wcfm_order_status_condition', '', 'commission' );
		$sql .= " AND commission.is_trashed != 1";
		$sql = wcfm_query_time_range_filter( $sql, 'created', $this->current_range );

		// Enable big selects for reports
		$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );

		$results = $wpdb->get_results( $wpdb->prepare( $sql, $vendor_id ) );

		$total_shipping_amount          = 0.00;
		$total_tax_amount               = 0.00;
		$total_earned_commission_amount = 0.00;
		$total_commission_amount        = 0.00;
		$total_refund_amount            = 0.00;
		$gross_sales_amount             = 0.00;
		$total_items                    = 0;

		$total_orders = array();

		foreach( $results as $data ) {

			$total_orders[] = $data->order_id;
			
			if( $data->item_id ) {
				try {
					if( apply_filters( 'wcfmmmp_gross_sales_respect_setting', true ) ) {
						$gross_sales_amount += (float) $WCFMmp->wcfmmp_commission->wcfmmp_get_commission_meta(  $data->ID, 'gross_total' );
					} else {
						$gross_sales_amount += (float) $WCFMmp->wcfmmp_commission->wcfmmp_get_commission_meta(  $data->ID, 'gross_sales_total' );
					}
					
					/*if( $WCFMmp->wcfmmp_vendor->is_vendor_deduct_discount( $vendor_id, $data->order_id ) ) {
						$gross_sales_amount += (float) $data->item_total;
					} else {
						$gross_sales_amount += (float) $data->item_sub_total;
					}
					if($is_vendor_get_tax = $WCFMmp->wcfmmp_vendor->is_vendor_get_tax( $vendor_id )) {
						$gross_sales_amount += (float) $data->tax;
					}
					if($WCFMmp->wcfmmp_vendor->is_vendor_get_shipping( $vendor_id )) {
						$gross_sales_amount += (float) apply_filters( 'wcfmmmp_gross_sales_shipping_cost', $data->shipping, $vendor_id );
						if($is_vendor_get_tax) {
							$gross_sales_amount += (float) $data->shipping_tax_amount;
						}
					}*/
					
					// Deduct Refunded Amount
					$gross_sales_amount -= (float) sanitize_text_field( $data->refunded_amount );
				} catch (Exception $e) {
					continue;
				}
			}
			
			$total_tax_amount               += (float) sanitize_text_field( $data->tax ) + (float) sanitize_text_field( $data->shipping_tax_amount );
			$total_shipping_amount          += (float) sanitize_text_field( apply_filters( 'wcfmmmp_gross_sales_shipping_cost', $data->shipping, $vendor_id ) );
			if( !$data->is_refunded ) {
				$total_earned_commission_amount += (float) sanitize_text_field( $data->total_commission );
			}
			$total_items                    += (int)   sanitize_text_field( $data->quantity );
			$total_refund_amount            += (float) sanitize_text_field( $data->refunded_amount );
			
			// show only paid commissions
			if( !$data->is_refunded ) {
				if ( ('paid' === $data->withdraw_status) || ('completed' === $data->withdraw_status) ) {
					$total_commission_amount   += (float) sanitize_text_field( $data->total_commission );
				}
			}
		}

		$total_orders = count( array_unique( $total_orders ) );
		$total_sales = $total_earned_commission_amount;
		
		$this->report_data->average_sales         = wc_format_decimal( $total_sales / ( $this->chart_interval + 1 ), 2 );
		$this->report_data->total_orders          = $total_orders;
		$this->report_data->total_items           = $total_items;
		$this->report_data->total_shipping        = wc_format_decimal( $total_shipping_amount );
		$this->report_data->total_earned          = wc_format_decimal( $total_sales );
		$this->report_data->total_commission      = wc_format_decimal( $total_commission_amount );
		$this->report_data->gross_sales           = wc_format_decimal( $gross_sales_amount );
		$this->report_data->total_tax             = wc_format_decimal( $total_tax_amount );
		$this->report_data->total_refund          = wc_format_decimal( $total_refund_amount );
		
		// Admin Fee Mode Commission
		$admin_fee_mode = apply_filters( 'wcfm_is_admin_fee_mode', false );
		if( $admin_fee_mode ) {
		  $this->report_data->total_earned = $gross_sales_amount - $total_sales;
		  $net_paid_sales_amount = $WCFM->wcfm_vendor_support->wcfm_get_gross_sales_by_vendor( $vendor_id, $this->current_range, true );
		  if( $net_paid_sales_amount && ( $net_paid_sales_amount > $total_commission_amount ) ) {
		  	$this->report_data->total_commission = $net_paid_sales_amount - $total_commission_amount;
		  } else {
		  	$this->report_data->total_commission = 0;
		  }
		}
	}

	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		global $WCFM, $WCFMmp;
		$legend = array();
		$data   = $this->get_report_data();
		
		$vendor_id = $WCFMmp->vendor_id; //apply_filters( 'wcfm_current_vendor_id', get_current_user_id() );

		switch ( $this->chart_groupby ) {
			case 'day' :
				$average_sales_title = sprintf( __( '%s average daily sales', 'wc-frontend-manager' ), '<strong>' . wc_price( $data->average_sales ) . '</strong>' );
			break;
			case 'month' :
			default :
				$average_sales_title = sprintf( __( '%s average monthly sales', 'wc-frontend-manager' ), '<strong>' . wc_price( $data->average_sales ) . '</strong>' );
			break;
		}
		
		if( apply_filters( 'wcfm_sales_report_is_allow_gross_sales', true ) ) {
			$legend[] = array(
				'title'            => sprintf( __( '%s gross sales in this period', 'wc-frontend-manager' ), '<strong>' . wc_price( $data->gross_sales ) . '</strong>' ),
				'placeholder'      => __( 'This is the sum of the order totals after any refunds and including shipping and taxes.', 'wc-frontend-manager' ),
				'color'            => $this->chart_colors['gross_sales_amount'],
				'highlight_series' => 3
			);
		}
		
		// Admin Fee Mode Commission
		$admin_fee_mode = apply_filters( 'wcfm_is_admin_fee_mode', false );
		
		if( $admin_fee_mode ) {
			if( apply_filters( 'wcfm_sales_report_is_allow_earning', true ) ) {
				$legend[] = array(
					'title'            => sprintf( __( '%s total admin fees', 'wc-frontend-manager' ), '<strong>' . wc_price( $data->total_earned ) . '</strong>' ),
					'placeholder'      => __( 'This is the sum of the admin fees including shipping and taxes if applicable.', 'wc-frontend-manager' ),
					'color'            => $this->chart_colors['earned'],
					'highlight_series' => 3
				);
			}
			
			if( apply_filters( 'wcfm_sales_report_is_allow_withdrawal', true ) ) {
				$legend[] = array(
					'title'            => sprintf( __( '%s total paid fees', 'wc-frontend-manager' ), '<strong>' . wc_price( $data->total_commission ) . '</strong>' ),
					'placeholder'      => __( 'This is the sum of the admin fees paid including shipping and taxes if applicable.', 'wc-frontend-manager' ),
					'color'            => $this->chart_colors['commission'],
					'highlight_series' => 4
				);
			}
		} else {
			if( apply_filters( 'wcfm_sales_report_is_allow_earning', true ) ) {
				$legend[] = array(
					'title'            => sprintf( __( '%s total earnings', 'wc-frontend-manager' ), '<strong>' . wc_price( $data->total_earned ) . '</strong>' ),
					'placeholder'      => __( 'This is the sum of the earned commission including shipping and taxes if applicable.', 'wc-frontend-manager' ),
					'color'            => $this->chart_colors['earned'],
					'highlight_series' => 3
				);
			}
			
			if( apply_filters( 'wcfm_sales_report_is_allow_withdrawal', true ) ) {
				$legend[] = array(
					'title'            => sprintf( __( '%s total withdrawal', 'wc-frontend-manager' ), '<strong>' . wc_price( $data->total_commission ) . '</strong>' ),
					'placeholder'      => __( 'This is the sum of the commission paid including shipping and taxes if applicable.', 'wc-frontend-manager' ),
					'color'            => $this->chart_colors['commission'],
					'highlight_series' => 4
				);
			}
		}
		
		if( apply_filters( 'wcfm_sales_report_is_allow_refund', true ) ) {
				$legend[] = array(
					'title'            => sprintf( __( '%s total refund', 'wc-frontend-manager' ), '<strong>' . wc_price( $data->total_refund ) . '</strong>' ),
					'placeholder'      => __( 'This is the sum of the refunds and partial refunds.', 'wc-frontend-manager' ),
					'color'            => $this->chart_colors['refund'],
					'highlight_series' => 4
				);
			}

		if ( $data->average_sales > 0 ) {
			$legend[] = array(
				'title'            => $average_sales_title,
				'color'            => $this->chart_colors['average'],
				'highlight_series' => 2
			);
		}

		$legend[] = array(
			'title'            => sprintf( __( '%s orders placed', 'wc-frontend-manager' ), '<strong>' . $data->total_orders . '</strong>' ),
			'color'            => $this->chart_colors['order_count'],
			'highlight_series' => 0
		);
		
		$legend[] = array(
			'title'            => sprintf( __( '%s items purchased', 'wc-frontend-manager' ), '<strong>' . $data->total_items . '</strong>' ),
			'color'            => $this->chart_colors['item_count'],
			'highlight_series' => 1
		);
		
		if( $WCFMmp->wcfmmp_vendor->is_vendor_get_tax( $vendor_id ) && apply_filters( 'wcfm_sales_report_is_allow_tax', true ) ) {
			$legend[] = array(
				'title'            => sprintf( __( '%s total tax', 'wc-frontend-manager' ), '<strong>' . wc_price( $data->total_tax ) . '</strong>' ),
				'color'            => $this->chart_colors['tax_amount'],
				'highlight_series' => 1
			);
		}

		if( $WCFMmp->wcfmmp_vendor->is_vendor_get_shipping( $vendor_id ) && apply_filters( 'wcfm_sales_report_is_allow_shipping', true ) ) {
			$legend[] = array(
				'title'            => sprintf( __( '%s charged for shipping', 'wc-frontend-manager' ), '<strong>' . wc_price( $data->total_shipping ) . '</strong>' ),
				'color'            => $this->chart_colors['shipping_amount'],
				'highlight_series' => 1
			);
		}
		
		return apply_filters( 'wcfm_wcmarketplace_sales_report_legends', $legend );
	}

	/**
	 * Output the report
	 */
	public function output_report() {
		global $WCFM, $WCFMmp;
		$ranges = array(
			'year'         => __( 'Year', 'wc-frontend-manager' ),
			'last_month'   => __( 'Last Month', 'wc-frontend-manager' ),
			'month'        => __( 'This Month', 'wc-frontend-manager' ),
			'7day'         => __( 'Last 7 Days', 'wc-frontend-manager' ),
		);

		$this->chart_colors = array(
			'average'             => '#95a5a6',
			'order_count'         => '#dbe1e3',
			'item_count'          => '#ecf0f1',
			'shipping_amount'     => '#6f42c1',
			'tax_amount'          => '#73818f',
			'earned'              => '#4dbd74',
			'commission'          => '#b1d4ea',
			'gross_sales_amount'  => '#3498db',
			'refund'              => '#e83e8c',
		);

		$current_range = $this->current_range;

		$this->calculate_current_range( $this->current_range );

		include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php' );
    }
    

	/**
	 * Get the main chart
	 *
	 * @return string
	 */
	public function get_main_chart( $show_legend = 1 ) {
		global $wp_locale, $wpdb, $WCFM, $WCFMmp;
		
		// Admin Fee Mode Commission
		$admin_fee_mode = apply_filters( 'wcfm_is_admin_fee_mode', false );
		//$wcfm_commission_options = get_option( 'wcfm_commission_options', array() );
		//$vendor_commission_for = isset( $wcfm_commission_options['commission_for'] ) ? $wcfm_commission_options['commission_for'] : 'vendor';
		//if( $vendor_commission_for == 'admin' ) $is_admin_fee = true;
		
		
		$vendor_id = $WCFMmp->vendor_id; //apply_filters( 'wcfm_current_vendor_id', get_current_user_id() );
		
		$select = "SELECT GROUP_CONCAT(ID) commission_ids, GROUP_CONCAT(item_id) order_item_ids, COUNT( DISTINCT commission.order_id ) AS count, SUM( commission.quantity ) AS order_item_count, COALESCE( SUM( commission.item_total ), 0 ) AS total_item_total, COALESCE( SUM( commission.item_sub_total ), 0 ) AS total_item_sub_total, COALESCE( SUM( commission.shipping ), 0 ) AS total_shipping, COALESCE( SUM( commission.tax ), 0 ) AS total_tax, COALESCE( SUM( commission.shipping_tax_amount ), 0 ) AS total_shipping_tax_amount, COALESCE( SUM( commission.total_commission ), 0 ) AS total_commission, COALESCE( SUM( commission.refunded_amount ), 0 ) AS total_refund, commission.created AS time";

		$sql = $select;
		$sql .= " FROM {$wpdb->prefix}wcfm_marketplace_orders AS commission";
		$sql .= " WHERE 1=1";
		$sql .= " AND commission.vendor_id = %d";
		//$status = get_wcfm_marketplace_active_withdrwal_order_status_in_comma();
		//$sql .= " AND commission.order_status IN ({$status})";
		$sql .= apply_filters( 'wcfm_order_status_condition', '', 'commission' );
		$sql .= " AND commission.is_trashed != 1";
		$sql = wcfm_query_time_range_filter( $sql, 'created', $this->current_range );

		$sql .= " GROUP BY DATE( commission.created )";
			
		// Enable big selects for reports
		$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
		
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $vendor_id ) );
		
		// Prepare net sales data
		if( !empty( $results ) ) {
			foreach( $results as $result ) {
				$gross_sales = 0.00;
				$commission_ids = explode( ",", $result->commission_ids );
				foreach( $commission_ids as $commission_id ) {
					if( apply_filters( 'wcfmmmp_gross_sales_respect_setting', true ) ) {
						$gross_sales += (float) $WCFMmp->wcfmmp_commission->wcfmmp_get_commission_meta( $commission_id, 'gross_total' );
					} else {
						$gross_sales += (float) $WCFMmp->wcfmmp_commission->wcfmmp_get_commission_meta( $commission_id, 'gross_sales_total' );
					}
				}
				/*if( $WCFMmp->wcfmmp_vendor->is_vendor_deduct_discount( $vendor_id ) ) {
					$gross_sales = (float) $result->total_item_total;
				} else {
					$gross_sales = (float) $result->total_item_sub_total;
				}
				if($is_vendor_get_tax = $WCFMmp->wcfmmp_vendor->is_vendor_get_tax( $vendor_id )) {
					$gross_sales += (float) $result->total_tax;
				}
				if($WCFMmp->wcfmmp_vendor->is_vendor_get_shipping( $vendor_id )) {
					$gross_sales += (float) apply_filters( 'wcfmmmp_gross_sales_shipping_cost', $result->total_shipping, $vendor_id );
					if($is_vendor_get_tax) {
						$gross_sales += (float) $result->total_shipping_tax_amount;
					}
				}*/
				
				// Deduct Refunded Amount
				$gross_sales -= (float) $result->total_refund;
				$result->gross_sales = $gross_sales;
			}
		}

		// Prepare data for report
		$order_counts         = $this->prepare_chart_data( $results, 'time', 'count', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$order_item_counts    = $this->prepare_chart_data( $results, 'time', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$shipping_amounts     = $this->prepare_chart_data( $results, 'time', 'total_shipping', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$tax_amounts          = $this->prepare_chart_data( $results, 'time', 'total_tax', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$shipping_tax_amounts = $this->prepare_chart_data( $results, 'time', 'total_shipping_tax_amount', $this->chart_interval, $this->start_date, $this->chart_groupby );

		$total_commission     = $this->prepare_chart_data( $results, 'time', 'total_commission', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$total_gross_sales    = $this->prepare_chart_data( $results, 'time', 'gross_sales', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$total_refund         = $this->prepare_chart_data( $results, 'time', 'total_refund', $this->chart_interval, $this->start_date, $this->chart_groupby );

		$total_earned_commission = array();
		if( $admin_fee_mode ) {
			foreach ( $total_commission as $order_amount_key => $order_amount_value ) {
				$total_earned_commission[ $order_amount_key ] = $order_amount_value;
				if( $admin_fee_mode && isset ( $total_gross_sales[ $order_amount_key ] ) && isset ( $total_gross_sales[ $order_amount_key ][1] ) ) {
					$total_earned_commission[ $order_amount_key ][1] = $total_gross_sales[ $order_amount_key ][1] - $total_earned_commission[ $order_amount_key ][1];
				}
			}
		} else {
			$total_earned_commission = $total_commission;
		}
		
		// Total Paid Commission
		$select = "SELECT GROUP_CONCAT(ID) commission_ids, GROUP_CONCAT(item_id) order_item_ids, COUNT( DISTINCT commission.order_id ) AS count, SUM( commission.quantity ) AS order_item_count, COALESCE( SUM( commission.item_total ), 0 ) AS total_item_total, COALESCE( SUM( commission.item_sub_total ), 0 ) AS total_item_sub_total, COALESCE( SUM( commission.shipping ), 0 ) AS total_shipping, COALESCE( SUM( commission.tax ), 0 ) AS total_tax, COALESCE( SUM( commission.shipping_tax_amount ), 0 ) AS total_shipping_tax_amount, COALESCE( SUM( commission.total_commission ), 0 ) AS total_commission, COALESCE( SUM( commission.refunded_amount ), 0 ) AS total_refund, commission.commission_paid_date AS time";

		$sql = $select;
		$sql .= " FROM {$wpdb->prefix}wcfm_marketplace_orders AS commission";
		$sql .= " WHERE 1=1";
		$sql .= " AND commission.vendor_id = %d";
		//$status = get_wcfm_marketplace_active_withdrwal_order_status_in_comma();
		//$sql .= " AND commission.order_status IN ({$status})";
		$sql .= apply_filters( 'wcfm_order_status_condition', '', 'commission' );
		$sql .= " AND commission.is_trashed != 1";
		$sql .= " AND ( commission.withdraw_status = 'paid' OR commission.withdraw_status = 'completed' )";
		$sql  = wcfm_query_time_range_filter( $sql, 'commission_paid_date', $this->current_range );
		
		$sql .= " GROUP BY DATE( commission.commission_paid_date )";
		
		// Enable big selects for reports
		$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
		
		$results = $wpdb->get_results( $wpdb->prepare( $sql, $vendor_id ) );
		
		// Prepare paid net sales data
		if( !empty( $results ) ) {
			foreach( $results as $result ) {
				$paid_gross_sales = 0.00;
				$commission_ids = explode( ",", $result->commission_ids );
				foreach( $commission_ids as $commission_id ) {
					if( apply_filters( 'wcfmmmp_gross_sales_respect_setting', true ) ) {
						$paid_gross_sales += (float) $WCFMmp->wcfmmp_commission->wcfmmp_get_commission_meta( $commission_id, 'gross_total' );
					} else {
						$paid_gross_sales += (float) $WCFMmp->wcfmmp_commission->wcfmmp_get_commission_meta( $commission_id, 'gross_sales_total' );
					}
				}
				
				/*if( $WCFMmp->wcfmmp_vendor->is_vendor_deduct_discount( $vendor_id ) ) {
					$paid_gross_sales = (float) $result->total_item_total;
				} else {
					$paid_gross_sales = (float) $result->total_item_sub_total;
				}
				if($is_vendor_get_tax = $WCFMmp->wcfmmp_vendor->is_vendor_get_tax( $vendor_id )) {
					$paid_gross_sales += (float) $result->total_tax;
				}
				if($WCFMmp->wcfmmp_vendor->is_vendor_get_shipping( $vendor_id )) {
					$paid_gross_sales += (float) apply_filters( 'wcfmmmp_gross_sales_shipping_cost', $result->total_shipping, $vendor_id );
					if($is_vendor_get_tax) {
						$paid_gross_sales += (float) $result->total_shipping_tax_amount;
					}
				}*/
				
				$paid_gross_sales -= (float) $result->total_refund;
				$result->paid_gross_sales = $paid_gross_sales;
			}
		}
		
		$paid_gross_sales = $this->prepare_chart_data( $results, 'time', 'paid_gross_sales', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$total_commission = $this->prepare_chart_data( $results, 'time', 'total_commission', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$total_paid_commission = array();
		if( $admin_fee_mode ) {
			foreach ( $total_commission as $order_amount_key => $order_amount_value ) {
				$total_paid_commission[ $order_amount_key ] = $order_amount_value;
				if( isset ( $paid_gross_sales[ $order_amount_key ] ) && isset ( $paid_gross_sales[ $order_amount_key ][1] ) ) {
					$total_paid_commission[ $order_amount_key ][1] = $paid_gross_sales[ $order_amount_key ][1] - $total_paid_commission[ $order_amount_key ][1];
				}
			}
		} else {
			$total_paid_commission = $total_commission;
		}
		//$total_paid_commission = $total_commission;
		
		// Encode in json format
		
		$chart_data = '{'
			. '  "order_counts"             : ' . $WCFM->wcfm_prepare_chart_data( $order_counts )
			. ', "order_item_counts"        : ' . $WCFM->wcfm_prepare_chart_data( $order_item_counts )
			. ', "tax_amounts"              : ' . $WCFM->wcfm_prepare_chart_data( $tax_amounts )
			. ', "shipping_amounts"         : ' . $WCFM->wcfm_prepare_chart_data( $shipping_amounts )
			. ', "total_earned_commission"  : ' . $WCFM->wcfm_prepare_chart_data( $total_earned_commission )
			. ', "total_paid_commission"    : ' . $WCFM->wcfm_prepare_chart_data( $total_paid_commission )
			. ', "total_gross_sales"        : ' . $WCFM->wcfm_prepare_chart_data( $total_gross_sales )
			. ', "total_refund"             : ' . $WCFM->wcfm_prepare_chart_data( $total_refund )
		  . '}';
		
		/*
		$chart_data["order_counts"] = $WCFM->wcfm_prepare_chart_data( $order_counts );
		$chart_data["order_item_counts"] = $WCFM->wcfm_prepare_chart_data( $order_item_counts );
		$chart_data["tax_amounts"] = $WCFM->wcfm_prepare_chart_data( $tax_amounts );
		$chart_data["shipping_amounts"] = $WCFM->wcfm_prepare_chart_data( $shipping_amounts );
		$chart_data["total_earned_commission"] = $WCFM->wcfm_prepare_chart_data( $total_earned_commission );
		$chart_data["total_paid_commission"] = $WCFM->wcfm_prepare_chart_data( $total_paid_commission );
		$chart_data["total_gross_sales"] = $WCFM->wcfm_prepare_chart_data( $total_gross_sales );
		$chart_data["total_refund"] = $WCFM->wcfm_prepare_chart_data( $total_refund );
		*/
		
		  
		return json_decode($chart_data);
    }
}