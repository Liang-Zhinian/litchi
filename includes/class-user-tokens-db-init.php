<?php
global $wpdb;
class user_tokens_db_init {
	
	public function init() {
		
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();		
		$table_name = $wpdb->prefix . 'user_tokens';
		
		
		if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
			$sql = "CREATE TABLE $table_name (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `access_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `access_token_valid` datetime NOT NULL,
  `refresh_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_user_id_token` (`user_id`)
			) $charset_collate;";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$returnDB = dbDelta( $sql );
		}
	}
	
}
?>