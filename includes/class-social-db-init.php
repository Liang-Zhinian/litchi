<?php
global $wpdb;
class social_db_init {
	
	function init() {
		
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();		
		$table_name = $wpdb->prefix . 'wp_api_social';
		
		
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE $table_name (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				created_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				social_id varchar(255) NOT NULL,
				wp_user_id INT(10) NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$returnDB = dbDelta( $sql );
		}
	}
	
}
?>