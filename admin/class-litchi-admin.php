<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/liang-zhinian
 * @since      1.0.0
 *
 * @package    Litchi
 * @subpackage Litchi/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Litchi
 * @subpackage Litchi/admin
 * @author     Liang Zhinian <a13533550310@live.com>
 */
class Litchi_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$inc_dir = dirname( dirname( __FILE__ ) ) . '/includes/';
		require_once $inc_dir. 'log.php';
		Logger::Init( Logger::DefaultLogFileHandler(), 15);

		//Logger::DEBUG(" Litchi_Admin -> __construct: " . sprintf("%s/settings.php", dirname(__FILE__)));

		// Initialize Settings
		require_once(sprintf("%s/settings.php", dirname(__FILE__)));
		$WP_Plugin_Template_Settings = new WP_Plugin_Template_Settings();
		
		
		// Initialize Juhe API Settings
		require_once(sprintf("%s/settings.php", $inc_dir . 'juhe'));
		$WP_Plugin_Juhe_Template_Settings = new WP_Plugin_Juhe_Template_Settings();
		/*
			// Register custom post types
			require_once(sprintf("%s/post-types/post_type_template.php", dirname(__FILE__)));
			$Post_Type_Template = new Post_Type_Template();
*/

		//add_action( 'woocommerce_loaded', array( $this, 'init_hooks' ) );
		//add_filter( 'plugin_action_links_litchi', array( $this, 'plugin_action_links' ) );
	}

	public function init_hooks() {
		//Logger::DEBUG(" Litchi_Admin -> init_hooks: " . plugin_basename(__FILE__));
		add_filter( 'plugin_action_links_litchi/litchi.php', array( $this, 'plugin_action_links' ) );

	}

	// Add the settings link to the plugins page
	public function plugin_action_links($links)
	{
		/*
			$settings_link = '<a href="options-general.php?page=litchi">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
			*/

		// Build and escape the URL.
		$url = esc_url( add_query_arg(
			'page',
			'litchi-settings',
			get_admin_url() . 'admin.php'
		) );
		// Create the link.
		$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';
		// Adds the link to the end of the array.
		array_push(
			$links,
			$settings_link
		);
		return $links;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Litchi_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Litchi_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/litchi-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Litchi_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Litchi_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/litchi-admin.js', array( 'jquery' ), $this->version, false );

	}

}
