<?php
global $wpdb;
class information_db_init {
	
	public function init() {
		
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();		
		$table_name = $wpdb->prefix . 'wp_api_information';
		
		
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE $table_name (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				created_time datetime DEFAULT '0000-00-00 00:00:00',
				wp_user_id INT(10),
				longitude varchar(50),
				latitude varchar(50),
				eqno varchar(20),
				action varchar(10),
				ip varchar(20),
				UNIQUE KEY id (id)
			) $charset_collate;";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$returnDB = dbDelta( $sql );
		}
	}
	
}
?>