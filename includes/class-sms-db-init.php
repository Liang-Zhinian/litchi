<?php
global $wpdb;
class sms_db_init {
	
	public function init() {
		
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();		
		$table_name = $wpdb->prefix . 'sms';
		
		
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE $table_name (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`created_time` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				`mobile` varchar(255) NOT NULL,
				`tpl_id` INT(10) NOT NULL,
				`tpl_value` varchar(255) NOT NULL,
				`sid` varchar(255) NULL,
				`vercode` varchar(255) NULL,
				`group` varchar(255) NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$returnDB = dbDelta( $sql );
		}
	}
	
}
?>