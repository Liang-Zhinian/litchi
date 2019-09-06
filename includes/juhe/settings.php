<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if(!class_exists('WP_Plugin_Juhe_Template_Settings'))
{
	class WP_Plugin_Juhe_Template_Settings
	{

		private $enableExpApi = false;
		private $expApiKey = '';
		private $enableSmsApi = false;
		private $smsApiKey = '';

		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			$this->enableExpApi = get_option('litchi_setting_enable_juhe_express_api');
			$this->expApiKey = get_option('litchi_setting_juhe_express_api_key');

			$this->enableSmsApi = get_option('litchi_setting_enable_juhe_sms_api');
			$this->smsApiKey = get_option('litchi_setting_juhe_sms_api_key');


			// register actions
			add_action('admin_init', array(&$this, 'admin_init'));
			add_action('admin_menu', array(&$this, 'add_menu'));
		} // END public function __construct

		public function get_exp_api_status(){
			return $this->enableExpApi;
		}

		public function get_exp_api_key(){
			return $this->expApiKey;
		}

		public function get_sms_api_status(){
			return $this->enableSmsApi;
		}

		public function get_sms_api_key(){
			return $this->smsApiKey;
		}

		/**
         * hook into WP's admin_init action hook
         */
		public function admin_init()
		{
			// register your plugin's settings
			register_setting('wp_plugin_template-group', 'litchi_setting_juhe_sms_api_key');
			register_setting('wp_plugin_template-group', 'litchi_setting_juhe_express_api_key');

			register_setting('wp_plugin_template-group', 'litchi_setting_enable_juhe_sms_api', array(&$this, 'my_settings_sanitize'));
			register_setting('wp_plugin_template-group', 'litchi_setting_enable_juhe_express_api', array(&$this, 'my_settings_sanitize'));

			// add your settings section
			add_settings_section(
				'wp_plugin_template-section', 
				'WP Plugin Template Settings', 
				array(&$this, 'settings_section_wp_plugin_template'), 
				'wp_plugin_template'
			);

			add_settings_field(
				'wp_plugin_template-litchi_setting_enable_juhe_sms_api', 
				'Enable Juhe SMS API', 
				array(&$this, 'settings_field_checkbox'), 
				'wp_plugin_template', 
				'wp_plugin_template-section',
				array(
					'field' => 'litchi_setting_enable_juhe_sms_api'
				)
			);


			add_settings_field(
				'wp_plugin_template-litchi_setting_juhe_sms_api_key', 
				'Juhe SMS API Key', 
				array(&$this, 'settings_field_input_text'), 
				'wp_plugin_template', 
				'wp_plugin_template-section',
				array(
					'field' => 'litchi_setting_juhe_sms_api_key'
				)
			);

			add_settings_field(
				'wp_plugin_template-litchi_setting_enable_juhe_express_api', 
				'Enable Juhe Express API', 
				array(&$this, 'settings_field_checkbox'), 
				'wp_plugin_template', 
				'wp_plugin_template-section',
				array(
					'field' => 'litchi_setting_enable_juhe_express_api'
				)
			);


			add_settings_field(
				'wp_plugin_template-litchi_setting_juhe_express_api_key', 
				'Juhe Express API Key', 
				array(&$this, 'settings_field_input_text'), 
				'wp_plugin_template', 
				'wp_plugin_template-section',
				array(
					'field' => 'litchi_setting_juhe_express_api_key'
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

			// $title = sprintf( esc_attr__( 'Getting Started with %s', 'litchi' ), esc_html__( 'Litchi', 'litchi' ) );

			// add_menu_page(
			// 	$title,
			// 	esc_html__( 'Litchi', 'litchi' ),
			// 	apply_filters( 'litchi_screen_capability', 'manage_options' ),
			// 	'litchi-getting-started',
			// 	array( $this, 'plugin_settings_page' ),
			// 	'dashicons-cart'
			// );
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
