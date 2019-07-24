<div class="wrap">
    <h2>Litchi Settings</h2>
	<?php
	$hello = get_option('setting_a');
	$world = get_option('setting_b');
	?>
	<h3>
		<?php echo $hello ?> <?php echo $world ?>
	</h3>
    <form method="post" action="options.php"> 
        <?php @settings_fields('wp_plugin_template-group'); ?>

        <?php do_settings_sections('wp_plugin_template'); ?>

        <?php @submit_button(); ?>
    </form>
</div>