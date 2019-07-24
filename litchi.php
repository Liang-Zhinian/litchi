<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/liang-zhinian
 * @since             1.0.0
 * @package           Litchi
 *
 * @wordpress-plugin
 * Plugin Name:       Litchi
 * Plugin URI:        https://github.com/liang-zhinian/litchi.git
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Liang Zhinian
 * Author URI:        https://github.com/liang-zhinian
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       litchi
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LITCHI_VERSION', '1.0.0' );
define( 'PLUGIN_NAME', 'litchi' );

$inc_dir = plugin_dir_path( __FILE__ ) . 'includes/';
require_once $inc_dir. 'log.php';
Logger::Init( Logger::DefaultLogFileHandler(), 15);

//add_action( 'woocommerce_loaded', 'init_hooks' );

function init_hooks() {
	Logger::DEBUG(" Litchi -> init_hooks: " . 'plugin_action_links_' . plugin_basename(__FILE__));
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'plugin_action_links' );
}

// Add the settings link to the plugins page
function plugin_action_links( $links_array ){
	array_unshift( $links_array, '<a href="#">Settings</a>' );
	return $links_array;
}

if(get_option('wp_plugin_template-litchi_setting_enable_wx_payment')) {

add_action( 'init', 'wechat_wc_payment_gateway_init' );

if(!function_exists('wechat_wc_payment_gateway_init')){
    function wechat_wc_payment_gateway_init() {
        if( !class_exists('WC_Payment_Gateway') )  return;
        require_once plugin_dir_path( __FILE__ ) .'class-wechat-payment-gateway.php';
        $api = new Litchi_WeChat_Payment_Gateway();

        $api->check_wechatpay_response();

        add_filter('woocommerce_payment_gateways',array($api,'woocommerce_wechatpay_add_gateway' ),10,1);
    }
}
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-litchi-activator.php
 */
function activate_litchi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-litchi-activator.php';
	Litchi_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-litchi-deactivator.php
 */
function deactivate_litchi() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-litchi-deactivator.php';
	Litchi_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_litchi' );
register_deactivation_hook( __FILE__, 'deactivate_litchi' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-litchi.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_litchi() {

	$plugin = new Litchi();
	$plugin->run();

}
run_litchi();
