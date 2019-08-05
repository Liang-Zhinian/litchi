<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/liang-zhinian
 * @since      1.0.0
 *
 * @package    Litchi
 * @subpackage Litchi/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Litchi
 * @subpackage Litchi/includes
 * @author     Liang Zhinian <a13533550310@live.com>
 */
class Litchi_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once $inc_dir . 'class-social-db-init.php';
		$social_db = new social_db_init();
		$social_db -> init();

	}

}
