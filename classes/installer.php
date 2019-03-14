<?php

/**
 * Litchi installer class
 *
 * @author weDevs
 */
class Litchi_Installer {

    function do_install() {

        // installs
        $this->user_roles();
        // $this->setup_pages();
        // $this->woocommerce_settings();
        // $this->create_tables();
        // $this->product_design();

        // // does it needs any update?
        // $updater = new Litchi_Upgrade();
        // $updater->perform_updates();

        // if ( class_exists( 'Litchi_Rewrites' ) ) {
        //     Litchi_Rewrites::init()->register_rule();
        // }

        // flush_rewrite_rules();

        // $was_installed_before = get_option( 'litchi_theme_version', false );

        // update_option( 'litchi_theme_version', DOKAN_PLUGIN_VERSION );

        // if ( ! $was_installed_before ) {
        //     set_transient( '_litchi_setup_page_redirect', true, 30 );
        // }
    }

    /**
     * Init litchi user roles
     *
     * @since Dokan 1.0
     *
     * @global WP_Roles $wp_roles
     */
    function user_roles() {
        global $wp_roles;

        if ( class_exists( 'WP_Roles' ) && ! isset( $wp_roles ) ) {
            $wp_roles = new WP_Roles();
        }

        add_role( 'seller', __( 'Vendor', 'litchi' ), array(
            'read'                      => true,
            'publish_posts'             => true,
            'edit_posts'                => true,
            'delete_published_posts'    => true,
            'edit_published_posts'      => true,
            'delete_posts'              => true,
            'manage_categories'         => true,
            'moderate_comments'         => true,
            'unfiltered_html'           => true,
            'upload_files'              => true,
            'edit_shop_orders'          => true,
            'edit_product'              => true,
            'read_product'              => true,
            'delete_product'            => true,
            'edit_products'             => true,
            'publish_products'          => true,
            'read_private_products'     => true,
            'delete_products'           => true,
            'delete_products'           => true,
            'delete_private_products'   => true,
            'delete_published_products' => true,
            'delete_published_products' => true,
            'edit_private_products'     => true,
            'edit_published_products'   => true,
            'manage_product_terms'      => true,
            'delete_product_terms'      => true,
            'assign_product_terms'      => true,
            'list_users'                => true,
            'edit_users'                => true,
            'delete_users'              => true,
            'create_users'              => true,
            'litchidar'                 => true,
        ) );

        $capabilities = array();
        $all_cap      = litchi_get_all_caps();

        foreach ( $all_cap as $key => $cap ) {
            $capabilities = array_merge( $capabilities, array_keys( $cap ) );
        }

        $wp_roles->add_cap( 'shop_manager', 'litchidar' );
        $wp_roles->add_cap( 'administrator', 'litchidar' );

        foreach ( $capabilities as $key => $capability ) {
            $wp_roles->add_cap( 'seller', $capability );
            $wp_roles->add_cap( 'administrator', $capability );
            $wp_roles->add_cap( 'shop_manager', $capability );
        }
    }

}
