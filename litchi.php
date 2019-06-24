<?php
/*
Plugin Name: Litchi
Plugin URI: 
Description: A simple wordpress plugin template
Version: 1.0
Author: 
Author URI: 
Text Domain: litchi
WC requires at least: 3.0
WC tested up to: 3.5.5
Domain Path: /languages/
License: GPL2
*/



// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Autoload class files on demand
 *
 *
 * @since 1.0
 *
 * @param string  $class requested class name
 */
function litchi_autoload( $class ) {
    if ( stripos( $class, 'Litchi_' ) !== false ) {
        $class_name = str_replace( array( 'Litchi_', '_' ), array( '', '-' ), $class );
        $file_path = dirname( __FILE__ ) . '/classes/' . strtolower( $class_name ) . '.php';

        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        }
    }
}

spl_autoload_register( 'litchi_autoload' );


if(!class_exists('Litchi'))
{
	class Litchi
	{

		/**
		 * Plugin version
		 *
		 * @var string
		 */
		public $version = '2.9.7';
	
		/**
		 * Minimum PHP version required
		 *
		 * @var string
		 */
		private $min_php = '5.6.0';
	
		/**
		 * Holds various class instances
		 *
		 * @since 2.6.10
		 *
		 * @var array
		 */
		private $container = array();

		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			$this->define_constants();

			if ( ! $this->is_supported_php() ) {
				return;
			}

			register_activation_hook( __FILE__, array( $this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

			add_action( 'woocommerce_loaded', array( $this, 'init_plugin' ) );
		
			// Initialize Settings
			require_once(sprintf("%s/settings.php", dirname(__FILE__)));
			$WP_Plugin_Template_Settings = new WP_Plugin_Template_Settings();

			// Register custom post types
			require_once(sprintf("%s/post-types/post_type_template.php", dirname(__FILE__)));
			$Post_Type_Template = new Post_Type_Template();
			
		} // END public function __construct

		/**
		 * Initializes the Litchi() class
		 *
		 * Checks for an existing Litchi() instance
		 * and if it doesn't find one, creates it.
		 */
		public static function init() {
			static $instance = false;

			if ( ! $instance ) {
				$instance = new Litchi();
			}

			return $instance;
		}

		/**
		 * Magic getter to bypass referencing objects
		 *
		 * @since 2.6.10
		 *
		 * @param $prop
		 *
		 * @return mixed
		 */
		public function __get( $prop ) {
			if ( array_key_exists( $prop, $this->container ) ) {
				return $this->container[ $prop ];
			}
	
			return $this->{$prop};
		}

		/**
		 * Check if the PHP version is supported
		 *
		 * @return bool
		 */
		public function is_supported_php() {
			if ( version_compare( PHP_VERSION, $this->min_php, '<=' ) ) {
				return false;
			}
	
			return true;
		}

		/**
		 * Get the plugin path.
		 *
		 * @return string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}
	
		/**
		 * Get the template path.
		 *
		 * @return string
		 */
		public function template_path() {
			return apply_filters( 'litchi_template_path', 'litchi/' );
		}

		/**
		 * Activate the plugin
		 */
		public function activate()
		{
			// Do nothing
			// if ( ! function_exists( 'WC' ) ) {
			// 	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			// 	deactivate_plugins( plugin_basename( __FILE__ ) );

			// 	wp_die( '<div class="error"><p>' . sprintf( esc_html__( '<b>Dokan</b> requires <a href="%s">WooCommerce</a> to be installed & activated!', 'dokan-lite' ), '<a target="_blank" href="https://wordpress.org/plugins/woocommerce/">', '</a>' ) . '</p></div>' );
			// }

			// if ( ! $this->is_supported_php() ) {
			// 	require_once WC_ABSPATH . 'includes/wc-notice-functions.php';

			// 	wc_print_notice( sprintf( __( 'The Minimum PHP Version Requirement for <b>Dokan</b> is %s. You are Running PHP %s', 'dokan' ), $this->min_php, phpversion(), 'error' ) );
			// 	exit;
			// }

			require_once dirname( __FILE__ ) . '/includes/functions.php';
			// require_once dirname( __FILE__ ) . '/includes/functions-compatibility.php';

			// Background Processes
			// require_once dirname( __FILE__ ) . '/includes/background-processes/class-dokan-background-processes.php';
			// require_once dirname( __FILE__ ) . '/includes/background-processes/abstract-class-dokan-background-processes.php';

			//$installer = new Litchi_Installer();
			//$installer->do_install();
		} // END public static function activate

		/**
		 * Deactivate the plugin
		 */
		public function deactivate()
		{
			// Do nothing
		} // END public static function deactivate

		/**
		 * Initialize plugin for localization
		 *
		 * @uses load_plugin_textdomain()
		 */
		public function localization_setup() {
			load_plugin_textdomain( 'litchi', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}


		/**
		 * Define all constants
		 *
		 * @return void
		 */
		public function define_constants() {
			define( 'LITCHI_PLUGIN_VERSION', $this->version );
			define( 'LITCHI_FILE', __FILE__ );
			define( 'LITCHI_DIR', dirname( __FILE__ ) );
			define( 'LITCHI_INC_DIR', dirname( __FILE__ ) . '/includes' );
			define( 'LITCHI_LIB_DIR', dirname( __FILE__ ) . '/lib' );
			define( 'LITCHI_PLUGIN_ASSEST', plugins_url( 'assets', __FILE__ ) );
		}

		/**
		 * Load the plugin after WP User Frontend is loaded
		 *
		 * @return void
		 */
		public function init_plugin() {
			$this->includes();

			$this->init_hooks();

			do_action( 'litchi_loaded' );
		}

		/**
		 * Initialize the actions
		 *
		 * @return void
		 */
		function init_hooks() {

			// Localize our plugin
			add_action( 'init', array( $this, 'localization_setup' ) );

			// initialize the classes
			add_action( 'init', array( $this, 'init_classes' ),5 );
			// add_action( 'init', array( $this, 'wpdb_table_shortcuts' ) );

			add_action( 'plugins_loaded', array( $this, 'after_plugins_loaded' ) );

			add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_action_links' ) );
			
			// add_action( 'in_plugin_update_message-dokan-lite/dokan.php', array( 'Dokan_Installer', 'in_plugin_update_message' ) );

			// add_action( 'widgets_init', array( $this, 'register_widgets' ) );
		}

		/**
		 * Include all the required files
		 *
		 * @return void
		 */
		function includes() {
			$lib_dir     = dirname( __FILE__ ) . '/lib/';
			$inc_dir     = dirname( __FILE__ ) . '/includes/';
			$classes_dir = dirname( __FILE__ ) . '/classes/';

			require_once $inc_dir . 'functions.php';
			require_once $inc_dir . 'wc-functions.php';

			require_once $inc_dir . 'class-vendor.php';
			require_once $inc_dir . 'class-vendor-manager.php';

			// API includes
			//require_once $inc_dir . 'api/class-api-rest-controller.php';
			require_once $inc_dir . 'class-api-manager.php';

			//require_once $inc_dir . 'basic-auth.php';
			
			require_once $inc_dir . 'class-wechat.php';

		}

		/**
		 * Init all the classes
		 *
		 * @return void
		 */
		function init_classes() {
			$this->container['vendor']        = new Litchi_Vendor_Manager();
			// $this->container['product']       = new Dokan_Product_Manager();
			$this->container['api']           = new Litchi_API_Manager();
		}

		/**
		 * Executed after all plugins are loaded
		 *
		 * At this point Dokan Pro is loaded
		 *
		 * @since 2.8.7
		 *
		 * @return void
		 */
		public function after_plugins_loaded() {
			// new Dokan_Background_Processes();
		}

		// Add the settings link to the plugins page
		function plugin_action_links($links)
		{
			$settings_link = '<a href="options-general.php?page=litchi">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}


	} // END class Litchi
} // END if(!class_exists('Litchi'))

if(class_exists('Litchi'))
{
	/**
	 * Load Litchi Plugin when all plugins loaded
	 *
	 * @return void
	 */
	function litchi() {
		return Litchi::init();
	}

	// Lets Go....
	litchi();
}
