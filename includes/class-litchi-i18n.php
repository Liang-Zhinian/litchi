<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://github.com/liang-zhinian
 * @since      1.0.0
 *
 * @package    Litchi
 * @subpackage Litchi/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Litchi
 * @subpackage Litchi/includes
 * @author     Liang Zhinian <a13533550310@live.com>
 */
class Litchi_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {
		$inc_dir = plugin_dir_path( dirname( __FILE__ ) ) . 'includes/';
		require_once $inc_dir. 'log.php';
		Logger::Init( Logger::DefaultLogFileHandler(), 15);
		
		//Logger::DEBUG(" Litchi_i18n -> load_plugin_textdomain: " . dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/');
		//Logger::DEBUG(" Litchi_i18n -> load_plugin_textdomain: " . dirname( plugin_basename(__FILE__) ) . '/languages/');

		load_plugin_textdomain(
			'litchi',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
