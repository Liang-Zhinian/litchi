<?php



/**
 * Get store page url of a seller
 *
 * @param int $user_id
 * @return string
 */
function litchi_get_store_url( $user_id ) {
    $userdata         = get_userdata( $user_id );
    $user_nicename    = ( ! false == $userdata ) ? $userdata->user_nicename : '';
    $custom_store_url = litchi_get_option( 'custom_store_url', 'litchi_general', 'store' );

    return sprintf( '%s/%s/', home_url( '/' . $custom_store_url ), $user_nicename );
}


/**
 * Get the value of a settings field
 *
 * @param string $option settings field name
 * @param string $section the section name this field belongs to
 * @param string $default default text if it's not found
 * @return mixed
 */
function litchi_get_option( $option, $section, $default = '' ) {

    $options = get_option( $section );

    if ( isset( $options[ $option ] ) ) {
        return $options[ $option ];
    }

    return $default;
}

/**
 * Get a vendor
 *
 * @since 1.0.0
 *
 * @param  integer $vendor_id
 *
 * @return \Litchi_Vendor
 */
function litchi_get_vendor( $vendor_id = null ) {

    if ( ! $vendor_id ) {
        $vendor_id = wp_get_current_user();
    }

    return new Litchi_Vendor( $vendor_id );
}

/**
 * Get all cap related to seller
 *
 * @since 1.0.0
 *
 * @return array
 */
function litchi_get_all_caps() {
    $capabilities = array(
        'overview' => array(
            'litchi_view_sales_overview'        => __( 'View sales overview', 'litchi' ),
            'litchi_view_sales_report_chart'    => __( 'View sales report chart', 'litchi' ),
            'litchi_view_announcement'          => __( 'View announcement', 'litchi' ),
            'litchi_view_order_report'          => __( 'View order report', 'litchi' ),
            'litchi_view_review_reports'        => __( 'View review report', 'litchi' ),
            'litchi_view_product_status_report' => __( 'View product status report', 'litchi' ),
        ),
        'report' => array(
            'litchi_view_overview_report'    => __( 'View overview report', 'litchi' ),
            'litchi_view_daily_sale_report'  => __( 'View daily sales report', 'litchi' ),
            'litchi_view_top_selling_report' => __( 'View top selling report', 'litchi' ),
            'litchi_view_top_earning_report' => __( 'View top earning report', 'litchi' ),
            'litchi_view_statement_report'   => __( 'View statement report', 'litchi' ),
        ),
        'order' => array(
            'litchi_view_order'        => __( 'View order', 'litchi' ),
            'litchi_manage_order'      => __( 'Manage order', 'litchi' ),
            'litchi_manage_order_note' => __( 'Manage order note', 'litchi' ),
            'litchi_manage_refund'     => __( 'Manage refund', 'litchi' ),
        ),

        'coupon' => array(
            'litchi_add_coupon'    => __( 'Add coupon', 'litchi' ),
            'litchi_edit_coupon'   => __( 'Edit coupon', 'litchi' ),
            'litchi_delete_coupon' => __( 'Delete coupon', 'litchi' ),
        ),
        'review' => array(
            'litchi_view_reviews'   => __( 'View reviews', 'litchi' ),
            'litchi_manage_reviews' => __( 'Manage reviews', 'litchi' ),
        ),

        'withdraw' => array(
            'litchi_manage_withdraw' => __( 'Manage withdraw', 'litchi' ),
        ),
        'product' => array(
            'litchi_add_product'       => __( 'Add product', 'litchi' ),
            'litchi_edit_product'      => __( 'Edit product', 'litchi' ),
            'litchi_delete_product'    => __( 'Delete product', 'litchi' ),
            'litchi_view_product'      => __( 'View product', 'litchi' ),
            'litchi_duplicate_product' => __( 'Duplicate product', 'litchi' ),
            'litchi_import_product'    => __( 'Import product', 'litchi' ),
            'litchi_export_product'    => __( 'Export product', 'litchi' ),
        ),
        'customer' => array(
            'litchi_add_user'       => __( 'Add user', 'litchi' ),
            'litchi_edit_user'      => __( 'Edit user', 'litchi' ),
            'litchi_delete_user'    => __( 'Delete user', 'litchi' ),
            'litchi_view_user'      => __( 'View user', 'litchi' ),
        ),
        'menu' => array(
            'litchi_view_overview_menu'       => __( 'View overview menu', 'litchi' ),
            'litchi_view_product_menu'        => __( 'View product menu', 'litchi' ),
            'litchi_view_order_menu'          => __( 'View order menu', 'litchi' ),
            'litchi_view_coupon_menu'         => __( 'View coupon menu', 'litchi' ),
            'litchi_view_report_menu'         => __( 'View report menu', 'litchi' ),
            'litchi_view_review_menu'         => __( 'Vuew review menu', 'litchi' ),
            'litchi_view_withdraw_menu'       => __( 'View withdraw menu', 'litchi' ),
            'litchi_view_store_settings_menu' => __( 'View store settings menu', 'litchi' ),
            'litchi_view_store_payment_menu'  => __( 'View payment settings menu', 'litchi' ),
            'litchi_view_store_shipping_menu' => __( 'View shipping settings menu', 'litchi' ),
            'litchi_view_store_social_menu'   => __( 'View social settings menu', 'litchi' ),
            'litchi_view_store_seo_menu'      => __( 'View seo settings menu', 'litchi' ),
        ),
    );

    return apply_filters( 'litchi_get_all_cap', $capabilities );
}

