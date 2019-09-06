<?php
if(!class_exists('WP_Plugin_Template_Settings'))
{
	class WP_Plugin_Template_Settings
	{
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			// register actions
			add_action('admin_init', array(&$this, 'admin_init'));
			add_action('admin_menu', array(&$this, 'add_menu'));

			$this->enable_wx_pay = get_option('litchi_setting_enable_wx_payment');
		} // END public function __construct

		public function get_enable_wx_pay() {
			return $this -> enable_wx_pay;
		}

		/**
         * hook into WP's admin_init action hook
         */
		public function admin_init()
		{
			// register your plugin's settings
			// register_setting('wp_plugin_template-group', 'juhe-sms-api-key');
			// register_setting('wp_plugin_template-group', 'juhe-express-api-key');

			// register_setting('wp_plugin_template-group', 'litchi_setting_enable_juhe_sms_api', array(&$this, 'my_settings_sanitize'));
			// register_setting('wp_plugin_template-group', 'litchi_setting_enable_juhe_express_api', array(&$this, 'my_settings_sanitize'));
			register_setting('wp_plugin_template-group', 'litchi_setting_enable_wx_payment', array(&$this, 'my_settings_sanitize'));

			// add your settings section
			add_settings_section(
			    'wp_plugin_template-section', 
			    'WP Plugin Template Settings', 
			    array(&$this, 'settings_section_wp_plugin_template'), 
			    'wp_plugin_template'
			);

			// // add your setting's fields
			// add_settings_field(
			//     'wp_plugin_template-setting_a', 
			//     'Setting A', 
			//     array(&$this, 'settings_field_input_text'), 
			//     'wp_plugin_template', 
			//     'wp_plugin_template-section',
			//     array(
			//         'field' => 'setting_a'
			//     )
			// );
			// add_settings_field(
			//     'wp_plugin_template-setting_b', 
			//     'Setting B', 
			//     array(&$this, 'settings_field_input_text'), 
			//     'wp_plugin_template', 
			//     'wp_plugin_template-section',
			//     array(
			//         'field' => 'setting_b'
			//     )
			// );

			// add_settings_field(
			//     'wp_plugin_template-litchi_setting_add_additional_order_status', 
			//     'Add additional order status', 
			//     array(&$this, 'settings_field_checkbox'), 
			//     'wp_plugin_template', 
			//     'wp_plugin_template-section',
			//     array(
			//         'field' => 'litchi_setting_add_additional_order_status'
			//     )
			// );

			// add_settings_field(
			// 	'wp_plugin_template-litchi_setting_enable_juhe_sms_api', 
			// 	'Enable Juhe SMS API', 
			// 	array(&$this, 'settings_field_checkbox'), 
			// 	'wp_plugin_template', 
			// 	'wp_plugin_template-section',
			// 	array(
			// 		'field' => 'litchi_setting_enable_juhe_sms_api'
			// 	)
			// );


			// add_settings_field(
			// 	'wp_plugin_template-juhe-sms-api-key', 
			// 	'Juhe SMS API Key', 
			// 	array(&$this, 'settings_field_input_text'), 
			// 	'wp_plugin_template', 
			// 	'wp_plugin_template-section',
			// 	array(
			// 		'field' => 'juhe-sms-api-key'
			// 	)
			// );

			// add_settings_field(
			// 	'wp_plugin_template-litchi_setting_enable_juhe_express_api', 
			// 	'Enable Juhe Express API', 
			// 	array(&$this, 'settings_field_checkbox'), 
			// 	'wp_plugin_template', 
			// 	'wp_plugin_template-section',
			// 	array(
			// 		'field' => 'litchi_setting_enable_juhe_express_api'
			// 	)
			// );


			// add_settings_field(
			// 	'wp_plugin_template-juhe-express-api-key', 
			// 	'Juhe Express API Key', 
			// 	array(&$this, 'settings_field_input_text'), 
			// 	'wp_plugin_template', 
			// 	'wp_plugin_template-section',
			// 	array(
			// 		'field' => 'juhe-express-api-key'
			// 	)
			// );

			add_settings_field(
				'wp_plugin_template-litchi_setting_enable_wx_payment', 
				'Enable WeChat Payment', 
				array(&$this, 'settings_field_checkbox'), 
				'wp_plugin_template', 
				'wp_plugin_template-section',
				array(
					'field' => 'litchi_setting_enable_wx_payment'
				)
			);
			// Possibly do additional admin_init tasks
		} // END public static function activate


		/* Sanitize Callback Function */
		public function my_settings_sanitize( $input ){
			return isset( $input ) ? true : false;
		}

		public function settings_section_wp_plugin_template()
		{
			// Think of this as help text for the section.
			echo 'These settings do things for the WP Plugin Template.';
		}

		/**
         * This function provides checkbox inputs for settings fields
         */
		public function settings_field_checkbox($args)
		{
			// Get the field name from the $args array
			$field = $args['field'];
			// Get the value of this setting
			$value = get_option($field);
			// echo a proper input type="checkbox"
			echo sprintf('<input type="checkbox" name="%s" id="%s" value="1" ' . checked( 1, $value, false ) . ' />', $field, $field);
		} // END public function settings_field_checkbox($args)

		/**
         * This function provides text inputs for settings fields
         */
		public function settings_field_input_text($args)
		{
			// Get the field name from the $args array
			$field = $args['field'];
			// Get the value of this setting
			$value = get_option($field);
			// echo a proper input type="text"
			echo sprintf('<input type="text" name="%s" id="%s" value="%s" style="width: 400px;" />', $field, $field, $value);
		} // END public function settings_field_input_text($args)

		/**
         * add a menu
         */		
		public function add_menu()
		{
			// Add a page to manage this plugin's settings
			// add_options_page(
			//     'WP Plugin Template Settings', 
			//     'WP Plugin Template', 
			//     'manage_options', 
			//     'wp_plugin_template', 
			//     array(&$this, 'plugin_settings_page')
			// );

			$title = sprintf( esc_attr__( 'Getting Started with %s', 'litchi' ), esc_html__( 'Litchi', 'litchi' ) );

			add_menu_page(
				$title,
				esc_html__( 'Litchi', 'litchi' ),
				apply_filters( 'litchi_screen_capability', 'manage_options' ),
				'litchi-getting-started',
				array( $this, 'plugin_settings_page' ),
				'dashicons-cart'
			);
		} // END public function add_menu()

		/**
         * Menu Callback
         */		
		public function plugin_settings_page()
		{
			if(!current_user_can('manage_options'))
			{
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}

			// Render the settings template
			include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
		} // END public function plugin_settings_page()
	} // END class WP_Plugin_Template_Settings
} // END if(!class_exists('WP_Plugin_Template_Settings'))
