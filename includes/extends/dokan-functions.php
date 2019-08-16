<?php



/////////////////////////////////////////////////////
/////////////////////// add new social profile /////////////
function dokan_profile_social_fields($fields) {
	$fields['qq'] = array(
		'icon'  => 'facebook-square',
		'title' => __( 'QQ', 'dokan-lite' ),
	);
	return $fields;
}
// add_filter('dokan_profile_social_fields', 'dokan_profile_social_fields');


//////////////////////////////////////////////////////////////////////
///////////////// add QQ settings /////////////////////////////////////
add_filter( 'dokan_settings_form_bottom', 'extra_fields', 10, 2);

function extra_fields( $current_user, $profile_info ){
	$seller_info= isset( $profile_info['qq'] ) ? $profile_info['qq'] : '';
?>
<div class="gregcustom dokan-form-group">
	<label class="dokan-w3 dokan-control-label" for="setting_address">
		<?php
	_e('QQ', 'dokan'); ?>
	</label>
	<div class="dokan-w5">
		<input class="dokan-form-control input-md valid" type="text" name="qq" id="reg_qq" placeholder="QQ number" value="<?php
	echo esc_textarea($seller_info); ?>"></input>
</div>
</div>
<?php
}

// save the field value

add_action('dokan_store_profile_saved', 'save_extra_fields', 15);

function save_extra_fields($store_id) {
	$dokan_settings = dokan_get_store_info($store_id);

	if (isset($_POST['qq'])) {
		$dokan_settings['qq'] = $_POST['qq'];
	}

	update_user_meta($store_id, 'dokan_profile_settings', $dokan_settings);
}

add_action( 'wp_enqueue_scripts', 'dd_enqueue_scripts' );

function dd_enqueue_scripts() {

	if ( ! dokan_is_store_page() ) {
		return;
	}

	wp_enqueue_style('dokan-magnific-popup');
	wp_enqueue_script('dokan-popup');
}


// show on the store page

add_action('dokan_store_header_info_fields', 'save_seller_info', 10);

function save_seller_info( $vendor_id ) {
	$store_info = dokan_get_store_info( $vendor_id );
	$more       = ' (read more..)';
	$info_text  = '';
	$num_words  = 12;

	if ( isset($store_info['qq']) && !empty($store_info['qq'] ) ) {
		$info_text = $store_info['qq'];
	}

	if ( $info_text ) { ?>
<li>
	<i class="fa fa-globe"></i>
	<a href="#" class="vendor-bio-<?php echo $vendor_id; ?>"><?php echo esc_html( wp_trim_words( $info_text, $num_words, $more ) ); ?></a>

	<style type="text/css">
		.profile-info-summery .profile-info { width: 70%; }
	</style>
</li>
<?php
					  }
}

add_filter('dokan_rest_store_additional_fields', 'dokan_rest_store_additional_fields', 10, 3);
function dokan_rest_store_additional_fields( $additional_fields, $store, $request ) {
	$additional_fields['qq'] = $store->get_info_part( 'qq' );
	return $additional_fields;
}