@ini_set( 'upload_max_size' , '64M' );
@ini_set( 'post_max_size', '64M');
@ini_set( 'max_execution_time', '300' );


if( !function_exists( 'which_marketplace' ) ) {
	function which_marketplace() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		
		// WCfM Multivendor Marketplace Check
		$is_marketplace = ( in_array( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php', $active_plugins ) || array_key_exists( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php', $active_plugins ) || class_exists( 'WCFMmp' ) ) ? 'wcfmmarketplace' : false;
		
		// WC Vendors Check
		if( !$is_marketplace )
		  $is_marketplace = ( in_array( 'wc-vendors/class-wc-vendors.php', $active_plugins ) || array_key_exists( 'wc-vendors/class-wc-vendors.php', $active_plugins ) || class_exists( 'WC_Vendors' ) ) ? 'wcvendors' : false;
		
		// WC Marketplace Check
		if( !$is_marketplace )
			$is_marketplace = ( in_array( 'dc-woocommerce-multi-vendor/dc_product_vendor.php', $active_plugins ) || array_key_exists( 'dc-woocommerce-multi-vendor/dc_product_vendor.php', $active_plugins ) || class_exists( 'WCMp' ) ) ? 'wcmarketplace' : false;
		
		// WC Product Vendors Check
		if( !$is_marketplace )
			$is_marketplace = ( in_array( 'woocommerce-product-vendors/woocommerce-product-vendors.php', $active_plugins ) || array_key_exists( 'woocommerce-product-vendors/woocommerce-product-vendors.php', $active_plugins ) ) ? 'wcpvendors' : false;
		
		// Dokan Lite Check
		if( !$is_marketplace )
			$is_marketplace = ( in_array( 'dokan-lite/dokan.php', $active_plugins ) || array_key_exists( 'dokan-lite/dokan.php', $active_plugins ) || class_exists( 'WeDevs_Dokan' ) ) ? 'dokan' : false;
		
		return $is_marketplace;
	}
}

/**
 * Register a book post type, with REST API support
 *
 * Based on example at: https://codex.wordpress.org/Function_Reference/register_post_type
 */
add_action( 'init', 'my_book_cpt' );
function my_book_cpt() {
    $args = array(
      'public'       => true,
      'show_in_rest' => true,
      'label'        => 'dokan_slider'
    );
    register_post_type( 'dokan_slider', $args );
}